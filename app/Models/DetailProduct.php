<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailProduct extends Model
{
    use SoftDeletes;

    protected $table = 'detail_products';

    protected $fillable = [
        'product_id',
        'expired',
        'stok',
    ];

    protected $casts = [
        'expired' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    public function barangMasukLogs()
    {
        return $this->hasMany(BarangMasukLog::class, 'detail_product_id');
    }
}
