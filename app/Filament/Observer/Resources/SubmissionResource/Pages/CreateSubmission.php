<?php

namespace App\Filament\Observer\Resources\SubmissionResource\Pages;

use App\Filament\Observer\Resources\SubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSubmission extends CreateRecord
{
    protected static string $resource = SubmissionResource::class;
}
