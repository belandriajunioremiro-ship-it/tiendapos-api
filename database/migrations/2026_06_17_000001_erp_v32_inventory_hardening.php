<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =========================================================================
        // SECCIÓN A — UNIDADES: semántica vendible/logística
        // =========================================================================
        DB::unprepared("
            ALTER TABLE unidades
              ADD COLUMN IF NOT EXISTS es_vendible BOOLEAN NOT NULL DEFAULT TRUE,
              ADD COLUMN IF NOT EXISTS es_logistica BOOLEAN NOT NULL DEFAULT TRUE;
        ");

        // =========================================================================
        // SECCIÓN B — PRODUCTOS: control de lotes
        // =========================================================================
        DB::unprepared("
            ALTER TABLE productos
              ADD COLUMN IF NOT EXISTS controla_lotes BOOLEAN NOT NULL DEFAULT FALSE;
        ");
        DB::unprepared("COMMENT ON COLUMN productos.controla_lotes IS 'TRUE = farmacia/alimentos. Obliga a usar inventario_lotes y FEFO. FALSE = PPS global en inventario.costo_promedio.';");

        // =========================================================================
        // SECCIÓN C — TRAZABILIDAD EN items_venta (snapshot de presentación)
        // =========================================================================
        DB::unprepared("
            ALTER TABLE items_venta
              ADD COLUMN IF NOT EXISTS unidad_venta_id BIGINT REFERENCES unidades(id),
              ADD COLUMN IF NOT EXISTS factor_snapshot NUMERIC(14,6),
              ADD COLUMN IF NOT EXISTS cantidad_venta NUMERIC(14,4);
        ");
        DB::unprepared("
            ALTER TABLE items_venta
              ADD CONSTRAINT chk_items_venta_snapshot_consistency
                CHECK ((unidad_venta_id IS NULL AND factor_snapshot IS NULL AND cantidad_venta IS NULL)
                    OR (unidad_venta_id IS NOT NULL AND factor_snapshot IS NOT NULL AND cantidad_venta IS NOT NULL)),
              ADD CONSTRAINT chk_cantidad_venta_positiva
                CHECK (cantidad_venta IS NULL OR cantidad_venta > 0);
        ");
        DB::unprepared("COMMENT ON COLUMN items_venta.cantidad_venta IS 'Cantidad en la unidad de venta (ej. 1 Caja x24). cantidad = cantidad_venta × factor_snapshot.';");

        // =========================================================================
        // SECCIÓN D — TRAZABILIDAD EN movimientos_inventario (snapshot)
        // =========================================================================
        DB::unprepared("
            ALTER TABLE movimientos_inventario
              ADD COLUMN IF NOT EXISTS unidad_origen_id BIGINT REFERENCES unidades(id),
              ADD COLUMN IF NOT EXISTS factor_snapshot NUMERIC(14,6),
              ADD COLUMN IF NOT EXISTS cantidad_origen NUMERIC(14,4),
              ADD CONSTRAINT chk_factor_consistency
                CHECK ((factor_snapshot IS NULL AND cantidad_origen IS NULL AND unidad_origen_id IS NULL)
                    OR (factor_snapshot IS NOT NULL AND cantidad_origen IS NOT NULL AND unidad_origen_id IS NOT NULL));
        ");

        // =========================================================================
        // SECCIÓN E — TABLA inventario_lotes (FEFO para farmacia)
        // =========================================================================
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS inventario_lotes (
                id                BIGSERIAL    PRIMARY KEY,
                inventario_id     BIGINT       NOT NULL REFERENCES inventario(id) ON DELETE CASCADE,
                lote              VARCHAR(50)  NOT NULL,
                fecha_vencimiento DATE         NOT NULL,
                cantidad          NUMERIC(14,4) NOT NULL DEFAULT 0,
                costo_unitario    NUMERIC(14,6),
                creado_en         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
                actualizado_en    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
                CONSTRAINT chk_inv_lotes_cantidad_positiva CHECK (cantidad >= 0),
                CONSTRAINT chk_inv_lotes_fecha_valida CHECK (fecha_vencimiento > CURRENT_DATE - 30),
                UNIQUE (inventario_id, lote)
            );
        ");

        // Índice FEFO: solo lotes con stock activo, ordenados por vencimiento ascendente
        DB::unprepared("
            CREATE INDEX IF NOT EXISTS idx_inv_lotes_fefo
              ON inventario_lotes (inventario_id, fecha_vencimiento ASC)
              WHERE cantidad > 0;
        ");

        // Trigger de update automático para actualizado_en
        DB::unprepared("
            CREATE TRIGGER trg_ts_inventario_lotes
              BEFORE UPDATE ON inventario_lotes
              FOR EACH ROW EXECUTE FUNCTION fn_actualizar_timestamp();
        ");

        DB::unprepared("COMMENT ON TABLE inventario_lotes IS 'Lotes por inventario. SOLO productos con productos.controla_lotes=TRUE. Política FEFO: vender primero el de fecha_vencimiento más próxima. Lot agotado (cantidad=0) NO se borra: se conserva para historial fiscal. El índice FEFO lo excluye automáticamente.';");

        // =========================================================================
        // SECCIÓN F — TRAZABILIDAD DE LOTES EN OPERACIONES
        // =========================================================================
        DB::unprepared("ALTER TABLE items_ajuste ADD COLUMN IF NOT EXISTS lote_id BIGINT REFERENCES inventario_lotes(id);");

        // items_traslado: solo lote_origen_id. El destino se crea automáticamente con el MISMO código.
        DB::unprepared("
            ALTER TABLE items_traslado
              ADD COLUMN IF NOT EXISTS lote_origen_id BIGINT REFERENCES inventario_lotes(id);
        ");

        // =========================================================================
        // SECCIÓN G — TRIGGER: coherencia dimensional en movimientos_inventario
        // =========================================================================
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_validar_coherencia_unidad()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$             DECLARE
                v_tipo_origen VARCHAR(20);
                v_tipo_base   VARCHAR(20);
            BEGIN
                -- Solo validar si los campos snapshot vienen poblados
                IF NEW.unidad_origen_id IS NULL THEN RETURN NEW; END IF;

                SELECT u.tipo INTO v_tipo_origen FROM unidades WHERE id = NEW.unidad_origen_id;

                SELECT u.tipo INTO v_tipo_base
                FROM unidades u
                JOIN productos p ON p.unidad_id = u.id
                JOIN variantes_producto vp ON vp.producto_id = p.id
                WHERE vp.id = NEW.variante_id;

                IF v_tipo_origen IS NULL OR v_tipo_base IS NULL THEN
                    RAISE EXCEPTION 'No se pudo resolver tipo de unidad para variante_id=%', NEW.variante_id;
                END IF;

                IF v_tipo_origen <> v_tipo_base THEN
                    RAISE EXCEPTION 'Incoherencia dimensional: unidad origen (tipo=%) no coincide con unidad base del producto (tipo=%). Venta rechazada.',
                        v_tipo_origen, v_tipo_base;
                END IF;

                RETURN NEW;
            END;
            $$;
        ");

        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_coherencia_unidad ON movimientos_inventario;
            CREATE TRIGGER trg_coherencia_unidad
              BEFORE INSERT OR UPDATE ON movimientos_inventario
              FOR EACH ROW EXECUTE FUNCTION fn_validar_coherencia_unidad();
        ");

        // =========================================================================
        // SECCIÓN H — TRIGGER: guard que prohíbe lotes en productos sin controla_lotes
        // =========================================================================
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_guard_inventario_lotes()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$             DECLARE
                v_controla_lotes BOOLEAN;
            BEGIN
                SELECT p.controla_lotes INTO v_controla_lotes
                FROM inventario i
                JOIN variantes_producto vp ON vp.id = i.variante_id
                JOIN productos p ON p.id = vp.producto_id
                WHERE i.id = NEW.inventario_id;

                IF v_controla_lotes IS NULL THEN
                    RAISE EXCEPTION 'No se encontró inventario_id=%', NEW.inventario_id;
                END IF;

                IF v_controla_lotes = FALSE THEN
                    RAISE EXCEPTION 'El producto asociado al inventario_id=% tiene controla_lotes=FALSE. No se permiten lotes.',
                        NEW.inventario_id;
                END IF;

                RETURN NEW;
            END;
            $$;
        ");

        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_guard_inventario_lotes ON inventario_lotes;
            CREATE TRIGGER trg_guard_inventario_lotes
              BEFORE INSERT OR UPDATE ON inventario_lotes
              FOR EACH ROW EXECUTE FUNCTION fn_guard_inventario_lotes();
        ");

        // =========================================================================
        // SECCIÓN I — TRIGGER: sincronización condicional inventario_lotes -> inventario
        // =========================================================================
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_sync_inventario_lotes()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$             DECLARE
                v_inventario_id BIGINT;
                v_controla_lotes BOOLEAN;
                v_nueva_cantidad NUMERIC(14,4);
            BEGIN
                v_inventario_id := COALESCE(NEW.inventario_id, OLD.inventario_id);

                -- Verificar si el producto controla lotes
                SELECT p.controla_lotes INTO v_controla_lotes
                FROM inventario i
                JOIN variantes_producto vp ON vp.id = i.variante_id
                JOIN productos p ON p.id = vp.producto_id
                WHERE i.id = v_inventario_id;

                -- Si NO controla lotes, no tocar inventario.cantidad_disponible
                IF v_controla_lotes IS NULL OR v_controla_lotes = FALSE THEN
                    RETURN COALESCE(NEW, OLD);
                END IF;

                -- Recalcular cantidad_disponible desde la suma de lotes
                SELECT COALESCE(SUM(cantidad), 0) INTO v_nueva_cantidad
                FROM inventario_lotes
                WHERE inventario_id = v_inventario_id;

                UPDATE inventario
                SET cantidad_disponible = v_nueva_cantidad,
                    actualizado_en = NOW()
                WHERE id = v_inventario_id;

                RETURN COALESCE(NEW, OLD);
            END;
            $$;
        ");

        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_sync_inventario_lotes ON inventario_lotes;
            CREATE TRIGGER trg_sync_inventario_lotes
              AFTER INSERT OR UPDATE OR DELETE ON inventario_lotes
              FOR EACH ROW EXECUTE FUNCTION fn_sync_inventario_lotes();
        ");

        // =========================================================================
        // SECCIÓN J — TRIGGER: en traslado, crear lote destino automáticamente
        //              con el MISMO código de lote y fecha_vencimiento
        // =========================================================================
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_traslado_replicar_lote()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$             DECLARE
                v_lote_origen        VARCHAR(50);
                v_fecha_venc         DATE;
                v_costo              NUMERIC(14,6);
                v_inventario_destino BIGINT;
                v_lote_destino_id    BIGINT;
            BEGIN
                -- Solo actuar si se especificó lote_origen_id y se recibió mercancía
                IF NEW.lote_origen_id IS NULL OR NEW.cantidad_recibida IS NULL OR NEW.cantidad_recibida <= 0 THEN
                    RETURN NEW;
                END IF;

                -- Leer datos del lote origen
                SELECT il.lote, il.fecha_vencimiento, il.costo_unitario
                INTO v_lote_origen, v_fecha_venc, v_costo
                FROM items_traslado it
                JOIN inventario_lotes il ON il.id = it.lote_origen_id
                WHERE it.id = NEW.id;

                IF v_lote_origen IS NULL THEN RETURN NEW; END IF;

                -- Resolver inventario_id del destino (a partir del traslado)
                SELECT i.id INTO v_inventario_destino
                FROM inventario i
                WHERE i.variante_id = NEW.variante_id
                  AND i.almacen_id = (SELECT almacen_destino_id FROM traslados_stock WHERE id = NEW.traslado_id);

                IF v_inventario_destino IS NULL THEN
                    RAISE EXCEPTION 'No existe inventario en el almacén destino para variante_id=%', NEW.variante_id;
                END IF;

                -- Upsert del lote en destino (mismo código + misma fecha)
                INSERT INTO inventario_lotes (inventario_id, lote, fecha_vencimiento, cantidad, costo_unitario)
                VALUES (v_inventario_destino, v_lote_origen, v_fecha_venc, NEW.cantidad_recibida, v_costo)
                ON CONFLICT (inventario_id, lote)
                DO UPDATE SET
                    cantidad = inventario_lotes.cantidad + EXCLUDED.cantidad,
                    actualizado_en = NOW()
                RETURNING id INTO v_lote_destino_id;

                -- Descontar del lote origen
                UPDATE inventario_lotes
                SET cantidad = cantidad - NEW.cantidad_recibida,
                    actualizado_en = NOW()
                WHERE id = NEW.lote_origen_id;

                RETURN NEW;
            END;
            $$;
        ");

        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_traslado_replicar_lote ON items_traslado;
            CREATE TRIGGER trg_traslado_replicar_lote
              AFTER UPDATE OF cantidad_recibida ON items_traslado
              FOR EACH ROW
              WHEN (NEW.cantidad_recibida IS NOT NULL AND NEW.cantidad_recibida > 0)
              EXECUTE FUNCTION fn_traslado_replicar_lote();
        ");
    }

    public function down(): void
    {
        // Orden inverso: triggers -> funciones -> tablas -> columnas
        DB::unprepared("DROP TRIGGER IF EXISTS trg_traslado_replicar_lote ON items_traslado;");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_traslado_replicar_lote();");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_sync_inventario_lotes ON inventario_lotes;");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_sync_inventario_lotes();");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_guard_inventario_lotes ON inventario_lotes;");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_guard_inventario_lotes();");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_coherencia_unidad ON movimientos_inventario;");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_validar_coherencia_unidad();");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_ts_inventario_lotes ON inventario_lotes;");

        DB::unprepared("ALTER TABLE items_traslado DROP COLUMN IF EXISTS lote_origen_id;");
        DB::unprepared("ALTER TABLE items_ajuste DROP COLUMN IF EXISTS lote_id;");

        DB::unprepared("DROP INDEX IF EXISTS idx_inv_lotes_fefo;");
        DB::unprepared("DROP TABLE IF EXISTS inventario_lotes;");

        DB::unprepared("ALTER TABLE movimientos_inventario DROP CONSTRAINT IF EXISTS chk_factor_consistency;");
        DB::unprepared("ALTER TABLE movimientos_inventario DROP COLUMN IF EXISTS cantidad_origen;");
        DB::unprepared("ALTER TABLE movimientos_inventario DROP COLUMN IF EXISTS factor_snapshot;");
        DB::unprepared("ALTER TABLE movimientos_inventario DROP COLUMN IF EXISTS unidad_origen_id;");

        DB::unprepared("ALTER TABLE items_venta DROP CONSTRAINT IF EXISTS chk_cantidad_venta_positiva;");
        DB::unprepared("ALTER TABLE items_venta DROP CONSTRAINT IF EXISTS chk_items_venta_snapshot_consistency;");
        DB::unprepared("ALTER TABLE items_venta DROP COLUMN IF EXISTS cantidad_venta;");
        DB::unprepared("ALTER TABLE items_venta DROP COLUMN IF EXISTS factor_snapshot;");
        DB::unprepared("ALTER TABLE items_venta DROP COLUMN IF EXISTS unidad_venta_id;");

        DB::unprepared("ALTER TABLE productos DROP COLUMN IF EXISTS controla_lotes;");

        DB::unprepared("ALTER TABLE unidades DROP COLUMN IF EXISTS es_logistica;");
        DB::unprepared("ALTER TABLE unidades DROP COLUMN IF EXISTS es_vendible;");
    }
};
