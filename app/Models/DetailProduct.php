<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\DetailTransaction;

class DetailProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'expired',
        'stok'
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function detailTransactions()
    {
        return $this->hasMany(DetailTransaction::class, 'detail_product_id');
    }
}
