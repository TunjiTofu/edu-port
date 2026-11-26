<?php

namespace App\Filament\Student\Pages;

use App\Services\PortfolioService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * Lets a candidate pick a program and download their portfolio PDF.
 *
 * The PDF itself is produced by PortfolioDownloadController on a plain
 * HTTP route (filament.student.print-portfolio.download), not by a
 * Livewire action: Livewire downloads hold the file in memory several
 * times over and ran out of memory on production.
 */
class PrintPortfolio extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-printer';
    protected static ?string $navigationLabel = 'Print Portfolio';
    protected static ?string $title           = 'Print Portfolio';
    protected static ?string $slug            = 'print-portfolio';
    protected static ?int    $navigationSort  = 90;
    // No $navigationGroup: shows as a standalone menu item.

    protected static string $view = 'filament.student.pages.print-portfolio';

    public ?array $data = [];

    /** @var array<int, string> */
    public array $programs = [];

    public function mount(PortfolioService $service): void
    {
        $this->programs = $service->programOptions(Auth::user());

        $this->form->fill(['program_id' => array_key_first($this->programs)]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('program_id')
                    ->label('Training Program')
                    ->options(fn () => $this->programs)
                    ->required()
                    ->native(false)
                    ->live(),
            ])
            ->statePath('data');
    }

    /**
     * Summary for the selected program, recalculated on every render.
     * Deliberately NOT a public property: stored Livewire state outlives
     * deploys, so a tab opened before a code change would render an old shape.
     */
    #[Computed]
    public function summary(): ?array
    {
        $programId = $this->selectedProgramId();

        return $programId
            ? app(PortfolioService::class)->build(Auth::user(), $programId, withMedia: false)['summary'] ?? null
            : null;
    }

    #[Computed]
    public function downloadUrl(): ?string
    {
        $programId = $this->selectedProgramId();

        return $programId
            ? route('filament.student.print-portfolio.download', ['program' => $programId])
            : null;
    }

    private function selectedProgramId(): int
    {
        $id = (int) ($this->data['program_id'] ?? 0);

        // Only programs offered in the picker (the candidate's own enrolments).
        return array_key_exists($id, $this->programs) ? $id : 0;
    }
}
