<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;

class CategoriaController extends Controller
{
    /**
     * Muestra todas las categorías.
     */
    public function index()
    {
        try {
            $categorias = Categoria::all(); // Obtiene todas las categorías
            return response()->json($categorias, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener las categorías'], 500);
        }
    }
}
