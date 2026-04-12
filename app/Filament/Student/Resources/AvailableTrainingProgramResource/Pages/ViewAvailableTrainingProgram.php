<?php

namespace App\Filament\Student\Resources\AvailableTrainingProgramResource\Pages;

use App\Filament\Student\Resources\AvailableTrainingProgramResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAvailableTrainingProgram extends ViewRecord
{
    protected static string $resource = AvailableTrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('enroll')
                ->label('Enroll in Program')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Enroll in Program')
                ->modalDescription(fn () => "Enroll in '{$this->record->name}'? It will be added to your learning dashboard.")
                ->modalSubmitActionLabel('Enroll Now')
                ->action(fn () => AvailableTrainingProgramResource::handleEnroll($this->record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── Hero Section ─────────────────────────────────────────────
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(['default' => 1, 'sm' => 4])
                            ->schema([
                                // FIX: Use image_url accessor so both local and S3 images resolve
                                Infolists\Components\ImageEntry::make('image_url')
                                    ->label('')
                                    ->circular()
                                    ->size(100)
                                    ->defaultImageUrl(asset('images/logo.png'))
                                    ->columnSpan(1)
                                    ->extraImgAttributes(['class' => 'ring-4 ring-green-500/30 shadow-lg mx-auto sm:mx-0']),

                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('name')
                                        ->label('')
                                        ->size('lg')
                                        ->weight('bold'),

                                    Infolists\Components\TextEntry::make('description')
                                        ->label('')
                                        ->prose()
                                        ->markdown()
                                        ->columnSpanFull(),
                                ])->columnSpan(['default' => 1, 'sm' => 3]),
                            ]),
                    ]),

                // ── Key Details ───────────────────────────────────────────────
                Infolists\Components\Section::make('Program Details')
                    ->icon('heroicon-o-information-circle')
                    ->columns(['default' => 2, 'sm' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('duration_weeks')
                            ->label('Duration')
                            ->badge()->color('primary'),

                        Infolists\Components\TextEntry::make('start_date')
                            ->label('Starts')
                            ->date('M j, Y')
                            ->badge()->color('success'),

                        Infolists\Components\TextEntry::make('end_date')
                            ->label('Ends')
                            ->date('M j, Y')
                            ->badge()->color('warning'),

                        Infolists\Components\TextEntry::make('registration_deadline')
                            ->label('Enroll By')
                            ->date('M j, Y')
                            ->badge()
                            ->color(fn ($state) => $state && now()->diffInDays($state, false) <= 7 ? 'danger' : 'info')
                            ->placeholder('No deadline'),

                        Infolists\Components\TextEntry::make('passing_score')
                            ->label('Passing Score')
                            ->suffix('%')
                            ->badge()->color('gray'),

                        Infolists\Components\TextEntry::make('sections_count')
                            ->label('Sections')
                            ->badge()->color('info'),

                        Infolists\Components\TextEntry::make('enrollments_count')
                            ->label('Enrolled')
                            ->getStateUsing(fn ($record) => $record->enrollments()->count())
                            ->badge()->color('success'),

                        Infolists\Components\TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
                    ]),

                // ── Sections List ─────────────────────────────────────────────
                Infolists\Components\Section::make('What You Will Cover')
                    ->icon('heroicon-o-book-open')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('sections')
                            ->schema([
                                Infolists\Components\Grid::make(['default' => 1, 'sm' => 3])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Section')
                                            ->weight('bold'),

                                        Infolists\Components\TextEntry::make('order_index')
                                            ->label('Order')
                                            ->badge()->color('gray'),

                                        Infolists\Components\TextEntry::make('tasks_count')
                                            ->label('Tasks')
                                            ->getStateUsing(fn ($record) => $record->tasks()->count())
                                            ->badge()->color('info'),
                                    ]),

                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description')
                                    ->prose()->markdown()
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => ! empty($record->description)),
                            ])
                            ->contained(true),
                    ]),
            ]);
    }
}
