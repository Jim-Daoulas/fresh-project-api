<?php

namespace App\Filament\Resources\ReworkResource\Pages;

use App\Filament\Resources\ReworkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRework extends EditRecord
{
    protected static string $resource = ReworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
