<?php

namespace App\Providers\Filament;

use App\Filament\Reviewer\Pages\ChangePassword;
use App\Filament\Reviewer\Resources\ChangePasswordResource;
use App\Filament\Reviewer\Resources\Widgets\MyReviewsWidget;
use App\Filament\Reviewer\Resources\Widgets\ReviewerStatsWidget;
use App\Http\Middleware\EnsureUserIsReviewer;
use App\Http\Middleware\ForcePasswordChange;
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

class ReviewerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('reviewer')
            ->path('reviewer')
            ->login()
            ->colors([
                'primary' => Color::Sky,
            ])
            ->discoverResources(in: app_path('Filament/Reviewer/Resources'), for: 'App\\Filament\\Reviewer\\Resources')
            ->discoverPages(in: app_path('Filament/Reviewer/Pages'), for: 'App\\Filament\\Reviewer\\Pages')
            ->pages([
                Pages\Dashboard::class,
                ChangePassword::class,
            ])
//            ->discoverWidgets(in: app_path('Filament/Reviewer/Widgets'), for: 'App\\Filament\\Reviewer\\Widgets')
            ->widgets([
                AccountWidget::class,
                ReviewerStatsWidget::class,
                MyReviewsWidget::class
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
                ForcePasswordChange::class

            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsReviewer::class,
            ])
            ->brandName('Reviewer Portal')
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
