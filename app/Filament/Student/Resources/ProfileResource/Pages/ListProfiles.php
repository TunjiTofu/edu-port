<?php

namespace App\Filament\Student\Resources\ProfileResource\Pages;

use App\Filament\Student\Resources\ProfileResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    /**
     * Skip the table entirely — redirect straight to the edit form.
     * This gives candidates a single-page profile experience:
     * click "My Profile" → land directly on the editable form.
     */
    public function mount(): void
    {
        $this->redirect(
            static::getResource()::getUrl('edit', ['record' => Auth::id()])
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
