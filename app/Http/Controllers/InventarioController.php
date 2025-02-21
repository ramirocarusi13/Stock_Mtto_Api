<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Movimiento;
use App\Models\Prestamo;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    /**
     * Muestra una lista de todos los productos en el inventario.
     */
    public function index()
    {
        try {
            $productos = Inventario::with(['proveedor', 'categoria'])->get(); // Se incluye la relación con la categoría
            return response()->json(['data' => $productos], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los productos'], 500);
        }
    }
    public function getProductoCompleto($id)
{
    try {
        // Buscar el producto con sus relaciones (proveedor y categoría)
        $producto = Inventario::with(['proveedor', 'categoria'])->find($id);

        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        // Calcular el stock real basado en los movimientos aprobados
        $stock_real = Movimiento::where('codigo_producto', $producto->codigo)
            ->where('estado', 'aprobado')
            ->sum('cantidad');

        // Obtener salidas agrupadas por fecha
        $salidasPorFecha = Movimiento::where('codigo_producto', $producto->codigo)
            ->where('motivo', 'salida') // Solo contar salidas
            ->where('estado', 'aprobado')
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('SUM(cantidad) as cantidad'))
            ->groupBy('fecha')
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json([
            'producto' => [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'categoria' => $producto->categoria ? $producto->categoria->nombre : 'Sin categoría',
                'proveedor' => $producto->proveedor ? $producto->proveedor->nombre : 'Sin proveedor',
                'precio' => $producto->precio,
                'costo' => $producto->costo_proveedor_usd,
                'gastos_importacion' => $producto->gastos_importacion_ars,
                'stock_real' => $stock_real,
                'minimo' => $producto->minimo,
                'maximo' => $producto->maximo,
                'estado' => $producto->estado,
                'creado_en' => $producto->created_at->format('Y-m-d H:i:s'),
                'actualizado_en' => $producto->updated_at->format('Y-m-d H:i:s'),
                'salidas_por_fecha' => $salidasPorFecha // Agregamos historial de salidas
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al obtener el producto',
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

        // Verificar si la columna existe antes de actualizarla
        if (Schema::hasColumn('prestamos', 'fecha_devolucion')) {
            $prestamo->update([
                'devuelto' => true,
                'fecha_devolucion' => now(),
            ]);
        } else {
            $prestamo->update([
                'devuelto' => true,
            ]);
        }

        // Restaurar el stock del inventario
        $inventario = Inventario::find($prestamo->inventario_id);
        if ($inventario) {
            $inventario->increment('en_stock', $prestamo->cantidad_prestada);
        }

        return response()->json(['message' => 'Producto devuelto con éxito', 'prestamo' => $prestamo], 200);
    }



    // Obtener todos los préstamos
    public function obtenerPrestamos()
    {
        $prestamos = Prestamo::with(['user', 'inventario'])->get();
        return response()->json(['data' => $prestamos], 200);
    }
    public function getProductosPorCategoria(Request $request)
    {
        $categoriaId = $request->query('categoria_id');

        if (!$categoriaId) {
            return response()->json(['error' => 'Debe proporcionar una categoría'], 400);
        }

        $productos = Inventario::where('categoria_id', $categoriaId)->get();

        return response()->json($productos);
    }



    /**
     * Muestra los detalles de un producto específico.
     */
    public function show($codigo)
    {
        // Buscar el producto por código
        $producto = Inventario::where('codigo', $codigo)->firstOrFail();

        // Calcular el stock basado en movimientos
        $stockCalculado = Movimiento::where('codigo_producto', $codigo)
            ->where('estado', 'aprobado') // Solo movimientos aprobados afectan el stock
            ->sum('cantidad');

        return response()->json([
            'producto' => $producto,
            'stock_real' => $stockCalculado, // Stock calculado desde movimientos
        ]);
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
    public function getCategorias()
    {
        try {
            $categorias = Inventario::select('categoria')->distinct()->get();
            return response()->json($categorias, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener las categorías'], 500);
        }
    }


    public function aprobarProducto($codigo)
    {
        DB::beginTransaction();
        try {
            // Buscar el producto
            $producto = Inventario::where('codigo', $codigo)->firstOrFail();

            // Verificar si ya está aprobado
            if ($producto->estado === 'aprobado') {
                return response()->json(['message' => 'El producto ya está aprobado'], 400);
            }

            // Cambiar estado del producto a "aprobado"
            $producto->estado = 'aprobado';
            $producto->save();

            // Actualizar todos los movimientos de ese producto a "aprobado"
            Movimiento::where('codigo_producto', $codigo)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'aprobado']);

            DB::commit();

            return response()->json(['message' => 'Producto y movimientos aprobados con éxito']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo aprobar el producto', 'detalle' => $e->getMessage()], 500);
        }
    }




    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|unique:inventarios,codigo',
            'descripcion' => 'required|string',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'en_stock' => 'required|integer|min:0',
            'minimo' => 'nullable|integer|min:0',
            'maximo' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Crear el producto con estado "pendiente"
            $producto = Inventario::create([
                'codigo' => $request->codigo,
                'descripcion' => $request->descripcion,
                'proveedor_id' => $request->proveedor_id,
                'categoria_id' => $request->categoria_id,
                'en_stock' => $request->en_stock,
                'minimo' => $request->minimo,
                'maximo' => $request->maximo,
                'entradas' => $request->en_stock,
                'salidas' => 0,
                'estado' => 'pendiente', // Estado inicial en pendiente
            ]);

            // Registrar el movimiento con estado "pendiente"
            if ($request->en_stock > 0) {
                Movimiento::create([
                    'codigo_producto' => $request->codigo,
                    'usuario_id' => Auth::id(),
                    'cantidad' => $request->en_stock,
                    'estado' => 'pendiente', // Estado pendiente hasta que el gerente lo apruebe
                    'motivo' => 'ingreso',
                    'observacion_salida' => 'nullable|string',

                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Producto agregado y movimiento registrado como pendiente',
                'producto' => $producto,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No se pudo agregar el producto',
                'detalle' => $e->getMessage(),
            ], 500);
        }
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
                'costo_por_unidad' => 'nullable|numeric|min:0',
                
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
