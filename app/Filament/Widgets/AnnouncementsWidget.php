<?php

namespace App\Filament\Widgets;

use App\Enums\RoleTypes;
use App\Models\Announcement;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AnnouncementsWidget extends Widget
{
    protected static string $view = 'filament.widgets.announcements-widget';
    protected static ?int   $sort = 0; // Show before all other widgets
    protected int|string|array $columnSpan = 'full';

    /**
     * Map Filament panel IDs to the audience string used in announcements.
     */
    private static function audienceForCurrentPanel(): string
    {
        $user = Auth::user();

        if ($user?->isStudent())  return 'candidate';
        if ($user?->isReviewer()) return 'reviewer';
        if ($user?->isObserver()) return 'observer';
        if ($user?->isAdmin())    return 'admin';

        return 'all';
    }

    public function getAnnouncements()
    {
        $audience = static::audienceForCurrentPanel();

        return Announcement::published()
            ->forAudience($audience)
            ->latestFirst()
            ->limit(5)
            ->get();
    }

    public function getViewData(): array
    {
        return [
            'announcements' => $this->getAnnouncements(),
        ];
    }

    /**
     * Only show the widget when there are announcements to display.
     */
    public static function canView(): bool
    {
        $audience = static::audienceForCurrentPanel();

        return Announcement::published()->forAudience($audience)->exists();
    }
}
