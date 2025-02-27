<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_transaksi';
    protected $primaryKey = 'no_transaksi';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'no_transaksi',
        'toko_id',
        'user_id',
        'tanggal_transaksi',
        'jenis_transaksi',
        'no_referensi',
        'total_harga',
        'total_diskon',
        'grand_total',
        'keterangan'
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'total_harga' => 'decimal:2',
        'total_diskon' => 'decimal:2',
        'grand_total' => 'decimal:2'
    ];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detail(): HasMany
    {
        return $this->hasMany(TransaksiDetails::class, 'no_transaksi', 'no_transaksi');
    }
}
