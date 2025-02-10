<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use Illuminate\Http\Request;

class MovimientoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'usuario_id' => 'required|exists:users,id',
            'gerente_id' => 'nullable|exists:users,id',
            'tipo' => 'required|in:entrada,salida,ajuste,devolución',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string'
        ]);

        $movimiento = Movimiento::create([
            'producto_id' => $request->producto_id,
            'usuario_id' => $request->usuario_id,
            'gerente_id' => $request->gerente_id,
            'tipo' => $request->tipo,
            'cantidad' => $request->cantidad,
            'fecha_movimiento' => now(),
            'motivo' => $request->motivo
        ]);

        return response()->json([
            'message' => 'Movimiento registrado exitosamente',
            'movimiento' => $movimiento
        ], 201);
    }
}
