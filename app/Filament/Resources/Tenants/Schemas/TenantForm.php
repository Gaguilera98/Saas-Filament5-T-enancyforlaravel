<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Clínica')
                    ->columns(2)
                    ->schema([
                        TextInput::make('clinic_name')
                            ->label('Nombre de la Clínica')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                $slug = str($state)->slug()->toString();
                                $set('id', $slug);
                                $set('domain', $slug . '.localhost');
                            }),

                        TextInput::make('id')
                            ->label('Slug / ID')
                            ->helperText('Generado automáticamente desde el nombre')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),

                        TextInput::make('legal_name')
                            ->label('Razón Social')
                            ->maxLength(200),

                        TextInput::make('nit')
                            ->label('NIT')
                            ->maxLength(20),

                        TextInput::make('domain')
                            ->label('Dominio')
                            ->helperText('Generado automáticamente')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Contacto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel(),

                        TextInput::make('city')
                            ->label('Ciudad'),

                        Select::make('country')
                            ->label('País')
                            ->options([
                                'BOL' => 'Bolivia',
                                'ARG' => 'Argentina',
                                'COL' => 'Colombia',
                                'PER' => 'Perú',
                                'MEX' => 'México',
                                'CHL' => 'Chile',
                            ])
                            ->default('BOL')
                            ->required(),
                    ]),

                Section::make('Configuración')
                    ->columns(2)
                    ->schema([
                        Select::make('db_pool')
                            ->label('Pool de Base de Datos')
                            ->options([
                                'pool_shared_1' => 'Pool Compartido 1 (Estándar)',
                                'pool_shared_2' => 'Pool Compartido 2 (Estándar)',
                                'pool_vip'      => 'Pool VIP (Enterprise)',
                            ])
                            ->default('pool_shared_1')
                            ->required(),

                        Select::make('timezone')
                            ->label('Zona Horaria')
                            ->options([
                                'America/La_Paz'                 => 'Bolivia (La Paz)',
                                'America/Lima'                   => 'Perú (Lima)',
                                'America/Bogota'                 => 'Colombia (Bogotá)',
                                'America/Santiago'               => 'Chile (Santiago)',
                                'America/Argentina/Buenos_Aires' => 'Argentina (Buenos Aires)',
                                'America/Mexico_City'            => 'México (CDMX)',
                            ])
                            ->default('America/La_Paz')
                            ->required(),

                        Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'BOB' => 'BOB - Boliviano',
                                'USD' => 'USD - Dólar',
                                'PEN' => 'PEN - Sol Peruano',
                                'COP' => 'COP - Peso Colombiano',
                                'ARS' => 'ARS - Peso Argentino',
                                'CLP' => 'CLP - Peso Chileno',
                            ])
                            ->default('BOB')
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),

                Section::make('Usuario Administrador')
                    ->columns(2)
                    ->schema([
                        TextInput::make('admin_name')
                            ->label('Nombre del Admin')
                            ->required(),

                        TextInput::make('admin_email')
                            ->label('Email del Admin')
                            ->email()
                            ->required(),

                        TextInput::make('admin_password')
                            ->label('Contraseña')
                            ->password()
                            ->required()
                            ->minLength(8),
                    ]),
            ]);
    }
}