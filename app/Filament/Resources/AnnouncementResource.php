<?php

namespace App\Filament\Resources;

use App\Enums\RoleTypes;
use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Models\User;
use App\Services\TermiiService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AnnouncementResource extends Resource
{
    protected static ?string $model          = Announcement::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Announcements & Broadcast';
    protected static ?string $navigationGroup = 'Communications';
    protected static ?int    $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Announcement Content')
                    ->icon('heroicon-o-megaphone')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Important Update on Submission Deadline')
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('body')
                            ->required()
                            ->toolbarButtons([
                                'bold', 'italic', 'underline',
                                'bulletList', 'orderedList',
                                'link', 'redo', 'undo',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Audience & Delivery')
                    ->icon('heroicon-o-user-group')
                    ->description('Choose who sees this announcement and how it is delivered.')
                    ->schema([
                        Forms\Components\Select::make('audience')
                            ->label('Audience')
                            ->options([
                                'all'       => 'Everyone (all roles)',
                                'candidate' => 'Candidates only',
                                'reviewer'  => 'Reviewers only',
                                'observer'  => 'Observers only',
                                'admin'     => 'Admins only',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->helperText('This controls who sees the announcement on their dashboard.'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Publish to Dashboard')
                            ->default(true)
                            ->helperText('When enabled, the selected audience will see this on their dashboard immediately.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Delivery Channels')
                    ->icon('heroicon-o-paper-airplane')
                    ->description('Optionally send this announcement via email and/or SMS in addition to the platform dashboard.')
                    ->schema([
                        Forms\Components\Checkbox::make('send_email')
                            ->label('Send via Email')
                            ->helperText('An email will be sent to all users in the selected audience.'),

                        Forms\Components\Checkbox::make('send_sms')
                            ->label('Send via SMS (Termii)')
                            ->helperText('An SMS will be sent to all phone numbers in the selected audience. Charges apply.'),
                    ])
                    ->columns(2),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()->weight('bold')->limit(50),

                Tables\Columns\TextColumn::make('audience')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'all'       => 'primary',
                        'candidate' => 'success',
                        'reviewer'  => 'warning',
                        'observer'  => 'info',
                        'admin'     => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'all'       => '🌍 Everyone',
                        'candidate' => '🎓 Candidates',
                        'reviewer'  => '🔍 Reviewers',
                        'observer'  => '👁 Observers',
                        'admin'     => '🛡 Admins',
                        default     => ucfirst($state),
                    }),

                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()->label('Published'),

                Tables\Columns\IconColumn::make('sent_email')
                    ->boolean()->label('Email Sent'),

                Tables\Columns\IconColumn::make('sent_sms')
                    ->boolean()->label('SMS Sent'),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Created By')->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('audience')
                    ->options([
                        'all' => 'Everyone', 'candidate' => 'Candidates',
                        'reviewer' => 'Reviewers', 'observer' => 'Observers', 'admin' => 'Admins',
                    ]),
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // ── Resend / Send now ─────────────────────────────────────
                Tables\Actions\Action::make('send_now')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Send Announcement?')
                    ->modalDescription(fn (Announcement $record) =>
                    "This will send '{$record->title}' to all {$record->audienceLabel()} via email and/or SMS."
                    )
                    ->form([
                        Forms\Components\Checkbox::make('via_email')
                            ->label('Send via Email')->default(true),
                        Forms\Components\Checkbox::make('via_sms')
                            ->label('Send via SMS (Termii)')->default(false),
                    ])
                    ->action(function (Announcement $record, array $data) {
                        static::dispatchBroadcast($record, $data['via_email'], $data['via_sms']);
                    }),

                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Broadcast Logic ───────────────────────────────────────────────────────

    /**
     * Send an announcement to all users in its audience via the chosen channels.
     * Called both from the create page (auto-send) and the "Send Now" table action.
     */
    public static function dispatchBroadcast(
        Announcement $announcement,
        bool $viaEmail,
        bool $viaSms
    ): void {
        $users = static::getAudienceUsers($announcement->audience);

        $emailSent = false;
        $smsSent   = false;
        $emailFail = 0;
        $smsFail   = 0;

        Log::info('Announcement broadcast started', [
            'event'           => 'announcement_broadcast_start',
            'announcement_id' => $announcement->id,
            'audience'        => $announcement->audience,
            'user_count'      => $users->count(),
            'via_email'       => $viaEmail,
            'via_sms'         => $viaSms,
            'admin_id'        => Auth::id(),
        ]);

        // ── Email ──────────────────────────────────────────────────────────
        if ($viaEmail) {
            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(
                        new \App\Mail\AnnouncementMail($announcement, $user)
                    );
                    $emailSent = true;
                } catch (\Exception $e) {
                    $emailFail++;
                    Log::error('Announcement email failed', [
                        'event'           => 'announcement_email_failed',
                        'announcement_id' => $announcement->id,
                        'user_id'         => $user->id,
                        'error'           => $e->getMessage(),
                    ]);
                }
            }
        }

        // ── SMS ────────────────────────────────────────────────────────────
        if ($viaSms) {
            $termii  = app(TermiiService::class);
            // Strip HTML from body for SMS
            $smsBody = strip_tags($announcement->body);
            $smsText = "[MG Portfolio] {$announcement->title}: " . substr($smsBody, 0, 140);

            foreach ($users->whereNotNull('phone') as $user) {
                $result = $termii->sendSms($user->phone, $smsText);
                if ($result['success']) {
                    $smsSent = true;
                } else {
                    $smsFail++;
                    Log::warning('Announcement SMS failed', [
                        'event'           => 'announcement_sms_failed',
                        'announcement_id' => $announcement->id,
                        'user_id'         => $user->id,
                    ]);
                }
            }
        }

        // Update flags on the announcement record
        $announcement->update([
            'sent_email'   => $emailSent,
            'sent_sms'     => $smsSent,
            'published_at' => $announcement->published_at ?? now(),
        ]);

        Log::info('Announcement broadcast complete', [
            'event'           => 'announcement_broadcast_complete',
            'announcement_id' => $announcement->id,
            'email_sent'      => $emailSent,
            'email_failures'  => $emailFail,
            'sms_sent'        => $smsSent,
            'sms_failures'    => $smsFail,
        ]);

        $summary = [];
        if ($viaEmail) $summary[] = ($users->count() - $emailFail) . ' email(s) sent';
        if ($viaSms)   $summary[] = ($users->whereNotNull('phone')->count() - $smsFail) . ' SMS sent';

        if ($emailFail > 0 || $smsFail > 0) {
            Notification::make()
                ->warning()
                ->title('Broadcast Sent with Some Failures')
                ->body(implode(' · ', $summary) . " ({$emailFail} email fail, {$smsFail} SMS fail)")
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Broadcast Sent')
                ->body(implode(' · ', $summary))
                ->send();
        }
    }

    /**
     * Get the User collection for a given audience string.
     */
    private static function getAudienceUsers(string $audience)
    {
        $query = User::where('is_active', true)->with('role');

        return match ($audience) {
            'candidate' => $query->students()->get(),
            'reviewer'  => $query->reviewers()->get(),
            'observer'  => $query->whereHas('role',
                fn ($q) => $q->where('name', RoleTypes::OBSERVER->value))->get(),
            'admin'     => $query->admins()->get(),
            default     => $query->get(), // 'all'
        };
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'view'   => Pages\ViewAnnouncement::route('/{record}'),
            'edit'   => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
