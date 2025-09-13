<?php

namespace App\Filament\Observer\Resources\ChangePasswordResource\Pages;

use App\Filament\Observer\Resources\ChangePasswordResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateChangePassword extends CreateRecord
{
    protected static string $resource = ChangePasswordResource::class;
}
