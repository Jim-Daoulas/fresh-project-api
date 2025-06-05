<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChampionResource\Pages;
use App\Models\Champion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChampionResource extends Resource
{
    protected static ?string $model = Champion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                
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
                        'Bandle City' => 'Bandle City',
                        'Bilgewater' => 'Bilgewater',
                        'Demacia' => 'Demacia',
                        'Freljord' => 'Freljord',
                        'Ionia' => 'Ionia',
                        'Ixtal' => 'Ixtal',
                        'Noxus' => 'Noxus',
                        'Piltover' => 'Piltover',
                        'Shadow Isles' => 'Shadow Isles',
                        'Shurima' => 'Shurima',
                        'Targon' => 'Targon',
                        'The Void' => 'The Void',
                        'Zaun' => 'Zaun',
                    ]),
                
                Forms\Components\SpatieMediaLibraryFileUpload::make('champions')
                    ->collection('champions'),
                
                Forms\Components\TextInput::make('image_url')
                    ->url(),
                
                Forms\Components\Textarea::make('description')
                    ->required(),
                
                Forms\Components\KeyValue::make('stats'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('champions')
                    ->collection('champions')
                    ->circular()
                    ->size(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('region'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'Assassin' => 'Assassin',
                        'Fighter' => 'Fighter',
                        'Mage' => 'Mage',
                        'Marksman' => 'Marksman',
                        'Support' => 'Support',
                        'Tank' => 'Tank',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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