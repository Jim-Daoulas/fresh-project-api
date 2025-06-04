<?php

namespace App\Filament\Resources\ReworkResource\Pages;

use App\Filament\Resources\ReworkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReworks extends ListRecords
{
    protected static string $resource = ReworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
