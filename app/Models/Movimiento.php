<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventario_id',  // Cambio de producto_id a inventario_id
        'usuario_id', 
        'gerente_id', 
        'tipo', 
        'cantidad', 
        'fecha_movimiento', 
        'motivo'
    ];
    

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'producto_id'); // Ajuste en el nombre de la relación
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_prestamo');
    }


    public function gerente()
    {
        return $this->belongsTo(User::class, 'gerente_id');
    }
}
