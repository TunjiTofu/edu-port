<?php

namespace App\Providers\Filament;

use App\Filament\Student\Resources\ChangePasswordResource;
use App\Filament\Student\Widgets\RecentSubmissionsWidget;
use App\Filament\Student\Widgets\StudentProgressWidget;
use App\Filament\Student\Widgets\UpcomingDeadlinesWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
use App\Http\Middleware\EnsureUserIsStudent;

class StudentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('student')
            ->path('student')
            ->login()
            ->colors([
                'primary' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Student/Resources'), for: 'App\\Filament\\Student\\Resources')
            ->discoverPages(in: app_path('Filament/Student/Pages'), for: 'App\\Filament\\Student\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
//            ->discoverWidgets(in: app_path('Filament/Student/Widgets'), for: 'App\\Filament\\Student\\Widgets')
            ->widgets([
                StudentProgressWidget::class,
                RecentSubmissionsWidget::class,
                UpcomingDeadlinesWidget::class
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
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsStudent::class,
            ])
            ->brandName('Candidate Portal')
            ->favicon(asset('favicon.ico'))
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('Learning')
                    // ->icon('heroicon-o-academic-cap')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Submissions')
                    // ->icon('heroicon-o-document-text')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Performance')
                    // ->icon('heroicon-o-chart-bar')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('User Management')
                    // ->icon('heroicon-o-chart-bar')
                    ->collapsible(),
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
