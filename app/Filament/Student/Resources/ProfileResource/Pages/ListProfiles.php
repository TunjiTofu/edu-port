<?php

namespace App\Filament\Student\Resources\ProfileResource\Pages;

use App\Filament\Student\Resources\ProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit_profile')
                ->label('Edit Profile')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->url(fn() => static::getResource()::getUrl('edit', ['record' => Auth::id()])),
        ];
    }

    public function getTitle(): string
    {
        return 'My Profile';
    }
}
