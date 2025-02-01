<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Toko extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_toko';

    protected $fillable = [
        'user_id',
        'nama_toko',
        'alamat',
        'telepon'
    ];

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi dengan Kategori
    public function kategori()
    {
        return $this->hasMany(Kategori::class);
    }

    // Relasi dengan Barang
    public function barang()
    {
        return $this->hasMany(Barang::class);
    }

    // Relasi dengan Transaksi
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }
}
