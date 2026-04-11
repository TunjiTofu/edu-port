<?php

namespace App\Providers\Filament;

use App\Filament\Student\Pages\ChangePassword;
use App\Filament\Student\Resources\ChangePasswordResource;
use App\Filament\Student\Widgets\PerformanceChartWidget;
use App\Filament\Student\Widgets\RecentSubmissionsWidget;
use App\Filament\Student\Widgets\StudentProgressWidget;
use App\Filament\Student\Widgets\UpcomingDeadlinesWidget;
use App\Filament\Widgets\AnnouncementsWidget;
use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\EnsureProgramNotCompleted;
use App\Http\Middleware\EnsureUserIsStudent;
use App\Http\Middleware\ForcePasswordChange;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\UserMenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StudentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('student')
            ->path('student')
            ->login()
            ->colors(['primary' => Color::Green])
            ->brandName('MG Portfolio — Candidate Portal')
            ->favicon(asset('favicon.ico'))

            ->discoverResources(
                in: app_path('Filament/Student/Resources'),
                for: 'App\\Filament\\Student\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Student/Pages'),
                for: 'App\\Filament\\Student\\Pages'
            )
            ->pages([
                Pages\Dashboard::class,
                ChangePassword::class,
            ])
            ->widgets([
                AnnouncementsWidget::class,  // Announcements from admin shown first
                StudentProgressWidget::class,
                RecentSubmissionsWidget::class,
                UpcomingDeadlinesWidget::class,
                PerformanceChartWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ForcePasswordChange::class,
                // Runs after ForcePasswordChange so candidates who need a
                // password change are handled first, profile check second.
                EnsureProfileComplete::class,
                // Runs last — after auth and profile checks are satisfied.
                // Locks graduated candidates to read-only mode.
                EnsureProgramNotCompleted::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsStudent::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Learning')->collapsible(),
                NavigationGroup::make('Submissions')->collapsible(),
                NavigationGroup::make('Performance')->collapsible(),
                NavigationGroup::make('Account')->collapsible(),
            ])
            ->userMenuItems([
                'change-password' => UserMenuItem::make()
                    ->label('Change Password')
                    ->url(fn () => ChangePasswordResource::getUrl())
                    ->icon('heroicon-o-key')
                    ->sort(10),
            ])
            ->profile(isSimple: false);
    }
}
