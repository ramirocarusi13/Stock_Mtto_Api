<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salida extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventario_id',
        'usuario_id',
        'cantidad',
        'motivo',
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
