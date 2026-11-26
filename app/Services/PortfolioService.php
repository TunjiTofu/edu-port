<?php

namespace App\Services;

use App\Enums\SubmissionTypes;
use App\Models\ProgramEnrollment;
use App\Models\Section;
use App\Models\Submission;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the data for a candidate's printable portfolio PDF:
 * a front page of candidate details, then every COMPLETED task
 * (submission status = completed) in one training program with its score,
 * the reviewer's comment and whether it was resubmitted.
 *
 * Results are shown whether or not they have been published.
 */
class PortfolioService
{
    /**
     * Programs the candidate is enrolled in, for the program picker.
     *
     * @return array<int, string> program id => label
     */
    public function programOptions(User $student): array
    {
        return ProgramEnrollment::query()
            ->where('student_id', $student->id)
            ->whereHas('trainingProgram')
            ->with('trainingProgram')
            ->get()
            ->sortByDesc(fn ($e) => $e->trainingProgram->year ?? 0)
            ->mapWithKeys(fn ($e) => [
                $e->training_program_id => $this->programLabel($e->trainingProgram),
            ])
            ->all();
    }

    /** "Name (Year)", without repeating the year when the name already has it. */
    public function programLabel(TrainingProgram $program): string
    {
        $year = $program->year;

        return ($year && ! str_contains($program->name, (string) $year))
            ? "{$program->name} ({$year})"
            : $program->name;
    }

    /**
     * Full view data for resources/views/pdf/portfolio.blade.php.
     * Returns null when the candidate is not enrolled in the program,
     * so a candidate can never print another program's data.
     */
    public function build(User $student, int $programId, bool $withMedia = true): ?array
    {
        $enrollment = ProgramEnrollment::query()
            ->where('student_id', $student->id)
            ->where('training_program_id', $programId)
            ->with('trainingProgram')
            ->first();

        if (! $enrollment || ! $enrollment->trainingProgram) {
            return null;
        }

        /** @var TrainingProgram $program */
        $program = $enrollment->trainingProgram;
        $student->loadMissing(['church', 'district']);

        $sections = Section::query()
            ->where('training_program_id', $program->id)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->with(['tasks' => fn ($q) => $q->where('is_active', true)->orderBy('order_index')])
            ->get();

        $taskIds = $sections->flatMap(fn ($s) => $s->tasks->pluck('id'));

        $submissions = Submission::query()
            ->where('student_id', $student->id)
            ->whereIn('task_id', $taskIds)
            ->with('review')
            ->get()
            ->keyBy('task_id');

        $sectionRows = [];
        $totals = ['tasks' => 0, 'submitted' => 0, 'completed' => 0, 'resubmitted' => 0, 'score' => 0.0, 'max' => 0.0];

        foreach ($sections as $section) {
            $rows = [];

            foreach ($section->tasks as $task) {
                $totals['tasks']++;
                $submission = $submissions->get($task->id);

                if (! $submission) {
                    continue;
                }

                $totals['submitted']++;

                // The portfolio lists completed (fully reviewed) tasks only;
                // awaiting-review and needs-revision work is left out.
                if ($submission->status !== SubmissionTypes::COMPLETED->value) {
                    continue;
                }

                $review   = $submission->review;
                $reviewed = $review && $review->score !== null;

                $totals['completed']++;
                if ($submission->is_resubmission) {
                    $totals['resubmitted']++;
                }
                if ($reviewed) {
                    $totals['score'] += (float) $review->score;
                    $totals['max']   += (float) $task->max_score;
                }

                $rows[] = [
                    'section'       => $section->name,
                    'title'         => $task->title,
                    // Stored location of the candidate's uploaded PDF (merged in by PortfolioPdfBuilder)
                    'file_path'     => trim($submission->file_path, '/') . '/' . $submission->file_name,
                    'file_name'     => $submission->file_name,
                    'submitted_at'  => $submission->submitted_at?->format('M d, Y g:i A'),
                    'status'        => $this->statusLabel($submission->status),
                    'resubmitted'   => (bool) $submission->is_resubmission,
                    'reviewed'      => $reviewed,
                    'score'         => $reviewed ? (float) $review->score : null,
                    'max_score'     => (float) $task->max_score,
                    'comments'      => $reviewed ? trim((string) $review->comments) : null,
                ];
            }

            if ($rows) {
                $sectionRows[] = ['name' => $section->name, 'tasks' => $rows];
            }
        }

        return [
            'student' => [
                'name'      => $student->name,
                'email'     => $student->email,
                'phone'     => $student->phone,
                'church'    => $student->church?->name,
                'district'  => $student->district?->name,
                'mg_mentor' => $student->mg_mentor,
                'photo'     => $withMedia ? $this->photoDataUri($student) : null,
            ],
            'program' => [
                'id'          => $program->id,
                'name'        => $program->name,
                'label'       => $this->programLabel($program),
                'code'        => $program->code,
                'year'        => $program->year,
                'start_date'  => $program->start_date?->format('M d, Y'),
                'end_date'    => $program->end_date?->format('M d, Y'),
                'enrolled_at' => $enrollment->enrolled_at?->format('M d, Y'),
                'status'      => $enrollment->status instanceof \BackedEnum
                    ? $enrollment->status->value
                    : $enrollment->status,
            ],
            'summary' => [
                'total_tasks' => $totals['tasks'],
                'submitted'   => $totals['submitted'],
                'completed'   => $totals['completed'],
                'resubmitted' => $totals['resubmitted'],
                'total_score' => $totals['score'],
                'max_score'   => $totals['max'],
                'percentage'  => $totals['max'] > 0 ? round($totals['score'] / $totals['max'] * 100, 1) : null,
            ],
            'sections'     => $sectionRows,
            'logo'         => $withMedia ? $this->logoDataUri() : null,
            'header_year'  => $program->year ?: now()->year,
            'generated_at' => now()->format('F d, Y g:i A'),
        ];
    }

    public function filename(User $student, array $data): string
    {
        $slug = str($student->name)->slug();
        $prog = str($data['program']['name'])->slug();

        return "portfolio-{$slug}-{$prog}-" . now()->format('Y-m-d') . '.pdf';
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            SubmissionTypes::PENDING_REVIEW->value => 'Awaiting Review',
            SubmissionTypes::UNDER_REVIEW->value   => 'Under Review',
            SubmissionTypes::COMPLETED->value      => 'Reviewed',
            SubmissionTypes::NEEDS_REVISION->value => 'Needs Revision',
            SubmissionTypes::FLAGGED->value        => 'Flagged',
            SubmissionTypes::SUBMITTED->value      => 'Submitted',
            default                                => str($status ?? 'Unknown')->replace('_', ' ')->title()->toString(),
        };
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('images/logo.png');

        return is_file($path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($path)) : null;
    }

    /**
     * Embed the passport photo as a data URI so dompdf never has to fetch
     * a URL (temporary S3 links and remote fetching are unreliable in PDFs).
     */
    private function photoDataUri(User $student): ?string
    {
        if (! $student->passport_photo) {
            return null;
        }

        try {
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            $storage = Storage::disk($disk);

            if (! $storage->exists($student->passport_photo)) {
                return null;
            }

            $mime = $storage->mimeType($student->passport_photo) ?: 'image/jpeg';
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
                return null; // dompdf can't render webp/heic reliably
            }

            return 'data:' . $mime . ';base64,' . base64_encode($storage->get($student->passport_photo));
        } catch (\Throwable $e) {
            Log::warning('Portfolio: passport photo could not be embedded', [
                'user_id' => $student->id,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }
}
