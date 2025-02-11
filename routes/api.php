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
    Route::get('/inventario/{id}', [InventarioController::class, 'show']);
    Route::post('/inventario', [InventarioController::class, 'store']); // Nueva ruta para crear un producto
    Route::put('/inventario/{id}/prestar', [InventarioController::class, 'prestarProducto']);
    Route::put('/prestamo/{id}/devolver', [InventarioController::class, 'devolverProducto']);
    Route::get('/prestamos', [InventarioController::class, 'obtenerPrestamos']);

    Route::post('/inventario/{id}/estado', [InventarioController::class, 'actualizarEstado']);


    Route::put('/inventario/{id}', [InventarioController::class, 'actualizarProducto']);



    // Proveedores para el modal
    Route::get('/proveedores', [ProveedorController::class, 'getProveedores']);
    Route::post('/movimientos', [MovimientoController::class, 'store']);


    

    Route::get('/categorias', [CategoriaController::class, 'index']);
    



    Route::get('/salidas', [SalidaController::class, 'index']);
    Route::post('/salidas', [SalidaController::class, 'registrarSalida']);
});
