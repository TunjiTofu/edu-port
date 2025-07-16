<?php

namespace App\Filament\Student\Resources\ProfileResource\Pages;

use App\Filament\Student\Resources\ProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProfile extends CreateRecord
{
    protected static string $resource = ProfileResource::class;
}
