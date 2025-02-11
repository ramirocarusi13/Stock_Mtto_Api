<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'descripcion'];

    /**
     * Relación con Inventario: Una categoría puede tener muchos productos en el inventario.
     */
    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class, 'categoria_id');
    }
}
