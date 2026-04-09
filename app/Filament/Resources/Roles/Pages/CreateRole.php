<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use BezhanSalleh\FilamentShield\Support\Utils;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = $data['guard_name'] ?? 'web';
        return $data;
    }

    protected function afterCreate(): void
    {
        $permissionModels = collect($this->data['permissions'] ?? [])
            ->flatten()
            ->unique();
            
        $this->record->syncPermissions($permissionModels);
    }
}
