<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $role
 * @property int|null $kap_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Notifications\DatabaseNotification> $unreadNotifications
 */
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

    public function assignedKapProfile()
    {
        return $this->belongsTo(KapProfile::class, 'kap_id');
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_user_access')
            ->withTimestamps();
    }

    public function hasClientAccess(int $clientId): bool
    {
        return $this->clients()->where('clients.id', $clientId)->exists();
    }

    public function resolveKapId(): ?int
    {
        if ($this->kap_id) {
            return (int) $this->kap_id;
        }

        return $this->kapProfile?->id;
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }
}
