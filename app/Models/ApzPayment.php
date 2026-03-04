<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApzPayment extends Model
{
    use HasFactory;

    protected $table = 'apz_payments';

    protected $fillable = [
        'payment_date',
        'contract_id',
        'inn',
        'debit_amount',
        'credit_amount',
        'payment_purpose',
        'flow',
        'month',
        'amount',
        'district',
        'type',
        'year',
        'company_name',
    ];

    protected $casts = [
        'payment_date'   => 'date',
        'debit_amount'   => 'decimal:2',
        'credit_amount'  => 'decimal:2',
        'amount'         => 'decimal:2',
        'year'           => 'integer',
        'contract_id'    => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(ApzContract::class, 'contract_id', 'contract_id');
    }
}
