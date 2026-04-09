<?php

namespace App\Models;

use App\Mail\InvitationAcceptedMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * @property int $id
 * @property int|null $kap_id
 * @property int|null $client_id
 * @property string $email
 * @property string $role
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property-read \App\Models\KapProfile|null $kapProfile
 * @property-read \App\Models\Client|null $client
 */
class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'kap_id',
        'client_id',
        'email',
        'role',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public static function latestPendingForEmail(string $email): ?self
    {
        $email = strtolower(trim($email));

        return static::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->pending()
            ->active()
            ->latest('id')
            ->first();
    }

    public static function acceptForUser(User $user, ?self $invitation = null): ?self
    {
        $invitation ??= static::latestPendingForEmail($user->email);

        if (!$invitation) {
            return null;
        }

        if (strcasecmp($invitation->email, $user->email) !== 0) {
            return null;
        }

        $oldRole = $user->role;
        $oldKapId = $user->kap_id;

        DB::transaction(function () use ($user, $invitation) {
            $targetRole = in_array($invitation->role, ['auditor', 'auditi'], true)
                ? $invitation->role
                : $user->role;

            $user->forceFill([
                'role' => $targetRole,
                'kap_id' => $invitation->kap_id,
                'invitation_token' => $invitation->token,
            ])->save();

            $invitation->forceFill([
                'accepted_at' => now(),
            ])->save();
        });

        $invitation->refresh();
        $invitation->loadMissing(['kapProfile.user', 'client']);
        $user->refresh();

        $actorId = Auth::id() ?: $user->id;

        ActivityLog::create([
            'user_id' => $actorId,
            'model_type' => self::class,
            'model_id' => $invitation->id,
            'action' => 'invitation_accepted',
            'description' => 'Invitation accepted and access activated.',
            'old_payload' => [
                'accepted_at' => null,
            ],
            'new_payload' => [
                'accepted_at' => $invitation->accepted_at?->toDateTimeString(),
                'accepted_user_id' => $user->id,
                'accepted_user_email' => $user->email,
                'role' => $invitation->role,
                'kap_id' => $invitation->kap_id,
                'client_id' => $invitation->client_id,
            ],
        ]);

        if ($oldRole !== $user->role || $oldKapId !== $user->kap_id) {
            ActivityLog::create([
                'user_id' => $actorId,
                'model_type' => User::class,
                'model_id' => $user->id,
                'action' => 'role_changed_by_invitation',
                'description' => 'User role and scope updated from invitation acceptance.',
                'old_payload' => [
                    'role' => $oldRole,
                    'kap_id' => $oldKapId,
                ],
                'new_payload' => [
                    'role' => $user->role,
                    'kap_id' => $user->kap_id,
                    'invitation_id' => $invitation->id,
                ],
            ]);
        }

        static::sendAcceptanceEmails($invitation, $user);

        return $invitation;
    }

    private static function sendAcceptanceEmails(self $invitation, User $acceptedUser): void
    {
        $inviter = $invitation->kapProfile?->user;
        $superAdmins = User::query()
            ->where('role', 'super_admin')
            ->get(['id', 'email']);

        $recipientMap = [];

        if (!empty($acceptedUser->email)) {
            $recipientMap[strtolower($acceptedUser->email)] = 'accepted_user';
        }

        if ($inviter && !empty($inviter->email)) {
            $key = strtolower($inviter->email);
            if (!isset($recipientMap[$key])) {
                $recipientMap[$key] = 'inviter';
            }
        }

        foreach ($superAdmins as $superAdmin) {
            if (empty($superAdmin->email)) {
                continue;
            }

            $key = strtolower($superAdmin->email);
            if (!isset($recipientMap[$key])) {
                $recipientMap[$key] = 'super_admin';
            }
        }

        foreach ($recipientMap as $email => $recipientType) {
            try {
                Mail::to($email)->send(new InvitationAcceptedMail(
                    invitation: $invitation,
                    acceptedUser: $acceptedUser,
                    recipientType: $recipientType,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function kapProfile()
    {
        return $this->belongsTo(KapProfile::class, 'kap_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function isPending(): bool
    {
        return is_null($this->accepted_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }
}
