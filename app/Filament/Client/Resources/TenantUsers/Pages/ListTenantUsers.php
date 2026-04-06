<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\TenantUsers\Pages;

use App\Filament\Client\Resources\TenantUsers\TenantUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantUsers extends ListRecords
{
    protected static string $resource = TenantUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
