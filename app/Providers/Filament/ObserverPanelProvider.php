<?php

namespace App\Providers\Filament;

use App\Filament\Reviewer\Resources\ChangePasswordResource;
use App\Filament\Widgets\ChurchAnalyticsChart;
use App\Filament\Widgets\ChurchStatsWidget;
use App\Filament\Widgets\ReviewerPerformanceWidget;
use App\Filament\Widgets\SubmissionAdminWidget;
use App\Filament\Widgets\SubmissionChartWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\UserMenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\EnsureUserIsObserver;

class ObserverPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('observer')
            ->path('observer')
            ->login()
            ->colors([
                'primary' => Color::Purple,
            ])
            ->discoverResources(in: app_path('Filament/Observer/Resources'), for: 'App\\Filament\\Observer\\Resources')
            ->discoverPages(in: app_path('Filament/Observer/Pages'), for: 'App\\Filament\\Observer\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
//            ->discoverWidgets(in: app_path('Filament/Observer/Widgets'), for: 'App\\Filament\\Observer\\Widgets')
            ->widgets([
                AccountWidget::class,
                ChurchStatsWidget::class,
                ChurchAnalyticsChart::class,
                SubmissionAdminWidget::class,
                SubmissionChartWidget::class,
//                ReviewerPerformanceWidget::class,
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
                EnsureUserIsObserver::class,
            ])
            ->brandName('Observer Portal')
            ->favicon(asset('favicon.ico'))
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
