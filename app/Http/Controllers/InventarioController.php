<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Prestamo;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventarioController extends Controller
{
    /**
     * Muestra una lista de todos los productos en el inventario.
     */
    public function index()
    {
        try {
            $productos = Inventario::with("proveedor")->get();


            return response()->json([
                'success' => true,
                'data' => $productos,
                'message' => 'Productos obtenidos correctamente.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los productos del inventario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Registrar un préstamo
    public function prestarProducto(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'receptor_prestamo' => 'required|string|max:255',
            'cantidad_prestada' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $producto = Inventario::find($id);
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        if ($producto->estado !== 'aprobado') {
            return response()->json(['error' => 'El producto no está aprobado para préstamo'], 400);
        }

        if ($producto->en_stock < $request->cantidad_prestada) {
            return response()->json(['error' => 'Stock insuficiente para prestar'], 400);
        }

        // Registrar el préstamo
        $prestamo = Prestamo::create([
            'inventario_id' => $id,
            'receptor_prestamo' => $request->receptor_prestamo,
            'cantidad_prestada' => $request->cantidad_prestada,
            'fecha_prestado' => now()->toDateString(),
            'usuario_prestamo' => auth()->user()->id
        ]);

        // Reducir el stock del inventario
        $producto->decrement('en_stock', $request->cantidad_prestada);

        return response()->json(['message' => 'Producto prestado con éxito', 'prestamo' => $prestamo], 200);
    }

    // Registrar la devolución de un préstamo
    public function devolverProducto($id)
    {
        $prestamo = Prestamo::find($id);
        if (!$prestamo || $prestamo->devuelto) {
            return response()->json(['error' => 'Préstamo no encontrado o ya devuelto'], 404);
        }

        // Actualizar el préstamo como devuelto
        $prestamo->update([
            'devuelto' => true,
            'fecha_devolucion' => now()->toDateString(),
        ]);

        // Restaurar el stock del inventario
        $inventario = Inventario::find($prestamo->inventario_id);
        $inventario->increment('en_stock', $prestamo->cantidad_prestada);

        return response()->json(['message' => 'Producto devuelto con éxito', 'prestamo' => $prestamo], 200);
    }

    // Obtener todos los préstamos
    public function obtenerPrestamos()
    {
        $prestamos = Prestamo::with(['user','inventario'])->get();
        return response()->json(['data' => $prestamos], 200);
    }


    /**
     * Muestra los detalles de un producto específico.
     */
    public function show($id)
    {
        $producto = Inventario::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $producto,
            'message' => 'Producto obtenido correctamente.'
        ], 200);
    }
    public function actualizarEstado(Request $request, $id)
    {
        Log::info('Datos recibidos en la actualización de estado:', $request->all());

        try {
            // Validación
            $validatedData = $request->validate([
                'estado' => 'required|in:aprobado,rechazado',
            ]);

            // Buscar el producto
            $producto = Inventario::find($id);
            if (!$producto) {
                return response()->json(['message' => 'Producto no encontrado'], 404);
            }

            // Actualizar estado
            $producto->estado = $validatedData['estado'];
            $producto->save();

            return response()->json([
                'message' => 'Estado actualizado con éxito',
                'producto' => $producto
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }


    public function aprobarProducto($id)
    {
        try {
            $userLogueado = Auth::user();

            // Verifica que el usuario sea gerente
            if ($userLogueado->rol != 'gerente') {
                return response()->json(['error' => true, 'message' => 'No tiene permisos para aprobar este producto'], 403);
            }

            // Buscar el producto en el inventario
            $producto = Inventario::findOrFail($id);

            // Verifica que el producto esté en estado "pendiente"
            if ($producto->estado !== 'pendiente') {
                return response()->json(['error' => 'Solo se pueden aprobar productos en estado pendiente'], 400);
            }

            // Cambia el estado a "aprobado"
            $producto->estado = 'aprobado';
            $producto->save();

            return response()->json(['message' => 'Producto aprobado con éxito', 'producto' => $producto], 200);
        } catch (\Exception $e) {
            Log::error('Error al aprobar el producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al aprobar el producto'], 500);
        }
    }



    public function store(Request $request)
    {
        // Validar los datos
        $validated = $request->validate([
            'codigo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'proveedor_id' => 'required|exists:proveedores,id', // Validación para el proveedor
            'en_stock' => 'required|integer|min:0',
            'minimo' => 'required|integer|min:0',
            'maximo' => 'required|integer|min:0',
        ]);

        // Crear el inventario con la relación al proveedor
        $producto = Inventario::create($validated);

        return response()->json([
            'message' => 'Producto registrado correctamente',
            'data' => $producto,
        ]);
    }
    public function actualizarProducto(Request $request, $id)
    {
        // Log de depuración
        Log::info('Datos recibidos en la actualización de producto:', $request->all());

        try {
            $validatedData = $request->validate([
                'codigo' => 'required|string|max:255',
                'descripcion' => 'required|string|max:255',
                'proveedor_id' => 'required|integer|exists:proveedores,id',
                'en_stock' => 'required|integer|min:0',
                'minimo' => 'required|integer|min:0',
                'punto_de_pedido' => 'nullable|integer|min:0',
                'maximo' => 'required|integer|min:0',
                'costo_proveedor_usd' => 'nullable|numeric|min:0',
                'gastos_importacion_ars' => 'nullable|numeric|min:0',
            ]);

            $producto = Inventario::find($id);
            if (!$producto) {
                return response()->json(['message' => 'Producto no encontrado'], 404);
            }

            $producto->update($validatedData);

            return response()->json([
                'message' => 'Producto actualizado con éxito',
                'producto' => $producto
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }
}
