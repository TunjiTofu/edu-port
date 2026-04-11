<?php

namespace App\Filament\Student\Resources;

use App\Filament\Student\Resources\ProfileResource\Pages;
use App\Models\Church;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProfileResource extends Resource
{
    protected static ?string $model           = User::class;
    protected static ?string $navigationIcon  = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $navigationGroup = 'Account';
    protected static ?int    $navigationSort  = 10;

    public static function canViewAny(): bool  { return Auth::user()?->isStudent(); }
    public static function canCreate(): bool   { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canView($record): bool   { return Auth::id() === $record->id; }
    public static function canEdit($record): bool   { return Auth::id() === $record->id; }

    // Skip table — go straight to edit form
    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit', ['record' => Auth::id()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('id', Auth::id())
            ->with(['role', 'district', 'church']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── Passport Photo ───────────────────────────────────────────
                Forms\Components\Section::make('Passport Photograph')
                    ->icon('heroicon-o-camera')
                    ->description('Upload a clear passport-size photo. Max 1 MB. JPEG or PNG only.')
                    ->schema([
                        Forms\Components\FileUpload::make('passport_photo')
                            ->label('')
                            ->image()
                            ->disk(config('filesystems.default'))
                            ->directory('passport-photos')
                            ->visibility('private')
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')   // square crop for passport
                            ->imageResizeTargetWidth('400') // keep file light
                            ->imageResizeTargetHeight('400')
                            ->maxSize(1024)                 // 1 MB limit
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->helperText('Square crop. Clear face. Max 1 MB.')
                            ->columnSpanFull()
                            ->avatar(),                     // renders as a circle preview
                    ]),

                // ── Personal Information ─────────────────────────────────────
                Forms\Components\Section::make('Personal Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->disabled()->dehydrated(false)
                            ->prefixIcon('heroicon-o-user'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->disabled()->dehydrated(false)
                            ->prefixIcon('heroicon-o-envelope'),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()->maxLength(20)
                            ->required()
                            ->prefixIcon('heroicon-o-phone')
                            ->helperText('Required to complete your profile.'),

                        Forms\Components\TextInput::make('mg_mentor')
                            ->label('MG Mentor')
                            ->maxLength(255)
                            ->required()
                            ->prefixIcon('heroicon-o-academic-cap')
                            ->helperText('Full name of the minister mentoring you.'),
                    ])
                    ->columns(1),

                // ── Church & District ────────────────────────────────────────
                Forms\Components\Section::make('Church & District')
                    ->icon('heroicon-o-building-library')
                    ->description('Your district is set by an administrator. You may update your church within your district.')
                    ->schema([
                        Forms\Components\TextInput::make('district.name')
                            ->label('District')
                            ->disabled()->dehydrated(false)
                            ->prefixIcon('heroicon-o-map'),

                        Forms\Components\Select::make('church_id')
                            ->label('Church')
                            ->options(function () {
                                $districtId = Auth::user()?->district_id;
                                if (! $districtId) return [];
                                return Church::where('district_id', $districtId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->prefixIcon('heroicon-o-building-library'),
                    ])
                    ->columns(2),

                // ── Account Info ─────────────────────────────────────────────
                Forms\Components\Section::make('Account Information')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\TextInput::make('role.name')
                            ->label('Role')->disabled()->dehydrated(false)
                            ->formatStateUsing(fn () => 'Candidate')
                            ->prefixIcon('heroicon-o-identification'),

                        Forms\Components\TextInput::make('password_updated_at')
                            ->label('Password Last Changed')->disabled()->dehydrated(false)
                            ->formatStateUsing(fn ($state) => $state
                                ? \Carbon\Carbon::parse($state)->format('M j, Y g:i A')
                                : 'Never — default password active'
                            )
                            ->prefixIcon('heroicon-o-key'),
                    ])
                    ->columns(2)
                    ->collapsible()->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('phone')->placeholder('Not set'),
                Tables\Columns\TextColumn::make('church.name')->label('Church'),
            ])
            ->emptyStateHeading('My Profile')
            ->emptyStateIcon('heroicon-o-user-circle');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'edit'  => Pages\EditProfile::route('/{record}/edit'),
        ];
    }
}
