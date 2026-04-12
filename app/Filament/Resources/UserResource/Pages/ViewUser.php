<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\RoleTypes;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('graduate')
                ->label('Mark as Graduated')
                ->icon('heroicon-o-academic-cap')->color('success')
                ->requiresConfirmation()
                ->modalDescription('Candidate will be locked to read-only mode.')
                ->visible(fn () => $this->record->isStudent() && ! $this->record->hasCompletedProgram() && ! $this->record->isDisqualified())
                ->action(function () {
                    $this->record->markProgramCompleted();
                    Log::info('Admin: candidate graduated from ViewUser', ['admin_id' => Auth::id(), 'user_id' => $this->record->id]);
                    Notification::make()->title('Marked as Graduated')->success()->send();
                    $this->refreshFormData(['program_completed_at']);
                }),

            Actions\Action::make('ungraduate')
                ->label('Undo Graduation')
                ->icon('heroicon-o-arrow-uturn-left')->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isStudent() && $this->record->hasCompletedProgram())
                ->action(function () {
                    $this->record->unmarkProgramCompleted();
                    Notification::make()->title('Graduation Reversed')->warning()->send();
                    $this->refreshFormData(['program_completed_at']);
                }),

            Actions\Action::make('disqualify')
                ->label('Disqualify')
                ->icon('heroicon-o-no-symbol')->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Disqualify this Candidate?')
                ->modalDescription('The candidate will be logged out and blocked from logging in until restored.')
                ->form([
                    Forms\Components\Textarea::make('disqualification_reason')
                        ->label('Reason')->required()->rows(3),
                ])
                ->visible(fn () => $this->record->isStudent() && ! $this->record->isDisqualified())
                ->action(function (array $data) {
                    $this->record->disqualify($data['disqualification_reason']);
                    Log::warning('Admin: disqualified from ViewUser', ['admin_id' => Auth::id(), 'user_id' => $this->record->id]);
                    Notification::make()->title('Candidate Disqualified')->danger()->send();
                    $this->refreshFormData(['disqualified_at', 'is_active']);
                }),

            Actions\Action::make('restore_candidate')
                ->label('Restore Candidate')
                ->icon('heroicon-o-arrow-path')->color('info')
                ->requiresConfirmation()
                ->modalDescription('The candidate will be re-activated and can log in again.')
                ->visible(fn () => $this->record->isStudent() && $this->record->isDisqualified())
                ->action(function () {
                    $this->record->undisqualify();
                    Log::info('Admin: candidate restored from ViewUser', ['admin_id' => Auth::id(), 'user_id' => $this->record->id]);
                    Notification::make()->title('Candidate Restored')->success()->send();
                    $this->refreshFormData(['disqualified_at', 'is_active']);
                }),

            Actions\DeleteAction::make()
                ->before(function () {
                    $record = $this->record;
                    if ($record->role?->name === RoleTypes::ADMIN->value
                        && User::where('role_id', $record->role_id)->count() <= 1
                    ) {
                        Notification::make()->title('Denied')
                            ->body('You cannot delete the last admin user.')
                            ->danger()->persistent()->send();
                        throw new Halt();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── Profile header ────────────────────────────────────────
                Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 5])
                            ->schema([
                                ImageEntry::make('passport_photo_url')
                                    ->label('')
                                    ->disk(null)
                                    ->circular()
                                    ->size(100)
                                    ->defaultImageUrl(
                                        fn ($record) => \Illuminate\Support\Facades\Storage::disk('public')
                                            ->url('passport-photos/default-avatar.jpg')
                                    )
                                    ->columnSpan(1),

                                \Filament\Infolists\Components\Group::make([
                                    TextEntry::make('name')
                                        ->size('lg')->weight('bold'),

                                    TextEntry::make('role.name')
                                        ->badge()
                                        ->color(fn (?string $state) => match ($state) {
                                            RoleTypes::STUDENT->value  => 'primary',
                                            RoleTypes::REVIEWER->value => 'success',
                                            RoleTypes::OBSERVER->value => 'warning',
                                            RoleTypes::ADMIN->value    => 'danger',
                                            default => 'gray',
                                        }),

                                    // Graduation status badge — only visible for candidates
                                    TextEntry::make('program_completed_at')
                                        ->label('')
                                        ->badge()
                                        ->color('success')
                                        ->formatStateUsing(fn ($state) =>
                                        $state ? '🎓 Graduated — ' . \Carbon\Carbon::parse($state)->format('M j, Y') : null
                                        )
                                        ->visible(fn ($record) =>
                                            $record->isStudent() && $record->hasCompletedProgram()
                                        ),

                                    // Disqualification badge
                                    TextEntry::make('disqualified_at')
                                        ->label('')
                                        ->badge()
                                        ->color('danger')
                                        ->formatStateUsing(fn ($state) =>
                                        $state ? '🚫 Disqualified — ' . \Carbon\Carbon::parse($state)->format('M j, Y') : null
                                        )
                                        ->visible(fn ($record) =>
                                            $record->isStudent() && $record->isDisqualified()
                                        ),

                                    // Inactive badge
                                    TextEntry::make('is_active')
                                        ->label('')
                                        ->badge()
                                        ->color('gray')
                                        ->formatStateUsing(fn ($state) => ! $state ? '⏸ Account Inactive' : null)
                                        ->visible(fn ($record) => ! $record->is_active && ! $record->isDisqualified()),
                                ])->columnSpan(['default' => 1, 'sm' => 4]),
                            ]),
                    ]),

                // ── Personal information ──────────────────────────────────
                Section::make('Personal Information')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                TextEntry::make('email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),

                                TextEntry::make('phone')
                                    ->icon('heroicon-m-phone')
                                    ->placeholder('Not provided')
                                    ->copyable(),

                                TextEntry::make('mg_mentor')
                                    ->label('MG Mentor')
                                    ->icon('heroicon-m-academic-cap')
                                    ->placeholder('Not set')
                                    ->visible(fn ($record) => $record->isStudent()),
                            ]),
                    ]),

                // ── Assignment ────────────────────────────────────────────
                Section::make('Assignment')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                TextEntry::make('district.name')
                                    ->label('District')
                                    ->placeholder('Not assigned'),

                                TextEntry::make('church.name')
                                    ->label('Church')
                                    ->placeholder('Not assigned'),

                                IconEntry::make('is_active')
                                    ->label('Account Active')
                                    ->boolean(),
                            ]),
                    ]),

                // ── Account information ───────────────────────────────────
                Section::make('Account Information')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->dateTime('M j, Y')
                                    ->placeholder('Not verified'),

                                TextEntry::make('password_updated_at')
                                    ->label('Password Changed')
                                    ->dateTime('M j, Y')
                                    ->placeholder('Never — default password'),

                                TextEntry::make('profile_completed_at')
                                    ->label('Profile Completed')
                                    ->dateTime('M j, Y')
                                    ->placeholder('Incomplete'),

                                TextEntry::make('program_completed_at')
                                    ->label('Graduated At')
                                    ->dateTime('M j, Y')
                                    ->placeholder('—')
                                    ->visible(fn ($record) => $record->isStudent()),

                                TextEntry::make('disqualified_at')
                                    ->label('Disqualified At')
                                    ->dateTime('M j, Y')
                                    ->placeholder('—')
                                    ->color(fn ($state) => $state ? 'danger' : null)
                                    ->visible(fn ($record) => $record->isStudent()),

                                TextEntry::make('disqualification_reason')
                                    ->label('Disqualification Reason')
                                    ->placeholder('—')
                                    ->color('danger')
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->isStudent() && $record->isDisqualified()),

                                TextEntry::make('created_at')
                                    ->label('Registered')
                                    ->dateTime('M j, Y'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->since(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
