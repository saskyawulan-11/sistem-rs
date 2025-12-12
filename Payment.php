<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'payment_number',
        'payment_method',
        'amount',
        'status',
        'reference_number',
        'notes',
        'paid_at',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    // Methods
    public function isPaid()
    {
        return $this->status === 'PAID';
    }

    public function isPending()
    {
        return $this->status === 'PENDING';
    }

    public function isFailed()
    {
        return $this->status === 'FAILED';
    }
}
