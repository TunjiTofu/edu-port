<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getEloquentQuery()
            ->join('sections', 'tasks.section_id', '=', 'sections.id')
            ->select('tasks.*', 'sections.order_index as section_order_index')
            ->orderBy('sections.order_index', 'asc')
            ->orderBy('tasks.order_index', 'asc');
    }
}
