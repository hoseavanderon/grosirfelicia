<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'user_id',
        'nomor_nota',
        'total_item',
        'sub_total',
        'metode_pembayaran',
    ];

    protected $attributes = [
        'metode_pembayaran' => 'cash',
    ];

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function detailTransactions()
    {
        return $this->hasMany(DetailTransaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
