<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantProfile extends Model
{
    protected $connection = 'pgsql'; // siempre en la DB central

    protected $fillable = [
        'tenant_id',
        'clinic_name',
        'legal_name',
        'nit',
        'email',
        'phone',
        'country',
        'city',
        'timezone',
        'currency',
        'db_pool',
        'is_active',
        'onboarding_completed',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'onboarding_completed'  => 'boolean',
    ];

    // Relación con el tenant de tenancy
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}