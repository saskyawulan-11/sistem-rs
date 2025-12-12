<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'visit_number',
        'queue_number',
        'visit_date',
        'registration_time',
        'examination_time',
        'completion_time',
        'status',
        'complaints',
        'diagnosis',
        'treatment_plan',
        'payment_status',
        'total_cost'
    ];

    protected $casts = [
        'visit_date' => 'date',
        'registration_time' => 'datetime:H:i',
        'examination_time' => 'datetime:H:i',
        'completion_time' => 'datetime:H:i',
        'total_cost' => 'decimal:2',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->where('visit_date', today());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'WAITING');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }
}
