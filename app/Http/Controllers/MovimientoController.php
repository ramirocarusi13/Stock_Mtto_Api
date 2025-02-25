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
            'codigo_producto' => 'required|string',
            'cantidad' => 'required|numeric',
            'motivo' => 'required|string|in:ingreso,egreso,prestamo,devolución',
            'estado' => 'required|string|in:pendiente,aprobado,rechazado',
            'receptor_prestamo' => 'nullable|string',
            'observacion_salida' => 'nullable|string',
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
                'motivo' => $request->motivo,
                'estado' => $request->estado,
                'receptor_prestamo' => $request->receptor_prestamo, 
                'observacion_salida' => $request->observacion_salida,
            ]);

            // Actualizar en_stock sumando solo los movimientos aprobados
            $producto->en_stock = $producto->movimientos()->where('estado', 'aprobado')->sum('cantidad');
            $producto->save();

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
    public function devolverPrestamo(Request $request, $id)
    {
        $movimiento = Movimiento::findOrFail($id);
    
        if ($movimiento->motivo !== 'prestamo') {
            return response()->json(['error' => 'Solo se pueden devolver préstamos'], 400);
        }
    
        if ($movimiento->estado === 'devuelto') {
            return response()->json(['error' => 'Este préstamo ya ha sido devuelto'], 400);
        }
    
        DB::beginTransaction();
        try {
            // Marcar el préstamo original como "devuelto" y cambiar el motivo a "devolución"
            $movimiento->estado = 'aprobado';
            $movimiento->motivo = 'devolucion'; // Cambiar el motivo para que no aparezca en la tabla de préstamos
            $movimiento->save();
    
            // Buscar el producto en inventario
            $producto = Inventario::where('codigo', $movimiento->codigo_producto)->firstOrFail();
            $cantidad_devuelta = abs($movimiento->cantidad);
    
            // Restaurar stock en inventario
            $producto->en_stock += $cantidad_devuelta;
            $producto->save();
    
            // Crear un nuevo movimiento de ingreso con los nombres de columnas correctos
            $nuevoMovimiento = Movimiento::create([
                'codigo_producto' => $movimiento->codigo_producto,
                'usuario_id' => $movimiento->usuario_id, // Mismo usuario que hizo el préstamo
                'user_aprobacion_id' => Auth::id(), // Usuario que aprueba la devolución
                'receptor_prestamo' => $movimiento->receptor_prestamo, // Se mantiene el receptor original
                'estado' => 'aprobado', // Estado ya aprobado
                'cantidad' => $cantidad_devuelta, // Positiva para reflejar el ingreso
                'fecha_movimiento' => now(), // Fecha de devolución
                'motivo' => 'ingreso', // Ingreso porque se devuelve stock
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Préstamo devuelto y stock restaurado',
                'nuevo_movimiento' => $nuevoMovimiento,
                'nuevo_stock' => $producto->en_stock
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al devolver el producto', 'detalle' => $e->getMessage()], 500);
        }
    }
    public function getEgresosConProductos()
{
    try {
        $egresos = Movimiento::where('motivo', 'egreso')
            ->where('estado', 'aprobado')
            ->with('producto.categoria') // Relación con el modelo Inventario
             // Relación con el modelo Inventario
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Egresos obtenidos con éxito',
            'egresos' => $egresos,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'No se pudieron obtener los egresos',
            'detalle' => $e->getMessage()
        ], 500);
    }
}


    



    public function getPrestamos()
{
    try {
        // Obtener los movimientos de préstamo y devolución
        $prestamos = Movimiento::whereIn('motivo', ['prestamo', 'devolucion'])
            ->with(['inventario', 'usuario'])
            ->orderByRaw("CASE WHEN motivo = 'prestamo' THEN 0 ELSE 1 END") // Poner los préstamos arriba
            ->orderBy('updated_at', 'desc') // Ordenar por fecha de actualización (devolución)
            ->paginate(10); // Limitar a 10 registros por página

        // Agregar la fecha de devolución basada en updated_at solo para los devueltos
        $prestamos->getCollection()->transform(function ($prestamo) {
            if ($prestamo->motivo === 'devolucion') {
                $prestamo->fecha_devolucion = $prestamo->updated_at;
            } else {
                $prestamo->fecha_devolucion = null;
            }
            return $prestamo;
        });

        return response()->json($prestamos, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al obtener los préstamos', 'detalle' => $e->getMessage()], 500);
    }
}

    public function aprobarMovimientosPorCodigo($codigo_producto)
    {
        DB::beginTransaction();
        try {
            // Buscar los movimientos pendientes de ese producto
            $movimientosPendientes = Movimiento::where('codigo_producto', $codigo_producto)
                ->where('estado', 'pendiente')
                ->get();

            if ($movimientosPendientes->isEmpty()) {
                return response()->json(['message' => 'No hay movimientos pendientes para este producto'], 400);
            }

            // Aprobar todos los movimientos pendientes del producto
            foreach ($movimientosPendientes as $movimiento) {
                $movimiento->estado = 'aprobado';
                $movimiento->save();
            }

            // Buscar el producto en inventarios
            $producto = Inventario::where('codigo', $codigo_producto)->firstOrFail();

            // Si el producto aún está pendiente en inventarios, lo aprobamos con el primer movimiento
            if ($producto->estado === 'pendiente') {
                $producto->estado = 'aprobado';
            }

            // Usar el método existente en `Inventario.php` para actualizar el stock
            $producto->en_stock = $producto->getStockRealAttribute();
            $producto->save();

            DB::commit();

            return response()->json([
                'message' => 'Movimientos aprobados y stock actualizado correctamente',
                'nuevo_stock' => $producto->en_stock
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudieron aprobar los movimientos', 'detalle' => $e->getMessage()], 500);
        }
    }
    public function rechazarMovimientosPorCodigo($codigo_producto)
    {
        DB::beginTransaction();
        try {
            // Buscar los movimientos pendientes de ese producto
            $movimientosPendientes = Movimiento::where('codigo_producto', $codigo_producto)
                ->where('estado', 'pendiente')
                ->get();

            if ($movimientosPendientes->isEmpty()) {
                return response()->json(['message' => 'No hay movimientos pendientes para este producto'], 400);
            }

            // Rechazar todos los movimientos pendientes del producto
            foreach ($movimientosPendientes as $movimiento) {
                $movimiento->estado = 'rechazado';
                $movimiento->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Movimientos rechazados correctamente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudieron rechazar los movimientos', 'detalle' => $e->getMessage()], 500);
        }
    }




    public function getAllMovimientos(Request $request)
    {
        try {
            // Permitir filtrar por estado si se envía en la query string (por ejemplo: ?estado=pendiente)
            $estado = $request->query('estado');

            $query = Movimiento::query();

            if ($estado) {
                $query->where('estado', $estado);
            }

            // Obtener los movimientos con información del usuario y producto relacionado
            $movimientos = $query->with(['usuario', 'producto'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Lista de movimientos obtenida con éxito',
                'movimientos' => $movimientos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudieron obtener los movimientos',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function getMovimientosPorProducto($codigo_producto)
    {
        try {
            // Obtener los movimientos del producto por su código
            $movimientos = Movimiento::where('codigo_producto', $codigo_producto)
                ->with('usuario')
                ->orderBy('created_at', 'desc')
                ->get();

            // Calcular la cantidad total de movimientos aprobados
            $cantidadTotal = $movimientos
                ->where('estado', 'aprobado')
                ->sum('cantidad');

            return response()->json([
                'codigo_producto' => $codigo_producto,
                'cantidad_total' => $cantidadTotal,
                'movimientos' => $movimientos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudieron obtener los movimientos',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
    public function crearMovimientoDesdeEdicion(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Buscar el movimiento original
            $movimientoOriginal = Movimiento::findOrFail($id);

            // Buscar el producto relacionado
            $producto = Inventario::where('codigo', $movimientoOriginal->codigo_producto)->firstOrFail();

            // Verificar si el movimiento original ya está aprobado (para evitar ediciones directas)
            if ($movimientoOriginal->estado === 'aprobado') {
                return response()->json(['error' => 'No se puede modificar un movimiento aprobado, se generará uno nuevo'], 400);
            }

            // Crear un nuevo movimiento con la cantidad nueva
            $nuevoMovimiento = Movimiento::create([
                'codigo_producto' => $movimientoOriginal->codigo_producto,
                'usuario_id' => Auth::id(), // Usuario que genera la edición
                'cantidad' => $request->cantidad,
                'estado' => 'pendiente', // Nuevo movimiento en estado pendiente
                'motivo' => $movimientoOriginal->motivo, // Mismo motivo del movimiento original
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Nuevo movimiento creado con éxito, pendiente de aprobación',
                'movimiento' => $nuevoMovimiento,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo generar el nuevo movimiento', 'detalle' => $e->getMessage()], 500);
        }
    }
}
