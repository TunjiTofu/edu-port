<?php

namespace App\Filament\Reviewer\Resources\ReviewQueueResource\Pages;

use App\Filament\Reviewer\Resources\ReviewQueueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReviewQueue extends CreateRecord
{
    protected static string $resource = ReviewQueueResource::class;
}
