<?php

namespace App\Filament\Observer\Resources\ChurchResource\Pages;

use App\Filament\Observer\Resources\ChurchResource;
use Filament\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewChurch extends ViewRecord
{
    protected static string $resource = ChurchResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header Section
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Church Name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('district.name')
                            ->label('District')
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-m-map-pin'),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Church Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->size(Infolists\Components\IconEntry\IconEntrySize::Large),

                        Infolists\Components\TextEntry::make('users_count')
                            ->label('Total Members')
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-m-users')
                            ->state(fn($record) => $record->users()->count()),

                        Infolists\Components\TextEntry::make('active_users_count')
                            ->label('Active Members')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn($state) => number_format($state ?? 0))
                            ->icon('heroicon-m-check-circle')
                            ->getStateUsing(fn($record) => $record->users()->where('is_active', true)->count()),

                        Infolists\Components\TextEntry::make('recent_members_count')
                            ->label('New Members (30 days)')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn($state) => number_format($state ?? 0))
                            ->icon('heroicon-m-plus-circle')
                            ->getStateUsing(fn($record) => $record->users()->where('created_at', '>=', now()->subDays(5))->count()),
                    ])->columns(3)
                    ->headerActions([
                        Action::make('viewDistrict')
                            ->label('View District')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->color('gray')
                            ->url(fn($record) => $record->district ?
                                route('filament.observer.resources.districts.view', $record->district) : null)
                            ->visible(fn($record) => $record->district !== null),
                    ]),

                // Members Section
                Infolists\Components\Section::make('Recent Members')
                    ->description('Latest members who joined this church')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('users')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->weight(FontWeight::Bold),
                                Infolists\Components\TextEntry::make('email')
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('role.name')
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Joined')
                                    ->date()
                                    ->color('gray'),
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ])
                            ->columns(5)
                            ->grid(1)
                            ->getStateUsing(fn($record) => $record->users()->latest()->limit(5)->get())
                            ->visible(fn($record) => $record->users()->exists()),

                        Infolists\Components\TextEntry::make('no_members')
                            ->label('')
                            ->formatStateUsing(fn() => 'No members found for this church.')
                            ->color('gray')
                            ->visible(fn($record) => !$record->users()->exists()),
                    ])
                    ->icon('heroicon-o-users')
                    ->headerActions([
                        Action::make('viewAllMembers')
                            ->label('View All Members')
                            ->icon('heroicon-m-arrow-right')
                            ->color('primary')
                            ->url(fn($record) => route('filament.admin.resources.users.index', [
                                'tableFilters[church][value]' => $record->id
                            ]))
                            ->visible(fn($record) => $record->users()->exists()),
                    ])
                    ->collapsible(),

                // System Information Section
                Infolists\Components\Section::make('System Information')
                    ->description('Technical details and timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime()
                            ->icon('heroicon-m-calendar-days'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since()
                            ->icon('heroicon-m-clock'),

                        Infolists\Components\TextEntry::make('deleted_at')
                            ->label('Deleted At')
                            ->dateTime()
                            ->placeholder('Not deleted')
                            ->visible(fn($record) => $record->trashed())
                            ->color('danger')
                            ->icon('heroicon-m-trash'),

                        Infolists\Components\TextEntry::make('district.created_at')
                            ->label('District Created')
                            ->dateTime()
                            ->visible(fn($record) => $record->district !== null)
                            ->color('gray')
                            ->icon('heroicon-m-calendar'),
                    ])->columns(3)->visible(fn($record) => $record->trashed() || $record->district !== null)
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(),
            ]);
    }
}
