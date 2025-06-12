<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Role;
use App\Enum\RoleCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->minLength(6)
                            ->helperText('Leave empty to keep current password when editing'),
                    ]),
                
                Forms\Components\Section::make('Role & Progress')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->options([
                                RoleCode::admin => 'Admin',
                                RoleCode::user => 'User',
                            ])
                            ->getOptionLabelFromRecordUsing(fn (Role $record) => ucfirst($record->name))
                            ->required()
                            ->helperText('Select one or more roles for this user'),
                            
                        Forms\Components\TextInput::make('points')
                            ->numeric()
                            ->default(100)
                            ->minValue(0)
                            ->maxValue(99999)
                            ->helperText('Points for unlocking champions and skins'),
                            
                        Forms\Components\DatePicker::make('last_login_date')
                            ->label('Last Login Date')
                            ->displayFormat('d/m/Y')
                            ->helperText('Last time user logged in for daily bonus'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Account Status')
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('When the user verified their email'),
                            
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Account Created')
                            ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i:s') ?? 'Not created yet'),
                            
                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last Updated')
                            ->content(fn ($record) => $record?->updated_at?->format('d/m/Y H:i:s') ?? 'Not updated yet'),
                    ])
                    ->columns(2)
                    ->hidden(fn (string $context) => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope'),
                    
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'user' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->separator(', '),
                    
                Tables\Columns\TextColumn::make('points')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-star'),
                    
                Tables\Columns\TextColumn::make('last_login_date')
                    ->label('Last Login')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Never')
                    ->description(fn ($record) => $record->last_login_date ? 
                        $record->last_login_date->diffForHumans() : 'No login recorded'),
                    
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Filter by Role'),
                    
                Tables\Filters\Filter::make('email_verified')
                    ->label('Email Verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                    
                Tables\Filters\Filter::make('high_points')
                    ->label('High Points (>100)')
                    ->query(fn (Builder $query): Builder => $query->where('points', '>', 100)),
                    
                Tables\Filters\Filter::make('recent_login')
                    ->label('Recent Login (Last 7 days)')
                    ->query(fn (Builder $query): Builder => $query->where('last_login_date', '>=', now()->subDays(7))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('add_points')
                    ->label('Add Points')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('points_to_add')
                            ->label('Points to Add')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->default(50),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->increment('points', $data['points_to_add']);
                        
                        Notification::make()
                            ->title('Points Added')
                            ->body("Added {$data['points_to_add']} points to {$record->name}")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription('This will reset the user\'s password to "password123"')
                    ->action(function (User $record): void {
                        $record->update(['password' => Hash::make('password123')]);
                        
                        Notification::make()
                            ->title('Password Reset')
                            ->body("Password reset for {$record->name}")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('verify_emails')
                        ->label('Verify Emails')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['email_verified_at' => now()]);
                            });
                            
                            Notification::make()
                                ->title('Emails Verified')
                                ->body('Selected users\' emails have been verified')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}