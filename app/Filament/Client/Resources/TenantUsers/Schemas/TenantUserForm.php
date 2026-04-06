<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\TenantUsers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class TenantUserForm
{
    public static function configure(Schema $schema): Schema
    {
        $teamKey = config('permission.column_names.team_foreign_key');
        $rolesTable = config('permission.table_names.roles');

        return $schema
            ->components([
                Section::make('Usuario')
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
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule) => $rule->where($teamKey, tenant()->id),
                            )
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),

                        Select::make('role')
                            ->label('Perfil (aplicación)')
                            ->options([
                                'admin' => 'Administrador',
                                'doctor' => 'Médico',
                                'nurse' => 'Enfermería',
                                'receptionist' => 'Recepción',
                                'billing' => 'Facturación',
                            ])
                            ->required()
                            ->default('receptionist'),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),

                        Select::make('roles')
                            ->label('Roles (permisos)')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                // Misma lógica que Spatie en roles(): equipo actual o NULL (roles creados sin team).
                                modifyQueryUsing: function (Builder $query) use ($rolesTable, $teamKey): Builder {
                                    $tid = tenant()->id;

                                    return $query
                                        ->where($rolesTable.'.guard_name', 'tenant')
                                        ->where(function (Builder $q) use ($rolesTable, $teamKey, $tid): void {
                                            $q->where($rolesTable.'.'.$teamKey, $tid)
                                                ->orWhereNull($rolesTable.'.'.$teamKey);
                                        });
                                },
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Solo roles con guard «tenant». Si creaste uno y no aparece, edita el rol y comprueba el guard y el equipo (tenant).')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
