<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangMasukLog extends Model
{
    protected $fillable = ['detail_product_id', 'jumlah_masuk', 'tanggal_masuk', 'keterangan'];

    public function detailProduct()
    {
        return $this->belongsTo(DetailProduct::class, 'detail_product_id');
    }
}
