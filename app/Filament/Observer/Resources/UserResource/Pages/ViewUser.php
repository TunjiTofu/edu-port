<?php

namespace App\Filament\Observer\Resources\UserResource\Pages;

use App\Enums\RoleTypes;
use App\Filament\Observer\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Exceptions\Halt;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('name')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),

                        TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        TextEntry::make('phone')
                            ->icon('heroicon-m-phone')
                            ->placeholder('No phone number')
                            ->copyable(),
                    ])
                    ->columns(3),

                Section::make('Role & Access')
                    ->schema([
                        TextEntry::make('role.name')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'student' => 'primary',
                                'reviewer' => 'success',
                                'observer' => 'warning',
                                'supervisor' => 'danger',
                                'admin' => 'secondary',
                                default => 'gray',
                            }),

                        IconEntry::make('is_active')
                            ->label('Active Status')
                            ->boolean(),

                        TextEntry::make('district.name')
                            ->label('District')
                            ->placeholder('No district assigned'),

                        // TextEntry::make('trainingProgram.name')
                        //     ->label('Training Program')
                        //     ->placeholder('No training program assigned')
                        //     ->visible(fn ($record) => $record && $record->role === 'student'),
                    ])
                    ->columns(3),

                Section::make('Account Information')
                    ->schema([
                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime('M j, Y \a\t H:i')
                            ->placeholder('Email not verified'),

                        TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime('M j, Y \a\t H:i'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M j, Y \a\t H:i'),
                    ])
                    ->columns(3),

                Section::make('System Information')
                    ->schema([
                        TextEntry::make('id')
                            ->label('User ID'),


                        TextEntry::make('role.permissions')
                            ->label('Permissions')
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
