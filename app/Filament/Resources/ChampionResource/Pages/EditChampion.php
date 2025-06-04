<?php

namespace App\Filament\Resources\ChampionResource\Pages;

use App\Filament\Resources\ChampionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChampion extends EditRecord
{
    protected static string $resource = ChampionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
