<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = [
        'pemesanan_id',
        'jumlah',
        'metode_pembayaran',
        'status',
        'tanggal',
    ];

    public function penyewaan()
    {
        return $this->belongsTo(Penyewaan::class, 'pemesanan_id');
    }
}
