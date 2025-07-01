<?php

namespace App\Filament\Observer\Resources\UserResource\Pages;

use App\Filament\Observer\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
