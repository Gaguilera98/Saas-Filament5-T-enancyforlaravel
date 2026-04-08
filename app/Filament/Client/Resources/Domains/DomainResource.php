<?php

namespace App\Filament\Client\Resources\Domains;

use App\Filament\Client\Resources\Domains\Pages\ManageDomains;
use App\Models\Domain;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string|\Filament\Support\Icons\Icon|\Illuminate\Contracts\Support\Htmlable|null $navigationIcon = 'heroicon-o-globe-alt';
    
    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\TextInput::make('domain')
                    ->label('Dominio')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('tenant_id')
                    ->label('Clínica')
                    ->relationship('tenant', 'clinic_name')
                    ->required()
                    ->searchable(['id', 'clinic_name']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('domain')
                    ->label('Dominio')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('tenant.clinic_name')
                    ->label('Clínica')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDomains::route('/'),
        ];
    }
}
