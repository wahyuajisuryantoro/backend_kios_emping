<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TokoSatuan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_toko_satuan';

    protected $fillable = [
        'id_toko',
        'id_master_satuan',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relasi ke toko
    public function toko()
    {
        return $this->belongsTo(Toko::class, 'id_toko');
    }

}
