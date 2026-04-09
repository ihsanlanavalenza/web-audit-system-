<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $kap_id
 * @property string $nama_client
 * @property string $nama_pic
 * @property string $no_contact
 * @property string|null $alamat
 * @property Carbon|null $tahun_audit
 */
class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'kap_id',
        'nama_client',
        'nama_pic',
        'no_contact',
        'alamat',
        'tahun_audit',
    ];

    protected function casts(): array
    {
        return [
            'tahun_audit' => 'date',
        ];
    }

    public function kapProfile()
    {
        return $this->belongsTo(KapProfile::class, 'kap_id');
    }

    public function dataRequests()
    {
        return $this->hasMany(DataRequest::class);
    }
}
