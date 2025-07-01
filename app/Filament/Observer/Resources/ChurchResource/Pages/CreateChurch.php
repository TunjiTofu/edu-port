<?php

namespace App\Filament\Observer\Resources\ChurchResource\Pages;

use App\Filament\Observer\Resources\ChurchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateChurch extends CreateRecord
{
    protected static string $resource = ChurchResource::class;
}
