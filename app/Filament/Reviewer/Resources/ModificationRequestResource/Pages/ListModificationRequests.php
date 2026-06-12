<?php

namespace App\Filament\Reviewer\Resources\ModificationRequestResource\Pages;

use App\Enums\ReviewModificationStatus;
use App\Filament\Reviewer\Resources\ModificationRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListModificationRequests extends ListRecords
{
    protected static string $resource = ModificationRequestResource::class;

    public function getTitle(): string
    {
        return 'My Modification Requests';
    }

    public function getSubheading(): ?string
    {
        $pending = ModificationRequestResource::getEloquentQuery()
            ->where('status', ReviewModificationStatus::PENDING->value)
            ->count();

        $approved = ModificationRequestResource::getEloquentQuery()
            ->where('status', ReviewModificationStatus::APPROVED->value)
            ->whereNull('used_at')
            ->count();

        if ($approved > 0) {
            return "🎉 {$approved} request(s) approved — open the review to apply your update.";
        }

        if ($pending > 0) {
            return "{$pending} request(s) awaiting admin decision.";
        }

        return 'Track the status of your requests to edit completed reviews.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
