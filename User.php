<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is dokter
     */
    public function isDokter(): bool
    {
        return $this->role === 'dokter';
    }

    /**
     * Check if user is perawat
     */
    public function isPerawat(): bool
    {
        return $this->role === 'perawat';
    }

    /**
     * Check if user is pasien
     */
    public function isPasien(): bool
    {
        return $this->role === 'pasien';
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    /**
     * Get user's role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'dokter' => 'Dokter',
            'perawat' => 'Perawat',
            'pasien' => 'Pasien',
            default => 'Unknown'
        };
    }

    /**
     * Get user's role badge color
     */
    public function getRoleBadgeColor(): string
    {
        return match($this->role) {
            'admin' => 'danger',
            'dokter' => 'primary',
            'perawat' => 'success',
            'pasien' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Infer role from email domain suffix.
     */
    public static function roleForEmail(string $email): string
    {
        $email = strtolower($email);
        $domain = substr(strrchr($email, '@') ?: '', 1);

        return match ($domain) {
            'admin.com' => 'admin',
            'dokter.com' => 'dokter',
            'perawat.com' => 'perawat',
            default => 'pasien',
        };
    }

    /**
     * Check if the user's email domain matches the given role policy.
     */
    public function hasEmailDomainForRole(string $role): bool
    {
        $email = strtolower($this->email ?? '');
        $domain = substr(strrchr($email, '@') ?: '', 1);

        return match ($role) {
            'admin' => $domain === 'admin.com',
            'dokter' => $domain === 'dokter.com',
            'perawat' => $domain === 'perawat.com',
            // Pasien may use any domain
            'pasien' => true,
            default => false,
        };
    }
}
