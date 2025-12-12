<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'medical_record_number',
        'name',
        'identity_number',
        'gender',
        'birth_date',
        'address',
        'phone',
        'bpjs_number',
        'insurance_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'medical_history',
        'status'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    // Accessors & Mutators to provide compatibility with views/controllers
    // that use 'date_of_birth' and 'male'/'female' values.
    public function getDateOfBirthAttribute()
    {
        return $this->birth_date;
    }

    public function setDateOfBirthAttribute($value)
    {
        $this->attributes['birth_date'] = $value;
    }

    public function getGenderAttribute($value)
    {
        // DB stores 'L' (Laki-laki) and 'P' (Perempuan)
        if ($value === 'L') return 'male';
        if ($value === 'P') return 'female';
        return $value;
    }

    public function setGenderAttribute($value)
    {
        if ($value === 'male') {
            $this->attributes['gender'] = 'L';
        } elseif ($value === 'female') {
            $this->attributes['gender'] = 'P';
        } else {
            $this->attributes['gender'] = $value;
        }
    }

    // Relationships
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Link to the user account (if patients have a user record)
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function prescriptions()
    {
        return $this->hasManyThrough(Prescription::class, Visit::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeBpjs($query)
    {
        return $query->where('insurance_type', 'BPJS');
    }

    public function scopeMandiri($query)
    {
        return $query->where('insurance_type', 'MANDIRI');
    }
}
