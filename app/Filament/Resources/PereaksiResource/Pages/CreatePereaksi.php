<?php

namespace App\Filament\Resources\PereaksiResource\Pages;

use App\Filament\Resources\PereaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePereaksi extends CreateRecord
{
    protected static string $resource = PereaksiResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
