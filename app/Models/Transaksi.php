<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $table = 'tbl_transaksi';
    protected $primaryKey = 'no_transaksi';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $dates = ['tanggal_transaksi', 'deleted_at'];
    
    protected $fillable = [
        'no_transaksi',
        'tanggal_transaksi',
        'jenis_transaksi',
        'no_referensi',
        'total_harga',
        'total_diskon',
        'grand_total',
        'keterangan'
    ];

    public function details()
    {
        return $this->hasMany(TransaksiDetails::class, 'no_transaksi', 'no_transaksi');
    }
}
