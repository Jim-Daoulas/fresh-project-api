<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChampionResource\Pages;
use App\Filament\Resources\ChampionResource\RelationManagers;
use App\Models\Champion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class ChampionResource extends Resource
{
    protected static ?string $model = Champion::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Champions';


    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('role')
                ->required()
                ->options([
                    'Assassin' => 'Assassin',
                    'Fighter' => 'Fighter',
                    'Mage' => 'Mage',
                    'Marksman' => 'Marksman',
                    'Support' => 'Support',
                    'Tank' => 'Tank',
                ]),
            Forms\Components\Select::make('region')
                ->required()
                ->options([
                    'Demacia' => 'Demacia',
                    'Noxus' => 'Noxus',
                    'Ionia' => 'Ionia',
                    'Shurima' => 'Shurima',
                    'Freljord' => 'Freljord',
                    'Bilgewater' => 'Bilgewater',
                    'Piltover' => 'Piltover',
                    'Zaun' => 'Zaun',
                    'Shadow Isles' => 'Shadow Isles',
                    'Void' => 'Void',
                    'Bandle City' => 'Bandle City',
                    'Ixtal' => 'Ixtal',
                    'Targon' => 'Targon',
                ]),
            Forms\Components\Textarea::make('description')
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('image_url')
                ->url()
                ->maxLength(255),
            Forms\Components\KeyValue::make('stats')
                ->keyLabel('Stat Name')
                ->valueLabel('Value')
                ->addable()
                ->default([
                    'hp' => '0',
                    'mana' => '0',
                    'attack' => '0',
                    'defense' => '0',
                    'ability_power' => '0',
                ]),
        ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable(),
            Tables\Columns\TextColumn::make('title')
                ->searchable(),
            Tables\Columns\TextColumn::make('role')
                ->searchable(),
            Tables\Columns\TextColumn::make('region')
                ->searchable(),
            Tables\Columns\ImageColumn::make('image_url'),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            // Champion Name Search
            Tables\Filters\Filter::make('champion_search')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('Champion Name')
                        ->placeholder('Search by champion name...'),
                ])
                ->query(function ($query, array $data) {
                    return $query->when($data['name'], function ($query, $name) {
                        return $query->where('name', 'like', "%{$name}%");
                    });
                }),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Champion')
                ->modalDescription('Are you sure you want to delete this champion?')
                ->modalSubmitActionLabel('Yes, delete it'),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
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
            'index' => Pages\ListChampions::route('/'),
            'create' => Pages\CreateChampion::route('/create'),
            'edit' => Pages\EditChampion::route('/{record}/edit'),
        ];
    }
}
