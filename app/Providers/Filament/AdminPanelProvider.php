<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ChangePasswordResource;
use App\Filament\Widgets\AnnouncementsWidget;
use App\Filament\Widgets\ChurchStatsWidget;
use App\Filament\Widgets\ReviewerPerformanceWidget;
use App\Filament\Widgets\StudentDistributionBarChart;
use App\Filament\Widgets\StudentDistributionChart;
use App\Filament\Widgets\SubmissionAdminWidget;
use App\Filament\Widgets\SubmissionChartWidget;
use App\Http\Middleware\EnsureIsAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\UserMenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Amber])
            ->brandName('MG Portfolio — Admin')
            ->favicon(asset('favicon.ico'))

            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                // Announcements shown first — always visible when relevant
                AnnouncementsWidget::class,
                Widgets\AccountWidget::class,
                ChurchStatsWidget::class,
                StudentDistributionChart::class,
                StudentDistributionBarChart::class,
                SubmissionAdminWidget::class,
                SubmissionChartWidget::class,
                ReviewerPerformanceWidget::class,
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
                EnsureIsAdmin::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Academic Management')->collapsible(),
                NavigationGroup::make('User Management')->collapsible(),
                NavigationGroup::make('Communications')->collapsible(),
                NavigationGroup::make('System Configuration')->collapsible(),
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
