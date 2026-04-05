<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterCreate(): void
    {
        $tenant = $this->getRecord();

        // 1. Crear el dominio
        $tenant->domains()->create([
            'domain' => $this->data['domain'],
        ]);

        // 2. Crear usuario admin en el pool
        DB::connection($tenant->db_pool)->table('users')->insert([
            'tenant_id'  => $tenant->id,
            'name'       => $this->data['admin_name'],
            'email'      => $this->data['admin_email'],
            'password'   => bcrypt($this->data['admin_password']),
            'role'       => 'admin',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}