<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\TenantUsers;

use App\Filament\Client\Resources\TenantUsers\Pages\CreateTenantUser;
use App\Filament\Client\Resources\TenantUsers\Pages\EditTenantUser;
use App\Filament\Client\Resources\TenantUsers\Pages\ListTenantUsers;
use App\Filament\Client\Resources\TenantUsers\Schemas\TenantUserForm;
use App\Filament\Client\Resources\TenantUsers\Tables\TenantUsersTable;
use App\Models\TenantUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TenantUserResource extends Resource
{
    protected static ?string $model = TenantUser::class;

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Equipo';

    public static function form(Schema $schema): Schema
    {
        return TenantUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantUsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()->id);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenantUsers::route('/'),
            'create' => CreateTenantUser::route('/create'),
            'edit' => EditTenantUser::route('/{record}/edit'),
        ];
    }
}
