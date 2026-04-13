<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $nama_kap
 * @property string $nama_pic
 * @property string $alamat
 */
class KapProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_kap',
        'nama_pic',
        'alamat',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'kap_id');
    }

    public function dataRequests()
    {
        return $this->hasMany(DataRequest::class, 'kap_id');
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'kap_id');
    }
}
