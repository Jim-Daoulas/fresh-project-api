<?php

namespace App\Filament\Resources\ReworkAbilityResource\Pages;

use App\Filament\Resources\ReworkAbilityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReworkAbility extends EditRecord
{
    protected static string $resource = ReworkAbilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
