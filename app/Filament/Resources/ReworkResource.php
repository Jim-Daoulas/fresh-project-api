<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReworkResource\Pages;
use App\Filament\Resources\ReworkResource\RelationManagers;
use App\Models\Rework;
use App\Models\Champion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

class ReworkResource extends Resource
{
    protected static ?string $model = Rework::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Champions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rework Information')
                    ->schema([
                        Forms\Components\Select::make('champion_id')
                            ->relationship('champion', 'name')
                            ->required()
                            ->options(function ($record) {
                                // Στο edit, επέστρεψε όλους τους champions
                                if ($record) {
                                    return Champion::pluck('name', 'id');
                                }
                                // Στο create, επέστρεψε μόνο champions χωρίς rework
                                return Champion::whereDoesntHave('rework')->pluck('name', 'id');
                            })
                            ->rules(function ($record) {
                                return [
                                    Rule::unique('reworks', 'champion_id')->ignore($record?->id)
                                ];
                            })
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->options(function () {
                                // Επιστρέφει μόνο users που έχουν admin role
                                return \App\Models\User::whereHas('roles', function ($query) {
                                    $query->where('role_id', \App\Enum\RoleCode::admin);
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->default(auth()->id())
                            ->helperText('Original creator of this rework'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('summary')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('stats')
                            ->columnSpanFull()
                            ->helperText('JSON format for champion stats'),
                    ]),
                
                Forms\Components\Section::make('Tracking Information')
                    ->schema([
                        Forms\Components\Placeholder::make('created_info')
                            ->label('Created By')
                            ->content(function ($record) {
                                if (!$record) return 'Will be set on creation';
                                return $record->user->name ?? 'Unknown User';
                            }),
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(function ($record) {
                                if (!$record) return 'Will be set on creation';
                                return $record->created_at ? $record->created_at->format('d/m/Y H:i:s') : 'Unknown';
                            }),
                        Forms\Components\Placeholder::make('updated_info')
                            ->label('Last Modified By')
                            ->content(function ($record) {
                                if (!$record) return 'Will be set on first edit';
                                if ($record->updated_by && $record->updatedBy) {
                                    $modifier = $record->updatedBy->name;
                                    if ($record->user_id != $record->updated_by) {
                                        return $modifier . ' (different from creator)';
                                    }
                                    return $modifier . ' (original creator)';
                                }
                                return 'Unknown User';
                            }),
                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last Modified At')
                            ->content(function ($record) {
                                if (!$record) return 'Will be set on first edit';
                                return $record->updated_at ? $record->updated_at->format('d/m/Y H:i:s') : 'Unknown';
                            }),
                    ])
                    ->hidden(fn ($record) => !$record) // Κρύψε στο create mode
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('champion.name')
                    ->label('Champion')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('summary')
                    ->label('Summary')
                    ->limit(80)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 80) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->description(function ($record) {
                        if ($record->created_at != $record->updated_at) {
                            return 'Modified after creation';
                        }
                        return null;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('champion')
                    ->relationship('champion', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit this rework'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->tooltip('Delete this rework'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListReworks::route('/'),
            'create' => Pages\CreateRework::route('/create'),
            'edit' => Pages\EditRework::route('/{record}/edit'),
        ];
    }
}