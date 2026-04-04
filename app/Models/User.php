<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'kap_id',
        'invitation_token',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAuditor(): bool
    {
        return $this->role === 'auditor';
    }

    public function isAuditi(): bool
    {
        return $this->role === 'auditi';
    }

    public function kapProfile()
    {
        return $this->hasOne(KapProfile::class);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }
}
