<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function generarReporte(Request $request)
    {
        try {
            $startDate = $request->query('start');
            $endDate = $request->query('end');

            if (!$startDate || !$endDate) {
                return response()->json(['error' => 'Faltan parámetros de fecha'], 400);
            }

            // Reporte de movimientos dentro del rango de fechas
            $movimientos = Movimiento::whereBetween('created_at', [$startDate, $endDate])
                ->with(['producto'])
                ->get();

            return response()->json([
                'movimientos' => $movimientos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los reportes', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function productosMasMovidos(Request $request)
    {
        try {
            $startDate = $request->query('start');
            $endDate = $request->query('end');

            if (!$startDate || !$endDate) {
                return response()->json(['error' => 'Faltan parámetros de fecha'], 400);
            }

            $productos = Movimiento::select('codigo_producto', DB::raw('SUM(cantidad) as total_movido'))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('estado', 'aprobado')
                ->groupBy('codigo_producto')
                ->orderByDesc('total_movido')
                ->with(['producto.categoria']) // 🔥 Importante para traer la categoría
                ->get();

            // Formatear la respuesta incluyendo la categoría
            $productos->transform(function ($producto) {
                return [
                    'codigo_producto' => $producto->codigo_producto,
                    'total_movido' => $producto->total_movido,
                    'categoria' => $producto->producto && $producto->producto->categoria ? $producto->producto->categoria->nombre : 'Sin categoría',
                ];
            });

            return response()->json([
                'productos_mas_movidos' => $productos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los productos más movidos', 'detalle' => $e->getMessage()], 500);
        }
    }



    public function motivosMasFrecuentes(Request $request)
    {
        try {
            $startDate = $request->query('start');
            $endDate = $request->query('end');

            if (!$startDate || !$endDate) {
                return response()->json(['error' => 'Faltan parámetros de fecha'], 400);
            }

            $motivos = Movimiento::select('motivo', DB::raw('COUNT(*) as total'))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('motivo')
                ->orderByDesc('total')
                ->get();

            return response()->json([
                'motivos_mas_frecuentes' => $motivos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los motivos más frecuentes', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function reporteStock()
    {
        try {
            $productos = Inventario::select('codigo', 'descripcion', 'en_stock', 'minimo', 'maximo', 'categoria_id')
                ->with('categoria') // 🔥 Se añade la relación para traer la categoría
                ->withCount(['movimientos as stock_real' => function ($query) {
                    $query->where('estado', 'aprobado')->select(DB::raw('SUM(cantidad)'));
                }])
                ->get();

            // Evaluar el estado del stock en relación a los valores mínimos y máximos
            $productos->transform(function ($producto) {
                if ($producto->stock_real < $producto->minimo) {
                    $producto->estado_stock = 'bajo';
                } elseif ($producto->stock_real > $producto->maximo) {
                    $producto->estado_stock = 'excedente';
                } else {
                    $producto->estado_stock = 'óptimo';
                }

                // 🔥 Agregamos la categoría en la respuesta
                $producto->categoria = $producto->categoria ? $producto->categoria->nombre : 'Sin categoría';

                return $producto;
            });

            return response()->json([
                'reporte_stock' => $productos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener el reporte de stock', 'detalle' => $e->getMessage()], 500);
        }
    }
}
