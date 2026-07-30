<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'detail_transactions';

    protected $fillable = [
        'transaction_id',
        'detail_product_id',
        'harga_jual',
        'pcs',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function detailProduct()
    {
        return $this->belongsTo(DetailProduct::class);
    }
}
