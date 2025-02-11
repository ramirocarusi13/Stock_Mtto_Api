<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventarios';

    protected $fillable = [
        'codigo',
        'descripcion',
        'proveedor_id',
        'categoria_id', // ✅ Asegurar que se pueda guardar
        'costo_proveedor_usd',
        'gastos_importacion_ars',
        'en_stock',
        'minimo',
        'punto_de_pedido',
        'maximo',
        'salidas',
        'fecha',
        'sector',
        'linea',
        'maquina',
        'responsable_id',
        'turno',
        'costo_usd_consumido',
        'estado',
        'prestado',
        'fecha_prestado',
        'receptor_prestamo',
        'cantidad_prestada'
    ];
    

    protected $casts = [
        'prestado' => 'boolean',
    ];


    /**
     * Relación con el modelo User (Responsable).
     * Un inventario pertenece a un usuario como responsable.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relación con el modelo Proveedor.
     * Un inventario pertenece a un proveedor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Scope para filtrar inventarios con stock mínimo.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMinimoStock($query)
    {
        return $query->whereColumn('en_stock', '<=', 'minimo');
    }

    /**
     * Scope para filtrar inventarios por proveedor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $proveedorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Scope para filtrar inventarios por estado (aprobado, rechazado, pendiente).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $estado
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar inventarios que están prestados.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrestados($query)
    {
        return $query->where('prestado', true);
    }
}
