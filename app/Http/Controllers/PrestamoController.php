<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Inventario;
use Carbon\Carbon;

class PrestamoController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Prestamo::with('inventario')->get()]);
    }

    public function prestarProducto(Request $request, $id)
    {
        $producto = Inventario::findOrFail($id);
        if ($producto->en_stock < $request->cantidad_prestada) {
            return response()->json(['error' => 'Stock insuficiente'], 400);
        }

        $prestamo = Prestamo::create([
            'inventario_id' => $id,
            'receptor_prestamo' => $request->receptor_prestamo,
            'cantidad_prestada' => $request->cantidad_prestada,
            'usuario_prestamo' => $request->usuario_prestamo,
            'fecha_prestado' => Carbon::now(),
            'devuelto' => false
        ]);

        $producto->decrement('en_stock', $request->cantidad_prestada);
        $producto->refresh();
        $producto->actualizarFechaPuntoPedido();
        return response()->json(['message' => 'Producto prestado', 'data' => $prestamo]);
    }

    public function devolverProducto($id)
    {
        $prestamo = Prestamo::findOrFail($id);
        $producto = Inventario::findOrFail($prestamo->inventario_id);
        
        $producto->increment('en_stock', $prestamo->cantidad_prestada);
        $producto->refresh();
        $producto->actualizarFechaPuntoPedido();
        $prestamo->update(['devuelto' => true]);
        
        return response()->json(['message' => 'Producto devuelto']);
    }

    public function estadisticas()
    {
        $prestamosMes = Prestamo::whereMonth('fecha_prestado', Carbon::now()->month)->count();
        $usuarioMasPresto = Prestamo::select('usuario_prestamo')
            ->groupBy('usuario_prestamo')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(1)
            ->pluck('usuario_prestamo')
            ->first();

        return response()->json([
            'prestamosPorMes' => $prestamosMes,
            'usuarioMasPresto' => $usuarioMasPresto ?? 'N/A'
        ]);
    }
}
