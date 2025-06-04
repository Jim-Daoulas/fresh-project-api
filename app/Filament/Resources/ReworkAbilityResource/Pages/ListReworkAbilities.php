<?php

namespace App\Filament\Resources\ReworkAbilityResource\Pages;

use App\Filament\Resources\ReworkAbilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReworkAbilities extends ListRecords
{
    protected static string $resource = ReworkAbilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
