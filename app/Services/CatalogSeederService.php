<?php

namespace App\Services;

use App\Models\CategoriaProducto;
use App\Models\MetodoPago;

class CatalogSeederService
{
    public function sembrarCategorias(string $tipo, int $tiendaId): void
    {
        $categorias = match ($tipo) {
            'farmacia'    => ['Medicamentos', 'Higiene', 'Cuidado Personal', 'Prime', 'Bebés'],
            'ferreteria'  => ['Tornillería', 'Herramientas', 'Plomería', 'Electricidad', 'Pintura', 'Construcción'],
            'bodega'      => ['Granos', 'Lácteos', 'Bebidas', 'Limpieza', 'Abarrotes', 'Snacks'],
            'restaurante' => ['Bebidas', 'Comidas', 'Postres', 'Entradas', 'Menú del día'],
            'licoreria'   => ['Cervezas', 'Vinos', 'Whisky', 'Vodka', 'Sin Alcohol', 'Hielo'],
            'abarrotes'   => ['Bebidas', 'Lácteos', 'Granos', 'Limpieza', 'Snacks', 'Hogar'],
            'ropa'        => ['Hombre', 'Dama', 'Niños', 'Accesorios', 'Calzado'],
            'motos'       => ['Repuestos', 'Accesorios', 'Lubricantes', 'Herramientas', 'Cascos'],
            default       => ['General', 'Bebidas', 'Abarrotes', 'Limpieza'],
        };

        foreach ($categorias as $nombre) {
            $slug = strtolower(str_replace(
                [' ', 'ñ', 'á', 'é', 'í', 'ó', 'ú'],
                ['-', 'n', 'a', 'e', 'i', 'o', 'u'],
                $nombre
            ));
            CategoriaProducto::firstOrCreate(
                ['slug' => $slug, 'tienda_id' => $tiendaId],
                ['nombre' => $nombre, 'nivel' => 1, 'ruta' => $slug, 'activo' => true]
            );
        }
    }

    public function sembrarMetodosPago(string $pais, int $tiendaId): void
    {
        $metodos = match ($pais) {
            'VE' => [
                ['Efectivo USD', 'efectivo', 'USD', false, false, true],
                ['Efectivo VES', 'efectivo', 'VES', false, false, false],
                ['Pago Móvil', 'pago_movil', 'VES', true, true, false],
                ['Transferencia', 'transferencia', 'VES', true, true, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'VES', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'VES', true, false, false],
                ['Zelle', 'zelle', 'USD', true, false, true],
                ['USDT/Binance', 'criptomoneda', 'UST', true, false, true],
            ],
            'CO' => [
                ['Efectivo COP', 'efectivo', 'COP', false, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'COP', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'COP', true, false, false],
                ['Transferencia', 'transferencia', 'COP', true, true, false],
                ['PSE', 'transferencia', 'COP', true, true, false],
                ['Nequi', 'pago_movil', 'COP', true, true, false],
            ],
            'MX' => [
                ['Efectivo MXN', 'efectivo', 'MXN', false, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'MXN', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'MXN', true, false, false],
                ['Transferencia SPEI', 'transferencia', 'MXN', true, true, false],
                ['Código QR', 'pago_movil', 'MXN', true, false, false],
            ],
            'EC' => [
                ['Efectivo USD', 'efectivo', 'USD', false, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'USD', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'USD', true, false, false],
                ['Transferencia', 'transferencia', 'USD', true, true, false],
                ['Deuna', 'pago_movil', 'USD', true, false, false],
            ],
            'AR' => [
                ['Efectivo ARS', 'efectivo', 'ARS', false, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'ARS', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'ARS', true, false, false],
                ['Transferencia', 'transferencia', 'ARS', true, true, false],
                ['QR Mercado Pago', 'pago_movil', 'ARS', true, false, false],
            ],
            'PE' => [
                ['Efectivo PEN', 'efectivo', 'PEN', false, false, false],
                ['Yape', 'pago_movil', 'PEN', true, false, false],
                ['Plin', 'pago_movil', 'PEN', true, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'PEN', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'PEN', true, false, false],
            ],
            'CL' => [
                ['Efectivo CLP', 'efectivo', 'CLP', false, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'CLP', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'CLP', true, false, false],
                ['Transferencia', 'transferencia', 'CLP', true, true, false],
                ['Mach', 'pago_movil', 'CLP', true, false, false],
            ],
            'BO' => [
                ['Efectivo BOB', 'efectivo', 'BOB', false, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'BOB', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'BOB', true, false, false],
                ['QR', 'pago_movil', 'BOB', true, false, false],
            ],
            'UY' => [
                ['Efectivo UYU', 'efectivo', 'UYU', false, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'UYU', true, false, false],
                ['Tarjeta Crédito', 'tarjeta_credito', 'UYU', true, false, false],
                ['Transferencia', 'transferencia', 'UYU', true, true, false],
            ],
            default => [
                ['Efectivo', 'efectivo', 'USD', false, false, false],
                ['Tarjeta Débito', 'tarjeta_debito', 'USD', true, false, false],
                ['Transferencia', 'transferencia', 'USD', true, true, false],
            ],
        };

        foreach ($metodos as $m) {
            MetodoPago::firstOrCreate(
                ['nombre' => $m[0], 'moneda' => $m[2], 'tienda_id' => $tiendaId],
                [
                    'tipo'                => $m[1],
                    'requiere_referencia' => $m[3],
                    'requiere_banco'      => $m[4],
                    'grava_igtf'          => $m[5],
                    'activo'              => true,
                ]
            );
        }
    }
}
