<?php

namespace App\Filament\Resources;

use App\Models\SiteSetting;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * SiteSettingsPageResource
 *
 * Rendered as a single-record settings page rather than a list/create/edit.
 * The "list" page IS the settings form — there is no index table.
 * Navigation leads directly to the settings form.
 */
class SiteSettingsPageResource extends Resource
{
    // We don't bind to an Eloquent model — settings are managed via
    // static SiteSetting::get/set helpers backed by the site_settings table.
    protected static ?string $model = null;

    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?string $navigationGroup = 'System Configuration';
    protected static ?string $breadcrumb      = 'Site Settings';
    protected static ?int    $navigationSort  = 10;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    // Resource has no model, so disable all standard CRUD operations
    public static function canCreate(): bool  { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form { return $form->schema([]); }

    public static function table(Table $table): Table { return $table->columns([]); }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => SiteSettingsPageResource\Pages\ManageSiteSettings::route('/'),
        ];
    }
}
