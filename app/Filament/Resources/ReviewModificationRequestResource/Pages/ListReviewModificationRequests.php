<?php

namespace App\Filament\Resources\ReviewModificationRequestResource\Pages;

use App\Filament\Resources\ReviewModificationRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListReviewModificationRequests extends ListRecords
{
    protected static string $resource = ReviewModificationRequestResource::class;

    // No CreateAction — modification requests come from reviewers, not admins.
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Review Modification Requests';
    }
}
