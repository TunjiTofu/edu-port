<?php

namespace App\Filament\Resources\StudentResultsResource\Pages;

use App\Filament\Resources\StudentResultsResource;
use App\Models\User;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentResultsExport;

class ViewStudentResultDetails extends Page
{
    protected static string $resource = StudentResultsResource::class;

    protected static string $view = 'filament.student.pages.view-student-result-details';

    protected static bool $shouldRegisterNavigation = false;

    public User $record;

    public array $studentData;

    public function mount(User $record): void
    {
        $this->record = $record->load([
            'church',
            'district',
            'submissions.task.section',
            'submissions.review'
        ]);

        $this->studentData = StudentResultsResource::getStudentDetailedData($this->record);
    }

    public function getTitle(): string
    {
        return "Result Details: {$this->record->name}";
    }

    public function getHeading(): string
    {
        return "Detailed Result for {$this->record->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Results')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(StudentResultsResource::getUrl('index')),

            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $pdf = Pdf::loadView('pdf.student-result', $this->studentData);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'student-result-' . $this->record->id . '-' . now()->format('Y-m-d') . '.pdf');
                }),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new StudentResultsExport([$this->studentData]),
                        'student-result-' . $this->record->id . '-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}
