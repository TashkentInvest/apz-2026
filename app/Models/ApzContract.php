<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApzContract extends Model
{
    use HasFactory;

    protected $table = 'apz_contracts';

    protected $fillable = [
        'contract_id',
        'district',
        'mfy',
        'address',
        'build_volume',
        'coefficient',
        'zone',
        'permit',
        'apz_number',
        'council_decision',
        'expertise',
        'construction_issues',
        'object_type',
        'client_type',
        'investor_name',
        'inn',
        'phone',
        'investor_address',
        'contract_number',
        'contract_date',
        'contract_status',
        'contract_value',
        'payment_terms',
        'installments_count',
        'demand_letter_number',
        'demand_letter_date',
        'payment_schedule',
    ];

    protected $casts = [
        'contract_date'       => 'date',
        'contract_value'      => 'decimal:2',
        'build_volume'        => 'decimal:2',
        'installments_count'  => 'integer',
        'demand_letter_date'  => 'date',
        'payment_schedule'    => 'array',
    ];

    public function payments()
    {
        return $this->hasMany(ApzPayment::class, 'contract_id', 'contract_id');
    }

    public function isActive(): bool
    {
        return empty($this->contract_status) || !in_array($this->contract_status, ['Bekor qilingan']);
    }

    public function isCompleted(): bool
    {
        return $this->contract_status === 'Yakunlagan';
    }

    public function isCancelled(): bool
    {
        return $this->contract_status === 'Bekor qilingan';
    }
}
