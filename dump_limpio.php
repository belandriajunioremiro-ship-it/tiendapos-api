<?php
// Genera un dump limpio estilo MySQL: solo CREATE TABLE con columnas
$host = 'ep-royal-sunset-adkfjzit.c-2.us-east-1.aws.neon.tech';
$db   = 'tiendapos_test';
$user = 'neondb_owner';
$pass = 'npg_9H8OgPVXaory';

$dsn = "pgsql:host=$host;port=5432;dbname=$db;sslmode=require";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Obtener todas las tablas del schema public
$stmt = $pdo->query("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = 'public' 
      AND table_type = 'BASE TABLE'
    ORDER BY table_name
");
$tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "-- TiendaPOS v2.1 — Dump Limpio\n";
echo "-- Base de datos: $db\n";
echo "-- Tablas: " . count($tablas) . "\n";
echo "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tablas as $tabla) {
    // Columnas
    $colStmt = $pdo->prepare("
        SELECT 
            c.column_name,
            c.data_type,
            c.udt_name,
            c.character_maximum_length,
            c.numeric_precision,
            c.numeric_scale,
            c.is_nullable,
            c.column_default,
            c.ordinal_position
        FROM information_schema.columns c
        WHERE c.table_schema = 'public' 
          AND c.table_name = ?
        ORDER BY c.ordinal_position
    ");
    $colStmt->execute([$tabla]);
    $columnas = $colStmt->fetchAll(PDO::FETCH_ASSOC);

    // Primary key
    $pkStmt = $pdo->prepare("
        SELECT a.attname
        FROM pg_index i
        JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
        WHERE i.indrelid = ?::regclass AND i.indisprimary
    ");
    $pkStmt->execute([$tabla]);
    $pks = $pkStmt->fetchAll(PDO::FETCH_COLUMN);

    // Foreign keys
    $fkStmt = $pdo->prepare("
        SELECT
            kcu.column_name,
            ccu.table_name AS referenced_table,
            kcu.constraint_name
        FROM information_schema.key_column_usage kcu
        JOIN information_schema.table_constraints tc 
            ON tc.constraint_name = kcu.constraint_name
        JOIN information_schema.constraint_column_usage ccu
            ON ccu.constraint_name = tc.constraint_name
        WHERE tc.table_name = ?
          AND tc.constraint_type = 'FOREIGN KEY'
          AND tc.table_schema = 'public'
    ");
    $fkStmt->execute([$tabla]);
    $fks = $fkStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "-- ═══════════════════════════════════════════════\n";
    echo "-- TABLA: $tabla\n";
    echo "-- ═══════════════════════════════════════════════\n";
    echo "CREATE TABLE $tabla (\n";

    $lineas = [];
    foreach ($columnas as $col) {
        $tipo = $col['udt_name'];
        
        // Mapear tipos internos a legibles
        $mapa = [
            'int4' => 'INTEGER',
            'int8' => 'BIGINT',
            'int2' => 'SMALLINT',
            'varchar' => 'VARCHAR',
            'bpchar' => 'CHAR',
            'float8' => 'DOUBLE PRECISION',
            'float4' => 'REAL',
            'numeric' => 'NUMERIC',
            'bool' => 'BOOLEAN',
            'timestamp' => 'TIMESTAMP',
            'timestamptz' => 'TIMESTAMPTZ',
            'date' => 'DATE',
            'time' => 'TIME',
            'text' => 'TEXT',
            'jsonb' => 'JSONB',
            'json' => 'JSON',
            'uuid' => 'UUID',
            'bytea' => 'BYTEA',
        ];
        $tipo = $mapa[$tipo] ?? strtoupper($tipo);

        if ($col['character_maximum_length']) {
            $tipo .= "({$col['character_maximum_length']})";
        } elseif ($col['numeric_precision'] && $col['data_type'] === 'numeric') {
            $tipo .= "({$col['numeric_precision']},{$col['numeric_scale']})";
        }

        $linea = "    {$col['column_name']} $tipo";
        
        if (in_array($col['column_name'], $pks)) {
            $linea .= " PRIMARY KEY";
        }
        
        if ($col['is_nullable'] === 'NO') {
            $linea .= " NOT NULL";
        }
        
        if ($col['column_default'] !== null) {
            $default = $col['column_default'];
            // Limpiar defaults internos de Postgres
            $default = preg_replace("/^nextval\('.*?'::regclass\)$/", 'AUTO_INCREMENT', $default);
            $default = str_replace("::character varying", '', $default);
            $default = str_replace("::integer", '', $default);
            $default = str_replace("::numeric", '', $default);
            $default = str_replace("::boolean", '', $default);
            $default = str_replace("::text", '', $default);
            $default = str_replace("public.", '', $default);
            $linea .= " DEFAULT $default";
        }

        $lineas[] = $linea;
    }

    // Agregar FOREIGN KEYS
    foreach ($fks as $fk) {
        $lineas[] = "    FOREIGN KEY ({$fk['column_name']}) REFERENCES {$fk['referenced_table']}({$fk['column_name']})";
    }

    echo implode(",\n", $lineas);
    echo "\n);\n\n";
}

echo "-- Total: " . count($tablas) . " tablas\n";
