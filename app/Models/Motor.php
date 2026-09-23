<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motor extends Model
{
    protected $fillable = [
        'pemilik_id',
        'merek',
        'tipe_cc',
        'no_plat',
        'status',
        'foto',
        'documen_kepemilikan'
    ];
    public function pemilik()
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }
    public function tarif()
    {
        return $this->hasOne(TarifRental::class);
    }
    public function penyewaans() {
        return $this->hasMany(Penyewaan::class, 'motor_id');
    }
}
