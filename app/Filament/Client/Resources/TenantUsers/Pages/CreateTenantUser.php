<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\TenantUsers\Pages;

use App\Filament\Client\Resources\TenantUsers\TenantUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantUser extends CreateRecord
{
    protected static string $resource = TenantUserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = tenant()->id;

        return $data;
    }
}
