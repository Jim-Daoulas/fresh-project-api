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
                            ->label('Primary Role')
                            ->required()
                            ->options([
                                'Assassin' => 'Assassin',
                                'Enchanter' => 'Enchanter',
                                'Catcher' => 'Catcher',
                                'Marksman' => 'Marksman',
                                'Juggernaut' => 'Juggernaut',
                                'Diver' => 'Diver',
                                'Burst' => 'Burst',
                                'Battlemage' => 'Battlemage',
                                'Artillery' => 'Artillery',
                                'Skirmisher' => 'Skirmisher',
                                'Vanguard' => 'Vanguard',
                                'Warden' => 'Warden',
                                'Specialist' => 'Specialist',
                            ]),
                        Forms\Components\Select::make('secondary_role')
                            ->label('Secondary Role (Optional)')
                            ->nullable() // Κάνε το optional
                            ->options([
                                'Assassin' => 'Assassin',
                                'Fighter' => 'Fighter',
                                'Mage' => 'Mage',
                                'Marksman' => 'Marksman',
                                'Support' => 'Support',
                                'Tank' => 'Tank',
                            ])
                            ->different('role'), // Δεν μπορεί να είναι ίδιο με το primary role
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
                            ->deletable()
                            ->reorderable()
                            ->default([
                                'hp' => '0',
                                'mp' => '0',
                                'Health_Regen' => '0',
                                'Mana_regen' => '0',
                                'Armor' => '0',
                                'Attack' => '0',
                                'Magic_Resistance' => '0',
                                'Critical_Damage' => '0',
                                'Move_Speed' => '0',
                                'Attack_Range' => '0',
                            ])
                            ->columnSpanFull(),
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
                    ->size(60),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                // Updated roles column to show both roles
                Tables\Columns\TextColumn::make('roles')
                    ->label('Roles')
                    ->getStateUsing(function (Champion $record) {
                        $roles = [$record->role];
                        if ($record->secondary_role) {
                            $roles[] = $record->secondary_role;
                        }
                        return implode(' / ', $roles);
                    })
                    ->badge()
                    ->color('primary'),

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
                    ->label('Primary Role')
                    ->options([
                        'Assassin' => 'Assassin',
                        'Enchanter' => 'Enchanter',
                        'Catcher' => 'Catcher',
                        'Marksman' => 'Marksman',
                        'Juggernaut' => 'Juggernaut',
                        'Diver' => 'Diver',
                        'Burst' => 'Burst',
                        'Battlemage' => 'Battlemage',
                        'Artillery' => 'Artillery',
                        'Skirmisher' => 'Skirmisher',
                        'Vanguard' => 'Vanguard',
                        'Warden' => 'Warden',
                        'Specialist' => 'Specialist',

                    ]),
                Tables\Filters\SelectFilter::make('secondary_role')
                    ->label('Secondary Role')
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