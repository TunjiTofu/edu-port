<?php

namespace App\Filament\Student\Pages;

use App\Services\PortfolioPdfBuilder;
use App\Services\PortfolioService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;

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
        $programId = (int) ($this->data['program_id'] ?? 0);

        return $programId
            ? app(PortfolioService::class)->build(Auth::user(), $programId, withMedia: false)['summary'] ?? null
            : null;
    }

    public function download(PortfolioService $service)
    {
        $state = $this->form->getState();
        $user  = Auth::user();
        $data  = $service->build($user, (int) $state['program_id']);

        if (! $data) {
            Notification::make()
                ->title('Program not found')
                ->body('You are not enrolled in the selected program.')
                ->danger()
                ->send();

            return null;
        }

        if ($data['summary']['completed'] === 0) {
            Notification::make()
                ->title('Nothing to print yet')
                ->body('None of your tasks in this program have been completed yet. Your portfolio includes completed tasks only.')
                ->warning()
                ->send();

            return null;
        }

        Log::info('Portfolio: PDF downloaded', [
            'event'      => 'portfolio_download',
            'user_id'    => $user->id,
            'program_id' => (int) $state['program_id'],
        ]);

        @set_time_limit(180); // merging several uploaded PDFs can take a while

        try {
            $bytes = app(PortfolioPdfBuilder::class)->build($data);
        } catch (\Throwable $e) {
            Log::error('Portfolio: PDF build failed', [
                'event'      => 'portfolio_build_failed',
                'user_id'    => $user->id,
                'program_id' => (int) $state['program_id'],
                'error'      => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Could not create your portfolio')
                ->body('Something went wrong while preparing the PDF. Please try again, or contact the administrator if it keeps happening.')
                ->danger()
                ->send();

            return null;
        }

        return response()->streamDownload(
            fn () => print($bytes),
            $service->filename($user, $data),
            ['Content-Type' => 'application/pdf'],
        );
    }
}
