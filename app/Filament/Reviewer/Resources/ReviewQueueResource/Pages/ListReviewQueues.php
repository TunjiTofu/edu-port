<?php

namespace App\Filament\Reviewer\Resources\ReviewQueueResource\Pages;

use App\Filament\Reviewer\Resources\ReviewQueueResource;
use App\Enums\SubmissionTypes;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListReviewQueues extends ListRecords
{
    protected static string $resource = ReviewQueueResource::class;

    public function getTitle(): string
    {
        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default    => 'Good evening',
        };

        $name = explode(' ', Auth::user()->name)[0] ?? '';

        return "{$greeting}, {$name}! 👋";
    }

    public function getSubheading(): ?string
    {
        $pending = ReviewQueueResource::getEloquentQuery()
            ->whereIn('status', [
                SubmissionTypes::PENDING_REVIEW->value,
                SubmissionTypes::UNDER_REVIEW->value,
            ])
            ->count();

        if ($pending === 0) {
            return "You're all caught up — nothing waiting in your queue right now.";
        }

        return $pending === 1
            ? "You have 1 submission waiting for review."
            : "You have {$pending} submissions waiting for review.";
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
