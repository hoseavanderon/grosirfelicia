<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarangMasukLog extends Model
{
    use SoftDeletes;

    protected $fillable = ['detail_product_id', 'jumlah_masuk', 'tanggal_masuk', 'user_id'];

    public function detailProduct()
    {
        return $this->belongsTo(DetailProduct::class, 'detail_product_id')->withTrashed();
    }
}
