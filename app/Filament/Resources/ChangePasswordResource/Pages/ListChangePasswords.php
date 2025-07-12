<?php

namespace App\Filament\Resources\ChangePasswordResource\Pages;

use App\Filament\Resources\ChangePasswordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListChangePasswords extends ListRecords
{
    protected static string $resource = ChangePasswordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_my_password')
                ->label('Change My Password')
                ->icon('heroicon-o-key')
                ->color('primary')
                ->url(fn () => ChangePasswordResource::getUrl('edit', ['record' => Auth::id()]))
                ->visible(fn () => Auth::check()),
        ];
    }

    public function getTitle(): string
    {
        return Auth::user()?->isAdmin() ? 'Manage User Passwords' : 'Change My Password';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add widgets here if needed
        ];
    }
}
