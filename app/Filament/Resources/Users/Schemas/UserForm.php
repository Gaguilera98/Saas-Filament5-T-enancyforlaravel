<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $teamKey = config('permission.column_names.team_foreign_key');
        $rolesTable = config('permission.table_names.roles');
        $centralTeamId = config('filament-shield.central_team_id');

        return $schema
            ->components([
                Section::make('Usuario del sistema central')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('roles')
                            ->label('Roles (permisos)')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query) use ($rolesTable, $teamKey, $centralTeamId): Builder {
                                    return $query
                                        ->where($rolesTable.'.guard_name', 'web')
                                        ->where(function (Builder $q) use ($rolesTable, $teamKey, $centralTeamId): void {
                                            $q->where($rolesTable.'.'.$teamKey, $centralTeamId)
                                                ->orWhereNull($rolesTable.'.'.$teamKey);
                                        });
                                },
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Solo roles con guard «web» y equipo central o sin equipo. Si creaste un rol y no sale, revisa su guard en Roles (debe ser web).')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
