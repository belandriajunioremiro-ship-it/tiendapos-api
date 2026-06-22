<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

class PosMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:map';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra el mapa de rutas API y la matriz de permisos de TIENDAPOS.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Banner Épico
        $this->line('');
        $this->line('<fg=bright-yellow;options=bold>   _____ _   _ ____     ___  ____ _____ </>');
        $this->line('<fg=bright-yellow;options=bold>  |_   _| | | |  _ \   / _ \/ ___|_   _|</>');
        $this->line('<fg=bright-yellow;options=bold>    | | | | | | |_) | | | | \___ \ | |  </>');
        $this->line('<fg=bright-yellow;options=bold>    | | | |_| |  __/  | |_| |___) || |  </>');
        $this->line('<fg=bright-yellow;options=bold>    |_|  \___/|_|      \___/|____/ |_|  </>');
        $this->line('');
        $this->line('<fg=bright-yellow;options=bold>=====================================================</>');
        $this->line('<fg=bright-yellow;options=bold>      TIENDAPOS - MAPA DEL SISTEMA v2.1</>');
        $this->line('<fg=bright-yellow;options=bold>=====================================================</>');
        $this->line('');

        // 2. Sección 1: Rutas API
        $this->line('<options=bold>📡 SECCIÓN 1: RUTAS API POR MÓDULO</>');
        $this->line('');

        $routes = Route::getRoutes();
        
        $modules = [
            '🔐 Auth' => ['api/login', 'api/logout', 'api/user'],
            '🏪 Tienda' => ['api/tienda'],
            '📦 Productos' => ['api/productos', 'api/categorias'],
            '📋 Inventario' => ['api/inventario'],
            '🛒 Compras' => ['api/proveedores', 'api/ordenes-compra'],
            '💵 Ventas / POS' => ['api/ventas', 'api/clientes'],
            '🗄️ Caja' => ['api/cajas'],
            '⚙️ Admin' => ['api/usuarios', 'api/reportes', 'api/impuestos', 'api/descuentos', 'api/metodos-pago', 'api/tasas-cambio'],
        ];

        foreach ($modules as $moduleName => $keywords) {
            $rows = [];
            foreach ($routes as $route) {
                $uri = $route->uri();
                // Filter only api routes
                if (strpos($uri, 'api/') !== 0) {
                    continue;
                }

                $belongsToModule = false;
                foreach ($keywords as $keyword) {
                    if (strpos($uri, $keyword) === 0) {
                        $belongsToModule = true;
                        break;
                    }
                }

                if ($belongsToModule) {
                    $methods = implode('|', array_filter($route->methods(), fn($m) => $m !== 'HEAD'));
                    
                    $action = $route->getActionName();
                    // Clean up action name
                    if ($action !== 'Closure') {
                        $action = class_basename($action);
                    }

                    $rows[] = [
                        $methods,
                        $uri,
                        $action
                    ];
                }
            }

            if (count($rows) > 0) {
                $this->line("<fg=bright-cyan>========================================</>");
                $this->line("<fg=bright-cyan;options=bold>$moduleName</>");
                $this->line("<fg=bright-cyan>========================================</>");
                $this->table(['Método', 'Ruta', 'Acción'], $rows);
                $this->line('');
            }
        }

        // 3. Sección 2: Matriz de Seguridad (Spatie)
        $this->line('<options=bold>🛡️ SECCIÓN 2: MATRIZ DE SEGURIDAD (ROLES Y PERMISOS)</>');
        $this->line('');

        try {
            $adminRole = Role::where('name', 'admin')->first();
            $supervisorRole = Role::where('name', 'supervisor')->first();
            $cajeroRole = Role::where('name', 'cajero')->first();

            if ($adminRole) {
                $adminCount = $adminRole->permissions()->count();
                $this->line("<fg=bright-green;options=bold>🛡️ ADMIN:</> $adminCount permisos (Acceso Total) ✅");
            } else {
                $this->line("<fg=bright-red;options=bold>🛡️ ADMIN:</> Rol no encontrado. Corre las migraciones/seeders.");
            }

            if ($supervisorRole) {
                $supervisorCount = $supervisorRole->permissions()->count();
                $this->line("<fg=bright-cyan;options=bold>🛡️ SUPERVISOR:</> $supervisorCount permisos (Operación + Gestión) ✅");
                $supervisorPerms = $supervisorRole->permissions()->pluck('name')->toArray();
                $this->line("   <fg=gray>Permisos: " . implode(', ', $supervisorPerms) . "</>");
            }

            if ($cajeroRole) {
                $cajeroCount = $cajeroRole->permissions()->count();
                $this->line("<fg=bright-yellow;options=bold>🛡️ CAJERO:</> $cajeroCount permisos (Ventas + Caja + Clientes) ✅");
                $cajeroPerms = $cajeroRole->permissions()->pluck('name')->toArray();
                $this->line("   <fg=gray>Permisos: " . implode(', ', $cajeroPerms) . "</>");
            }
        } catch (\Exception $e) {
            $this->line("<fg=bright-red>Error conectando a la base de datos para leer roles: " . $e->getMessage() . "</>");
        }

        $this->line('');

        // 4. Pie de página
        $this->line('<fg=bright-cyan;options=bold>=====================================================</>');
        $this->line('<fg=bright-green;options=bold> 🚀 ARQUITECTURA LISTA PARA DESPLIEGUE FRONTEND (NEXT.JS) 🚀</>');
        $this->line('<fg=bright-cyan;options=bold>=====================================================</>');
        $this->line('');
    }
}
