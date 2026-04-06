<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Services\TenantPoolAdminProvisioner;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterCreate(): void
    {
        $tenant = $this->getRecord();

        $tenant->domains()->create([
            'domain' => $this->data['domain'],
        ]);

        try {
            app(TenantPoolAdminProvisioner::class)->provision(
                $tenant,
                $this->data['admin_name'],
                $this->data['admin_email'],
                $this->data['admin_password'],
            );
        } catch (\Throwable $e) {
            Log::error('TenantPoolAdminProvisioner failed', [
                'tenant_id' => $tenant->id,
                'exception' => $e,
            ]);

            Notification::make()
                ->danger()
                ->title('Tenant creado, pero falló el usuario admin en el pool')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
