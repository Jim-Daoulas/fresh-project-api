<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChampionResource\Pages;
use App\Models\Champion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

class ChampionResource extends Resource
{
    protected static ?string $model = Champion::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                
                Select::make('role')
                    ->required()
                    ->options([
                        'Tank' => 'Tank',
                        'Fighter' => 'Fighter',
                        'Assassin' => 'Assassin',
                        'Mage' => 'Mage',
                        'Marksman' => 'Marksman',
                        'Support' => 'Support',
                    ]),
                
                Select::make('region')
                    ->required()
                    ->options([
                        'Ionia' => 'Ionia',
                        'Demacia' => 'Demacia',
                        'Noxus' => 'Noxus',
                        'Freljord' => 'Freljord',
                        'Zaun' => 'Zaun',
                        'Piltover' => 'Piltover',
                        'Targon' => 'Targon',
                        'Shurima' => 'Shurima',
                        'Shadow Isles' => 'Shadow Isles',
                        'Bilgewater' => 'Bilgewater',
                        'Void' => 'Void',
                        'Bandle City' => 'Bandle City',
                    ]),
                
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                
                SpatieMediaLibraryFileUpload::make('avatar')
                    ->label('Champion Avatar')
                    ->collection('avatar')
                    ->image()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('400')
                    ->imageResizeTargetHeight('400')
                    ->columnSpanFull(),
                
                Forms\Components\Fieldset::make('Stats')
                    ->schema([
                        TextInput::make('stats.hp')
                            ->label('Health Points')
                            ->numeric()
                            ->default(0),
                        TextInput::make('stats.mana')
                            ->label('Mana Points')
                            ->numeric()
                            ->default(0),
                        TextInput::make('stats.attack')
                            ->label('Attack Damage')
                            ->numeric()
                            ->default(0),
                        TextInput::make('stats.defense')
                            ->label('Defense')
                            ->numeric()
                            ->default(0),
                        TextInput::make('stats.ability_power')
                            ->label('Ability Power')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('Avatar')
                    ->collection('avatar')
                    ->conversion('thumb')
                    ->circular()
                    ->size(60),
                
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('title')
                    ->searchable()
                    ->limit(30),
                
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tank' => 'success',
                        'Fighter' => 'warning',
                        'Assassin' => 'danger',
                        'Mage' => 'info',
                        'Marksman' => 'primary',
                        'Support' => 'secondary',
                        default => 'gray',
                    }),
                
                TextColumn::make('region')
                    ->searchable(),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'Tank' => 'Tank',
                        'Fighter' => 'Fighter',
                        'Assassin' => 'Assassin',
                        'Mage' => 'Mage',
                        'Marksman' => 'Marksman',
                        'Support' => 'Support',
                    ]),
                
                Tables\Filters\SelectFilter::make('region')
                    ->options([
                        'Ionia' => 'Ionia',
                        'Demacia' => 'Demacia',
                        'Noxus' => 'Noxus',
                        'Freljord' => 'Freljord',
                        'Zaun' => 'Zaun',
                        'Piltover' => 'Piltover',
                        'Targon' => 'Targon',
                        'Shurima' => 'Shurima',
                        'Shadow Isles' => 'Shadow Isles',
                        'Bilgewater' => 'Bilgewater',
                        'Void' => 'Void',
                        'Bandle City' => 'Bandle City',
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