<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function generarReporte(Request $request)
    {
        try {
            $categoriaId = $request->query('categoria');
            $startDate = $request->query('start');
            $endDate = $request->query('end');

            if (!$categoriaId || !$startDate || !$endDate) {
                return response()->json(['error' => 'Faltan parámetros'], 400);
            }

            $reportes = Movimiento::where('categoria_id', $categoriaId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            return response()->json($reportes, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los reportes', 'detalle' => $e->getMessage()], 500);
        }
    }
}
