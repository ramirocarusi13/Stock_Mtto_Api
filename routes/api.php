<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;

use App\Http\Controllers\NotificacionesController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Models\Inventario;
use App\Http\Controllers\SalidaController;

/*
|--------------------------------------------------------------------------- 
| API Routes 
|--------------------------------------------------------------------------- 
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['cors', 'json.response']], function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

// Rutas protegidas

Route::group(['middleware' => ['auth:api', 'cors', 'json.response']], function () {
    // Inventario
    Route::get('/inventario', [InventarioController::class, 'index']);
    Route::post('/inventario', [InventarioController::class, 'store']); // Nueva ruta para crear un producto
    Route::put('inventario/aprobar-con-limites/{codigo}', [InventarioController::class, 'aprobarProductoConLimites']);
    Route::get('inventario/criticos', [InventarioController::class, 'productosCriticos']);
    Route::get('inventario/punto-de-pedido', [InventarioController::class, 'productosPuntoDePedido']);
    Route::get('/prestamos', [InventarioController::class, 'obtenerPrestamos']);
    Route::get('inventario/lista-minima', [InventarioController::class, 'listarCodigosYNombres']);

    Route::get('/inventario/{codigo}', [InventarioController::class, 'show']);
    Route::put('/inventario/{id}/prestar', [InventarioController::class, 'prestarProducto']);
    Route::put('/prestamo/{id}/devolver', [MovimientoController::class, 'devolverPrestamo']);


    Route::post('/inventario/{id}/estado', [InventarioController::class, 'actualizarEstado']);
    Route::put('/movimientos/aprobar-id/{id}', [MovimientoController::class, 'aprobarMovimientoPorId']);
    Route::put('/movimientos/rechazar-id/{id}', [MovimientoController::class, 'rechazarMovimientoPorId']);



    Route::put('/inventario/aprobar/{codigo}', [InventarioController::class, 'aprobarProducto']);
    Route::put('/inventario/{id}', [InventarioController::class, 'actualizarProducto']);

    Route::get('/prestamos', [MovimientoController::class, 'getPrestamos']);


    // Proveedores para el modal


    Route::get('/proveedores', [ProveedorController::class, 'getProveedores']);
    Route::post('/movimientos', [MovimientoController::class, 'store']);

    Route::post('/movimientos/{id}/editar', [MovimientoController::class, 'crearMovimientoDesdeEdicion']);
    Route::get('/movimientos', [MovimientoController::class, 'getAllMovimientos']);



    Route::get('/prestamos', [MovimientoController::class, 'getPrestamos']);
    Route::get('/productos', [InventarioController::class, 'getProductosPorCategoria']);





    Route::get('/categorias', [CategoriaController::class, 'index']);




    Route::get('/salidas', [SalidaController::class, 'index']);
    Route::post('/salidas', [SalidaController::class, 'registrarSalida']);
    Route::get('/egresos-con-productos', [MovimientoController::class, 'getEgresosConProductos']);

    Route::get('/reportes', [ReporteController::class, 'generarReporte']);
    Route::get('/reportes/productos-mas-movidos', [ReporteController::class, 'productosMasMovidos']);
    Route::get('/reportes/motivos-frecuentes', [ReporteController::class, 'motivosMasFrecuentes']);
    Route::get('/reportes/stock', [ReporteController::class, 'reporteStock']);
    Route::get('/movimientos/egresos', [MovimientoController::class, 'getEgresosFiltrados']);
    Route::get('/movimientos/egresos/{codigo_producto}', [MovimientoController::class, 'getEgresosPorProducto']);

    Route::get('/movimientos/{codigo_producto}', [MovimientoController::class, 'getMovimientosPorProducto']);
});
