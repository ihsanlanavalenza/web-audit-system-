<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $no
 * @property int|null $client_id
 * @property int|null $kap_id
 * @property int|null $pic_id
 * @property string|null $section_code
 * @property string|null $section_no
 * @property string|null $account_process
 * @property string|null $description
 * @property Carbon|null $request_date
 * @property Carbon|null $expected_received
 * @property array<int, mixed>|null $input_file
 * @property string|null $status
 * @property string|null $comment_client
 * @property string|null $comment_auditor
 * @property Carbon|null $date_input
 * @property Carbon|null $last_update
 * @property Carbon|null $followup_sent_at
 * @property Carbon|null $followup_7day_sent_at
 * @property Carbon|null $followup_15day_sent_at
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\KapProfile|null $kapProfile
 * @property-read \App\Models\User|null $pic
 * @property-read string $section
 */
class DataRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'kap_id',
        'pic_id',
        'no',
        'section_code',
        'section_no',
        'account_process',
        'description',
        'request_date',
        'expected_received',
        'input_file',
        'status',
        'comment_client',
        'comment_auditor',
        'date_input',
        'last_update',
        'followup_sent_at',
        'followup_7day_sent_at',
        'followup_15day_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'expected_received' => 'date',
            'date_input' => 'datetime',
            'last_update' => 'datetime',
            'followup_sent_at' => 'datetime',
            'followup_7day_sent_at' => 'datetime',
            'followup_15day_sent_at' => 'datetime',
            'input_file' => 'array',
        ];
    }

    /**
     * Accessor: Gabungkan section_code + section_no → "A1"
     */
    public function getSectionAttribute(): string
    {
        $code = $this->section_code ?? '';
        $no = $this->section_no ?? '';
        return trim($code . $no) ?: '-';
    }

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_ON_REVIEW = 'on_review';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_NOT_APPLICABLE = 'not_applicable';
    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_PARTIALLY_RECEIVED => '01 Partially Received',
        self::STATUS_ON_REVIEW => '02 On Review',
        self::STATUS_RECEIVED => '03 Received',
        self::STATUS_NOT_APPLICABLE => '04 Not Applicable',
        self::STATUS_PENDING => '05 Pending',
    ];

    public static function statusGradient(string $status): string
    {
        return match ($status) {
            self::STATUS_PARTIALLY_RECEIVED => 'from-amber-500 to-amber-300',
            self::STATUS_ON_REVIEW => 'from-blue-800 to-blue-500',
            self::STATUS_RECEIVED => 'from-emerald-600 to-emerald-300',
            self::STATUS_NOT_APPLICABLE => 'from-gray-500 to-gray-400',
            self::STATUS_PENDING => 'from-red-600 to-red-400',
            default => 'from-gray-500 to-gray-400',
        };
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function kapProfile()
    {
        return $this->belongsTo(KapProfile::class, 'kap_id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    protected static function booted(): void
    {
        static::saving(function (DataRequest $model) {
            $model->last_update = Carbon::now();
        });

        static::created(function (DataRequest $model) {
            $model->logActivity('created', 'Data Request created.');
        });

        static::updated(function (DataRequest $model) {
            $changes = $model->getChanges();
            unset($changes['last_update'], $changes['updated_at']);

            if (empty($changes)) return;

            $action = 'updated';
            $desc = 'Data Request updated.';

            if (array_key_exists('input_file', $changes)) {
                $action = 'file_uploaded';
                $desc = 'File uploaded.';
            } elseif (array_key_exists('status', $changes)) {
                $action = 'status_changed';
                $desc = "Status changed from '{$model->getOriginal('status')}' to '{$model->status}'.";
            }

            $model->logActivity($action, $desc, $model->getOriginal(), $model->getAttributes());
        });

        static::deleted(function (DataRequest $model) {
            $model->logActivity('deleted', 'Data Request deleted.');
        });
    }

    /**
     * Normalisasi input_file ke format versi baru.
     * Legacy format (array of paths) → [{version, files, uploaded_at, uploaded_by}]
     */
    public function normalizeInputFileVersions(): array
    {
        $files = $this->input_file ?? [];

        if (!is_array($files) || count($files) === 0) {
            return [];
        }

        // Sudah dalam format berversi
        if (isset($files[0]['version'])) {
            return $files;
        }

        // Legacy format — migrate ke format versi
        return [
            [
                'version'     => 1,
                'files'       => $files,
                'uploaded_at' => $this->date_input
                    ? $this->date_input->format('Y-m-d H:i:s')
                    : now()->format('Y-m-d H:i:s'),
                'uploaded_by' => 'Auto-migrated',
            ],
        ];
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject', 'model_type', 'model_id');
    }

    public function logActivity(string $action, string $description, ?array $old = null, ?array $new = null)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        ActivityLog::create([
            'user_id' => $userId,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'action' => $action,
            'description' => $description,
            'old_payload' => $old,
            'new_payload' => $new,
        ]);
    }
}
