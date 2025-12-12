<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'unit',
        'price',
        'stock',
        'min_stock',
        'status',
        'expiry_date',
        'manufacturer',
        'batch_number'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'AVAILABLE');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= min_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    // Accessors
    public function getStockStatusAttribute()
    {
        if ($this->stock == 0) {
            return 'HABIS';
        } elseif ($this->stock <= $this->min_stock) {
            return 'HAMPIR HABIS';
        } else {
            return 'TERSEDIA';
        }
    }
}
