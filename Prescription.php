<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'doctor_id',
        'prescription_number',
        'prescription_date',
        'instructions',
        'payment_status',
        'total_amount',
        'status',
        'dispensed_at',
        'dispensed_by'
    ];

    protected $casts = [
        'prescription_date' => 'date',
        'total_amount' => 'decimal:2',
        'dispensed_at' => 'datetime',
    ];

    // Relationships
    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'UNPAID');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'PAID');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeDispensed($query)
    {
        return $query->where('status', 'DISPENSED');
    }

    // Methods
    public function calculateTotal()
    {
        return $this->items->sum('subtotal');
    }

    public function isPaid()
    {
        return $this->payment_status === 'PAID';
    }

    public function isDispensed()
    {
        return $this->status === 'DISPENSED';
    }
}
