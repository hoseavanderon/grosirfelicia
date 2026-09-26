<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Employee;

class Transaction extends Model
{
    use SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'customer_id',
        'user_id',
        'nomor_nota',
        'metode_pembayaran',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detailTransactions()
    {
        return $this->hasMany(DetailTransaction::class);
    }

    public function createdByEmployee()
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }

    public function deliveredByEmployee()
    {
        return $this->belongsTo(Employee::class, 'delivered_by_employee_id');
    }
}
