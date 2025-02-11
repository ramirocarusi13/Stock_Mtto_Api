<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventario_id',
        'receptor_prestamo',
        'cantidad_prestada',
        'usuario_prestamo',
        'fecha_prestado',
        'devuelto',
        'fecha_devolucion'
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_prestamo', 'id');
    }
}
