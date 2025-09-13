<?php

namespace App\Filament\Resources\RubricResource\Pages;

use App\Filament\Resources\RubricResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRubric extends CreateRecord
{
    protected static string $resource = RubricResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-increment order_index if not provided
        if (!isset($data['order_index']) || !$data['order_index']) {
            $data['order_index'] = \App\Models\Rubric::where('task_id', $data['task_id'])->max('order_index') + 1;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
