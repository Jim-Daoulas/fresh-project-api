<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChampionResource\Pages;
use App\Filament\Resources\ChampionResource\RelationManagers;
use App\Models\Champion;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
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
            Forms\Components\Section::make('Basic Information')
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
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Avatar')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('avatar')
                        ->disk('s3')
                        ->collection('avatars')
                        ->image()
                        ->imageEditor()
                        ->maxSize(5120)
                        ->helperText('Upload champion avatar (max 5MB)'),
                    Forms\Components\TextInput::make('image_url')
                        ->label('Image URL (Fallback)')
                        ->url()
                        ->placeholder('https://example.com/champion-image.jpg')
                        ->helperText('Optional: External image URL as fallback'),
                ]),
            
            Forms\Components\Section::make('Description')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
            
            Forms\Components\Section::make('Statistics')
                ->schema([
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
                ]),
        ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            SpatieMediaLibraryImageColumn::make('avatar')
                ->collection('avatars')
                ->conversion('thumb')
                ->circular()
                ->size(60)
                ->defaultImageUrl(function ($record) {
                    // Fallback to image_url field if no media
                    return $record->getAttributes()['image_url'] ?? 
                           'https://via.placeholder.com/100x100/667eea/ffffff?text=' . substr($record->name, 0, 1);
                }),
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->weight('bold'),
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable(),
            Tables\Columns\BadgeColumn::make('role')
                ->colors([
                    'danger' => 'Assassin',
                    'warning' => 'Fighter',
                    'primary' => 'Mage',
                    'success' => 'Marksman',
                    'info' => 'Support',
                    'secondary' => 'Tank',
                ]),
            Tables\Columns\TextColumn::make('region'),
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