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
        'devuelto'
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }
    public function user(){
        return $this->hasOne(User::class,'id','usuario_prestamo');
    }
}
