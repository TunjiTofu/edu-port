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

            // ── Sidebar behaviour ──────────────────────────────────────────
            ->sidebarCollapsibleOnDesktop()   // adds toggle button; collapses to icon rail
            ->sidebarWidth('15rem')           // slightly narrower than Filament default (20rem)
            ->collapsedSidebarWidth('3.5rem') // icon-only rail width when collapsed

            // ── Custom CSS ─────────────────────────────────────────────────
            // Reduces the gap between sidebar and main content, and enforces
            // consistent square passport photo thumbnails across all user cards.
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_START,
                fn () => new \Illuminate\Support\HtmlString('
                <style>
                    /* ── Tighter sidebar-to-content gap ── */
                    .fi-main-ctn {
                        padding-inline-start: 0 !important;
                    }
                    .fi-layout .fi-main {
                        padding: 1rem 1.25rem !important;
                    }

                    /* ── User cards: prevent content overflowing border ── */
                    .fi-ta-content .fi-ta-record-checkbox-cell,
                    [class*="fi-ta-"] .fi-ta-col {
                        min-width: 0;
                    }

                    /* The card container itself must clip overflow */
                    .fi-ta-ctn [class*="rounded"] {
                        overflow: hidden;
                    }

                    /* Name text: wrap long names, never overflow */
                    .fi-ta-col .font-bold,
                    .fi-ta-col [class*="font-bold"],
                    .fi-ta-col .text-base {
                        white-space: normal !important;
                        word-break: break-word !important;
                        overflow-wrap: break-word !important;
                        line-height: 1.3 !important;
                    }

                    /* Stack layout columns: constrain width so text wraps */
                    .fi-ta-col-stack {
                        min-width: 0 !important;
                        max-width: 100% !important;
                        overflow: hidden !important;
                    }

                    /* Split layout inside cards: keep items from overflowing */
                    .fi-ta-col-split {
                        min-width: 0 !important;
                        overflow: hidden !important;
                    }

                    /* All text columns inside card stacks */
                    .fi-ta-col-stack .fi-ta-col-text {
                        white-space: normal !important;
                        word-break: break-word !important;
                        overflow: hidden !important;
                    }

                    /* ── Square passport photos in user cards ── */
                    .user-photo {
                        width: 52px !important;
                        height: 52px !important;
                        min-width: 52px !important;
                        border-radius: 8px !important;
                        object-fit: cover !important;
                        object-position: center top !important;
                        flex-shrink: 0 !important;
                        display: block;
                    }

                    /* ── Square photo on ViewUser infolist ── */
                    .user-photo-lg {
                        width: 200px !important;
                        height: 200px !important;
                        border-radius: 4px !important;
                        object-fit: cover !important;
                        object-position: center top !important;
                        display: block;
                    }
                </style>')
            )

            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )
            ->pages([
                \App\Filament\Pages\AdminDashboard::class,
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
