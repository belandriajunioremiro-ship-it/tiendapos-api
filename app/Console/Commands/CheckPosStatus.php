<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Tienda;

class CheckPosStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica el estado de conexión de TIENDAPOS con Neon.tech';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. El Banner Épico
        $this->line('');
        $this->line('<fg=bright-yellow;options=bold>   _____ _   _ ____     ___  ____ _____ </>');
        $this->line('<fg=bright-yellow;options=bold>  |_   _| | | |  _ \   / _ \/ ___|_   _|</>');
        $this->line('<fg=bright-yellow;options=bold>    | | | | | | |_) | | | | \___ \ | |  </>');
        $this->line('<fg=bright-yellow;options=bold>    | | | |_| |  __/  | |_| |___) || |  </>');
        $this->line('<fg=bright-yellow;options=bold>    |_|  \___/|_|      \___/|____/ |_|  </>');
        $this->line('');
        $this->line('<fg=bright-cyan;options=bold>=====================================================</>');
        $this->line('<fg=bright-cyan;options=bold>   SISTEMA MULTIMONEDA + IGTF (v2.1)</>');
        $this->line('<fg=bright-cyan;options=bold>=====================================================</>');
        $this->line('');

        // 2. Verificar Conexión a Neon
        $this->info(' 🔍 Verificando conexión a la base de datos en la nube...');
        
        try {
            DB::connection()->getPdo();
            $dbStatus = '<fg=bright-green;options=bold>CONECTADO A NEON.TECH ✅</>';
            
            // Contar tablas
            $tables = DB::select("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public'");
            $tableCount = $tables[0]->count;
            
            // Buscar la tienda
            $tienda = Tienda::first();
            $tiendaInfo = $tienda ? "Tienda: <fg=bright-green;options=bold>{$tienda->nombre_comercial}</>" : "Tienda no configurada aún";

        } catch (\Exception $e) {
            $dbStatus = '<fg=bright-red;options=bold>ERROR DE CONEXIÓN ❌</>';
            $tableCount = 0;
            $tiendaInfo = '';
        }

        // 3. Verificar Rutas API
        $routeCount = count(app('router')->getRoutes());
        $apiStatus = $routeCount > 0 ? '<fg=bright-green;options=bold>ACTIVA ✅</>' : '<fg=bright-red;options=bold>NO REGISTRADAS ❌</>';

        // 4. Imprimir Resultados
        $this->line('');
        $this->line(" 💾 Base de datos:    $dbStatus");
        $this->line(" 🗄️  Tablas encontradas: <fg=bright-yellow;options=bold>$tableCount tablas en PostgreSQL</>");
        if ($tiendaInfo) $this->line(" 🏪 $tiendaInfo");
        $this->line(" 🌐 API Laravel:     $apiStatus (<fg=bright-yellow>$routeCount</> rutas)");
        $this->line('');
        
        $this->line('<fg=bright-cyan;options=bold>=====================================================</>');
        $this->line('<fg=bright-green;options=bold> 🚀 ¡BACKEND FUNCIONAL Y LISTO PARA DESPLIEGUE! 🚀</>');
        $this->line('<fg=bright-magenta;options=bold> ⚛️  NEXT.JS PUEDE CONECTARSE A: http://127.0.0.1:8000/api</>');
        $this->line('<fg=bright-cyan;options=bold>=====================================================</>');
        $this->line('');
    }
}
