<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    protected $table = 'brands';

    public function products()
    {
        return $this->hasMany(
            Product::class,
            'brand_id'
        );
    }
}
