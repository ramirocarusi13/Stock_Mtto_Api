<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Salida;
use App\Models\Inventario;
use Illuminate\Support\Facades\Auth;

class SalidaController extends Controller
{
    // Obtener todas las salidas
    public function index()
    {
        $salidas = Salida::with('inventario', 'usuario')->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $salidas], 200);
    }

    // Registrar una nueva salida
    public function registrarSalida(Request $request)
    {
        $request->validate([
            'inventario_id' => 'required|exists:inventarios,id',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
        ]);

        $producto = Inventario::find($request->inventario_id);

        if ($producto->en_stock < $request->cantidad) {
            return response()->json(['error' => 'Stock insuficiente'], 400);
        }

        // Registrar la salida
        $salida = Salida::create([
            'inventario_id' => $request->inventario_id,
            'usuario_id' => Auth::id(),
            'cantidad' => $request->cantidad,
            'motivo' => $request->motivo,
        ]);

        // Restar del stock
        $producto->decrement('en_stock', $request->cantidad);

        return response()->json(['message' => 'Salida registrada con éxito', 'salida' => $salida], 200);
    }
}
