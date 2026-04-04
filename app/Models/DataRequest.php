<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DataRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'kap_id',
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
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'expected_received' => 'date',
            'date_input' => 'datetime',
            'last_update' => 'datetime',
            'followup_sent_at' => 'datetime',
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

    protected static function booted(): void
    {
        static::saving(function (DataRequest $model) {
            $model->last_update = Carbon::now();
        });
    }
}
