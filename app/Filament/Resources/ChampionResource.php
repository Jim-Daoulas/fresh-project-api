<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChampionResource\Pages;
use App\Models\Champion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChampionResource extends Resource
{
    protected static ?string $model = Champion::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Champions';

    protected static ?string $modelLabel = 'Champion';

    protected static ?string $pluralModelLabel = 'Champions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., the Nine-Tailed Fox'),
                        
                        Forms\Components\Select::make('role')
                            ->required()
                            ->options([
                                'Assassin' => 'Assassin',
                                'Fighter' => 'Fighter',
                                'Mage' => 'Mage',
                                'Marksman' => 'Marksman',
                                'Support' => 'Support',
                                'Tank' => 'Tank',
                            ])
                            ->searchable(),
                        
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
                            ])
                            ->searchable(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Avatar')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('champions')
                            ->label('Champion Avatar')
                            ->collection('champions')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                            ->helperText('Upload champion portrait image (max 5MB)')
                            ->downloadable()
                            ->openable()
                            ->deletable(),
                        
                        Forms\Components\TextInput::make('image_url')
                            ->label('External Image URL (Fallback)')
                            ->url()
                            ->placeholder('https://example.com/champion-image.jpg')
                            ->helperText('Optional: External image URL as fallback'),
                    ]),
                
                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(6)
                            ->placeholder('Write the champion lore and background story...')
                    ]),
                
                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\KeyValue::make('stats')
                            ->label('Base Stats')
                            ->keyLabel('Stat Name')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->addActionLabel('Add Stat')
                            ->helperText('Add champion base statistics (e.g., hp: 580, mana: 350, attack: 65)')
                            ->default([
                                'hp' => 500,
                                'mana' => 300,
                                'attack' => 60,
                                'defense' => 25,
                                'ability_power' => 0
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('champions')
                    ->label('Avatar')
                    ->collection('champions')
                    ->circular()
                    ->size(60)
                    ->defaultImageUrl(function ($record) {
                        // Fallback στο image_url attribute αν δεν υπάρχει media
                        return $record->attributes['image_url'] ?? 
                               'https://via.placeholder.com/150x150/667eea/ffffff?text=' . substr($record->name, 0, 1);
                    }),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->italic()
                    ->limit(40),
                
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
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'Assassin' => 'Assassin',
                        'Fighter' => 'Fighter',
                        'Mage' => 'Mage',
                        'Marksman' => 'Marksman',
                        'Support' => 'Support',
                        'Tank' => 'Tank',
                    ]),
                
                Tables\Filters\SelectFilter::make('region')
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
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make()
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            // You can add relation managers here for abilities, skins, etc.
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}