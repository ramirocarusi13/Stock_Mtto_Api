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
        'motivo',
        'receptor_prestamo',
        'observacion_salida'
    ];

    protected $appends = ['usuario_nombre']; // Agregar el nombre del usuario en la respuesta

    // Relación con el producto en inventarios
    public function producto()
    {
        return $this->belongsTo(Inventario::class, 'codigo_producto', 'codigo');
    }
    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'codigo_producto', 'codigo');
    }


    // Relación con el usuario que creó el movimiento
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Obtener el nombre del usuario que creó el movimiento
    public function getUsuarioNombreAttribute()
    {
        return $this->usuario ? $this->usuario->name : 'Desconocido';
    }
}
