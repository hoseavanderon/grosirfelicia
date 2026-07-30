<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JejakProduk extends Model
{
    public const TYPE_KELUAR = 'keluar';

    public const TYPE_MASUK = 'masuk';

    public const TYPE_BATAL = 'batal';

    protected $table = 'jejak_produk';

    protected $fillable = [
        'user_id',
        'product_id',
        'movement_type',
        'qty',
        'transaction_id',
        'nomor_nota',
        'stock_after',
    ];

    protected $casts = [
        'qty' => 'integer',
        'stock_after' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
