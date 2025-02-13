<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_producto',
        'usuario_id',
        'user_aprobacion_id',
        'estado',
        'cantidad',
        'fecha_movimiento',
        'motivo'
    ];

    protected $appends = ['usuario_nombre', 'aprobador_nombre'];

    // Relación con el producto en inventarios
    public function producto()
    {
        return $this->belongsTo(Inventario::class, 'codigo_producto', 'codigo');
    }

    // Relación con el usuario que creó el movimiento
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Relación con el usuario que aprobó o rechazó el movimiento
    public function aprobador()
    {
        return $this->belongsTo(User::class, 'user_aprobacion_id');
    }

    // Obtener el nombre del usuario que creó el movimiento
    public function getUsuarioNombreAttribute()
    {
        return $this->usuario ? $this->usuario->name : 'Desconocido';
    }

    // Obtener el nombre del usuario que aprobó o rechazó el movimiento
    public function getAprobadorNombreAttribute()
    {
        return $this->aprobador ? $this->aprobador->name : 'No aprobado';
    }
}
