<?php

namespace App\Filament\Client\Resources\Domains\Pages;

use App\Filament\Client\Resources\Domains\DomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDomains extends ManageRecords
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
