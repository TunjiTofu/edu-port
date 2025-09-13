<?php

namespace App\Filament\Observer\Resources\DistrictResource\Pages;

use App\Filament\Observer\Resources\DistrictResource;
use App\Services\Utility\Constants;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDistrict extends ViewRecord
{
    protected static string $resource = DistrictResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('District Information')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label('District Name')
                            ->size('lg')
                            ->weight('bold'),

                        \Filament\Infolists\Components\TextEntry::make('code')
                            ->label('District Code')
                            ->badge()
                            ->color('primary'),

                        \Filament\Infolists\Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),

                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])->columns(3),

                \Filament\Infolists\Components\Section::make(fn($record) => "User Statistics Overview for {$record->name}")
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('total_users')
                            ->label('Total Users')
                            ->badge()
                            ->color('info')
                            ->state(fn($record) => $record->users()->count()),

                        \Filament\Infolists\Components\TextEntry::make('active_users')
                            ->label('Active Users')
                            ->badge()
                            ->color('success')
                            ->state(fn($record) => $record->users()->where('is_active', true)->count()),

                        \Filament\Infolists\Components\TextEntry::make('inactive_users')
                            ->label('Inactive Users')
                            ->badge()
                            ->color('danger')
                            ->state(fn($record) => $record->users()->where('is_active', false)->count()),
                    ])->columns(3),

                \Filament\Infolists\Components\Section::make(fn($record) => "Users by Role for {$record->name}")
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(2)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('admin_users')
                                    ->label('👨‍💼 Administrators')
                                    ->badge()
                                    ->color('danger')
                                    ->state(fn($record) => $record->users()->where('role_id', Constants::ADMIN_ID)->count())
                                    ->suffix(
                                        fn($record) =>
                                        ' (' . $record->users()->where('role_id', Constants::ADMIN_ID)->where('is_active', true)->count() . ' active)'
                                    ),

                                \Filament\Infolists\Components\TextEntry::make('reviewer_users')
                                    ->label('👀 Reviewers')
                                    ->badge()
                                    ->color('info')
                                    ->state(fn($record) => $record->users()->where('role_id', Constants::REVIEWER_ID)->count())
                                    ->suffix(
                                        fn($record) =>
                                        ' (' . $record->users()->where('role_id', Constants::REVIEWER_ID)->where('is_active', true)->count() . ' active)'
                                    ),

                                \Filament\Infolists\Components\TextEntry::make('observer_users')
                                    ->label('🔍 Observers')
                                    ->badge()
                                    ->color('warning')
                                    ->state(fn($record) => $record->users()->where('role_id', Constants::OBSERVER_ID)->count())
                                    ->suffix(
                                        fn($record) =>
                                        ' (' . $record->users()->where('role_id', Constants::OBSERVER_ID)->where('is_active', true)->count() . ' active)'
                                    ),

                                \Filament\Infolists\Components\TextEntry::make('student_users')
                                    ->label('🎓 Students')
                                    ->badge()
                                    ->color('success')
                                    ->state(fn($record) => $record->users()->where('role_id', Constants::STUDENT_ID)->count())
                                    ->suffix(
                                        fn($record) =>
                                        ' (' . $record->users()->where('role_id', Constants::STUDENT_ID)->where('is_active', true)->count() . ' active)'
                                    ),

                                \Filament\Infolists\Components\TextEntry::make('other_users')
                                    ->label('❓ Other Roles')
                                    ->badge()
                                    ->color('gray')
                                    ->state(fn($record) => $record->users()->whereNotIn('role_id', [Constants::ADMIN_ID, Constants::REVIEWER_ID, Constants::OBSERVER_ID, Constants::STUDENT_ID])->count())
                                // ->visible(fn($record) => $record->users()->whereNotIn('role', ['admin', 'reviewer', 'observer', 'student'])->count() > 0),
                            ])->columns(5),
                    ]),

                // System Information Section
                \Filament\Infolists\Components\Section::make('System Information')
                    ->description('Technical details and timestamps')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime()
                            ->icon('heroicon-m-calendar-days'),

                        \Filament\Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since()
                            ->icon('heroicon-m-clock'),

                        \Filament\Infolists\Components\TextEntry::make('deleted_at')
                            ->label('Deleted At')
                            ->dateTime()
                            ->placeholder('Not deleted')
                            ->visible(fn($record) => $record->trashed())
                            ->color('danger')
                            ->icon('heroicon-m-trash'),
                    ])->columns(3)
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(),
            ]);
    }
}
