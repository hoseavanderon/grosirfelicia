<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarangMasukLog extends Model
{
    use SoftDeletes;

    protected $table = 'barang_masuk_logs';

    protected $fillable = [
        'detail_product_id',
        'jumlah_masuk',
        'nomor_nota',
        'tanggal_masuk',
        'user_id',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detailProduct(): BelongsTo
    {
        return $this->belongsTo(DetailProduct::class);
    }
}
