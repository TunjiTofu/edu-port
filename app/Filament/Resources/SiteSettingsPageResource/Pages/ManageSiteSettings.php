<?php

namespace App\Filament\Resources\SiteSettingsPageResource\Pages;

use App\Filament\Resources\SiteSettingsPageResource;
use App\Models\SiteSetting;
use App\Models\TrainingProgram;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;

class ManageSiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SiteSettingsPageResource::class;

    protected static string $view = 'filament.components.manage-site-settings';

    public ?string $registration_deadline = null;
    public bool    $registration_open     = true;

    public function mount(): void
    {
        $this->registration_open     = SiteSetting::get('registration_open', '1') === '1';
        $this->registration_deadline = SiteSetting::get('registration_deadline');

        $this->form->fill([
            'registration_open'     => $this->registration_open,
            'registration_deadline' => $this->registration_deadline,
        ]);
    }

    // ── Header actions ─────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clone_program')
                ->label('Clone Training Program')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->modalHeading('Clone Training Program')
                ->modalDescription(
                    'This creates a new training program by copying all sections, tasks, and rubrics ' .
                    'from an existing program. The new program will be INACTIVE until you activate it ' .
                    'in Training Programs. Existing submissions and enrollments are never affected.'
                )
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Select::make('source_program_id')
                        ->label('Source Program (to clone from)')
                        ->options(
                            TrainingProgram::orderByDesc('year')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($p) =>
                                [$p->id => ($p->year ? "[{$p->year}] " : '') . $p->name]
                                )
                        )
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            // Auto-suggest the next year as the new program year
                            $source = TrainingProgram::find($state);
                            if ($source) {
                                $nextYear = ($source->year ?? now()->year) + 1;
                                $set('new_year', $nextYear);
                                $set('new_name', str_replace(
                                    (string) ($source->year ?? ''),
                                    (string) $nextYear,
                                    $source->name
                                ));
                            }
                        })
                        ->helperText('Choose the program whose sections, tasks, and rubrics you want to copy.'),

                    Forms\Components\TextInput::make('new_name')
                        ->label('New Program Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. MG Training Programme 2026')
                        ->helperText('Give the new program a clear name that includes the year.'),

                    Forms\Components\TextInput::make('new_year')
                        ->label('Program Year')
                        ->numeric()
                        ->required()
                        ->minValue(2020)
                        ->maxValue(2100)
                        ->default(now()->year)
                        ->helperText('The cohort year for this program (e.g. 2026).'),

                    Forms\Components\Placeholder::make('clone_note')
                        ->label('')
                        ->content(
                            'ℹ️ The clone creates a new program record with its own sections, tasks, and rubrics. ' .
                            'Existing candidate submissions and enrollments are untouched. ' .
                            'You will need to activate the new program and set its dates before candidates can enrol.'
                        ),
                ])
                ->modalSubmitActionLabel('Clone Program')
                ->action(function (array $data) {
                    $source = TrainingProgram::with('sections.tasks.rubrics')
                        ->find($data['source_program_id']);

                    if (! $source) {
                        Notification::make()
                            ->title('Source program not found.')
                            ->danger()->send();
                        return;
                    }

                    try {
                        $newProgram = $source->cloneTo($data['new_name'], (int) $data['new_year']);

                        Log::info('Admin: program cloned via settings page', [
                            'event'      => 'admin_program_clone',
                            'admin_id'   => auth()->id(),
                            'source_id'  => $source->id,
                            'new_id'     => $newProgram->id,
                            'new_name'   => $newProgram->name,
                            'new_year'   => $newProgram->year,
                        ]);

                        Notification::make()
                            ->title('Program Cloned Successfully')
                            ->body(
                                "'{$newProgram->name}' has been created (ID: {$newProgram->id}) " .
                                "with all sections, tasks, and rubrics copied. " .
                                "It is currently INACTIVE — go to Training Programs to activate it and set its dates."
                            )
                            ->success()
                            ->persistent()
                            ->send();

                    } catch (\Exception $e) {
                        Log::error('Admin: program clone failed', [
                            'event'     => 'admin_program_clone_failed',
                            'admin_id'  => auth()->id(),
                            'source_id' => $data['source_program_id'],
                            'error'     => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Clone Failed')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()->send();
                    }
                }),
        ];
    }

    // ── Registration settings form ─────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Candidate Registration Window')
                    ->description(
                        'Control when candidates can self-register. ' .
                        'Changes take effect immediately — no cache clear needed.'
                    )
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Forms\Components\Toggle::make('registration_open')
                            ->label('Registration Open')
                            ->helperText('Switch OFF to close registration immediately, regardless of the deadline.')
                            ->default(true)
                            ->live(),

                        Forms\Components\DatePicker::make('registration_deadline')
                            ->label('Registration Deadline (Last Day)')
                            ->helperText(
                                'Candidates can register ON this date. Starting the next day, registration closes automatically. ' .
                                'Leave blank to keep open until manually switched off.'
                            )
                            ->displayFormat('M j, Y')
                            ->native(false)
                            ->minDate(now()->toDateString())
                            ->visible(fn (Forms\Get $get) => (bool) $get('registration_open'))
                            ->nullable(),
                    ])
                    ->columns(2),
            ])
            ->statePath('');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $open     = (bool) ($data['registration_open'] ?? true);
        $deadline = $data['registration_deadline']     ?? null;

        SiteSetting::set('registration_open',     $open ? '1' : '0');
        SiteSetting::set('registration_deadline', $deadline);

        $this->registration_open     = $open;
        $this->registration_deadline = $deadline;

        $body = match (true) {
            ! $open          => 'Registration is now manually closed.',
            (bool) $deadline => 'Registration is open through '
                . Carbon::parse($deadline)->format('l, F j, Y')
                . '. Closes automatically from '
                . Carbon::parse($deadline)->addDay()->format('F j, Y') . '.',
            default          => 'Registration is open with no deadline.',
        };

        Notification::make()->title('Settings Saved')->body($body)->success()->send();
    }

    // Blade helpers
    public function registrationIsOpen(): bool { return SiteSetting::isRegistrationOpen(); }
    public function getDeadline(): ?string      { return SiteSetting::get('registration_deadline'); }
    public function getOpenFlag(): string       { return SiteSetting::get('registration_open', '1'); }
}
