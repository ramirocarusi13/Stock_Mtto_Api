<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Inventario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovimientoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'codigo_producto' => 'required|exists:inventarios,codigo',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|in:ingreso,egreso,prestamo,devolucion',
        ]);

        DB::beginTransaction();
        try {
            // Buscar el producto por código
            $producto = Inventario::where('codigo', $request->codigo_producto)->firstOrFail();

            // Manejo de stock basado en el tipo de movimiento
            if ($request->motivo === 'ingreso' || $request->motivo === 'devolucion') {
                $producto->en_stock += $request->cantidad;
                $producto->entradas += $request->cantidad;
            } elseif ($request->motivo === 'egreso' || $request->motivo === 'prestamo') {
                if ($producto->en_stock < $request->cantidad) {
                    return response()->json(['error' => 'Stock insuficiente'], 400);
                }
                $producto->en_stock -= $request->cantidad;
                $producto->salidas += $request->cantidad;
            }

            // Guardar cambios en el producto
            $producto->save();

            // Crear el movimiento
            $movimiento = Movimiento::create([
                'codigo_producto' => $request->codigo_producto,
                'usuario_id' => Auth::id(),
                'cantidad' => $request->cantidad,
                'estado' => 'pendiente',
                'motivo' => $request->motivo,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Movimiento registrado correctamente',
                'movimiento' => $movimiento,
                'nuevo_stock' => $producto->en_stock,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo registrar el movimiento', 'detalle' => $e->getMessage()], 500);
        }
    }
}
