<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'detail_product_id',
        'harga_jual',
        'pcs',
    ];

    public function transaction(){
        return $this->belongsTo(Transaction::class);
    }

    public function detailProduct()
    {
        return $this->belongsTo(DetailProduct::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
