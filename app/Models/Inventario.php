<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventarios';

    protected $fillable = [
        'codigo',
        'descripcion',
        'proveedor_id',
        'categoria_id',
        'costo_proveedor_usd',
        'costo_por_unidad',
        'gastos_importacion_ars',
        'minimo',
        'punto_de_pedido',
        'maximo',
        'fecha',
        'sector',
        'estado',
        'linea',
        'maquina',
        'responsable_id',
        'turno',
        'costo_usd_consumido',
        'prestado',
        'fecha_prestado',
        'receptor_prestamo',
        'cantidad_prestada',
    ];

    protected $casts = [
        'prestado' => 'boolean',
    ];

    protected $appends = ['stock_real']; // Se agrega para calcular stock dinámico

    /**
     * Relación con el modelo User (Responsable).
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Relación con Categoría.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }



    /**
     * Relación con el modelo Proveedor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación con los movimientos del producto.
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'codigo_producto', 'codigo');
    }

    /**
     * Calcula el stock real sumando los movimientos aprobados.
     */
    public function getStockRealAttribute()
    {
        return $this->movimientos()
            ->where('estado', 'aprobado') // Solo contar movimientos aprobados
            ->sum('cantidad');
    }

    /**
     * Scope para filtrar inventarios con stock mínimo.
     */
    public function scopeMinimoStock($query)
    {
        return $query->whereColumn('en_stock', '<=', 'minimo');
    }

    /**
     * Scope para filtrar inventarios por proveedor.
     */
    public function scopePorProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Scope para filtrar inventarios por estado (aprobado, rechazado, pendiente).
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }
    public function producto()
    {
        return $this->belongsTo(Inventario::class, 'codigo_producto', 'codigo');
    }


    /**
     * Scope para filtrar inventarios que están prestados.
     */
    public function scopePrestados($query)
    {
        return $query->where('prestado', true);
    }
}
