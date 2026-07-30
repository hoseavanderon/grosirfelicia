<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    public const STOCK_CHECK_VERIFIED = 1;

    public const STOCK_CHECK_REQUIRED = 2;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'user_id',
        'brand_id',
        'nama_produk',
        'harga_jual',
        'sort_order',
        'stock_check_status',
    ];

    protected $casts = [
        'stock_check_status' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function detailProducts()
    {
        return $this->hasMany(
            DetailProduct::class,
            'product_id'
        );
    }

    public function barangMasukLogs()
    {
        return $this->hasManyThrough(
            BarangMasukLog::class,
            DetailProduct::class,
            'product_id',
            'detail_product_id',
            'id',
            'id',
        );
    }

    public function needsStockCheck(): bool
    {
        return (int) $this->stock_check_status === self::STOCK_CHECK_REQUIRED;
    }
}
