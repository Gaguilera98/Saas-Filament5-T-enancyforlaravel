<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant
{
    use HasDomains;

    protected $fillable = [
        'id',
        'clinic_name',
        'legal_name',
        'nit',
        'email',
        'phone',
        'city',
        'country',
        'timezone',
        'currency',
        'db_pool',
        'is_active',
        'onboarding_completed',
        'data',
    ];

    protected $casts = [
        'data'                 => 'array',
        'is_active'            => 'boolean',
        'onboarding_completed' => 'boolean',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'clinic_name',
            'legal_name',
            'nit',
            'email',
            'phone',
            'city',
            'country',
            'timezone',
            'currency',
            'db_pool',
            'is_active',
            'onboarding_completed',
        ];
    }
}