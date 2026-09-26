<x-filament-panels::page>
    @php
        $task      = $this->record->task;
        $student   = $this->record->student;
        $review    = $this->record->review;
        $extension = strtolower(pathinfo($this->record->file_name ?? '', PATHINFO_EXTENSION));
        $isPdf     = $extension === 'pdf';
        $cacheBuster  = $this->record->submitted_at?->timestamp ?? now()->timestamp;
        $fileViewUrl  = route('admin.submissions.file', $this->record) . '?v=' . $cacheBuster;
        $fileDownUrl  = route('admin.submissions.file', $this->record) . '?download=1&v=' . $cacheBuster;
    @endphp

    {{-- ── Breadcrumb context ──────────────────────────────────────────────── --}}
    <div class="mb-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('filament.admin.resources.submissions.index') }}"
           class="hover:text-gray-700 dark:hover:text-gray-200">Submissions</a>
        <span>›</span>
        <span class="text-gray-700 dark:text-gray-200 font-medium">Admin Review</span>
    </div>

    {{-- ── Admin notice banner ─────────────────────────────────────────────── --}}
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30
                px-4 py-3 flex items-start gap-3">
        <span class="text-xl flex-shrink-0">⚠️</span>
        <div class="text-sm text-amber-800 dark:text-amber-300">
            <strong>Admin Review Mode.</strong>
            Changes you make will overwrite the reviewer's score and comments.
            The original values are preserved in the audit trail.
            Your modification note is <em>internal only</em> — it is not shown to the candidate or reviewer.
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

        {{-- ═══════════════ LEFT — Candidate & File ═══════════════════════════ --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Candidate card --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/50
                                    flex items-center justify-center font-bold text-primary-700 dark:text-primary-300">
                            {{ strtoupper(substr($student?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $student?->name }}</div>
                            <div class="text-xs text-gray-500">{{ $student?->email }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500 mb-0.5">Church</div>
                            <div class="font-medium text-gray-800 dark:text-gray-200">{{ $student?->church?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-0.5">District</div>
                            <div class="font-medium text-gray-800 dark:text-gray-200">{{ $student?->district?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-0.5">Program</div>
                            <div class="font-medium text-gray-800 dark:text-gray-200">
                                {{ $task?->section?->trainingProgram?->name ?? '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-0.5">Submitted</div>
                            <div class="font-medium text-gray-800 dark:text-gray-200">
                                {{ $this->record->submitted_at?->format('M j, Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reviewer info --}}
                @if ($review?->reviewer)
                    <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 bg-gray-50 dark:bg-gray-800/50">
                        <div class="text-xs text-gray-500 mb-1">Assigned Reviewer</div>
                        <div class="text-sm font-medium text-gray-500">
                            {{ $review->reviewer->name }}
                        </div>
                        @if ($review->reviewed_at)
                            <div class="text-xs text-gray-500 mt-0.5">
                                Reviewed {{ $review->reviewed_at->format('M j, Y g:i A') }}
                            </div>
                        @else
                            <div class="text-xs text-amber-500 mt-0.5">Not yet reviewed</div>
                        @endif
                    </div>
                @else
                    <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 bg-gray-50 dark:bg-gray-800/50">
                        <div class="text-xs text-amber-600 dark:text-amber-400">No reviewer assigned</div>
                    </div>
                @endif
            </div>

            {{-- Task info --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-900 shadow-sm p-5">
                <h3 class="font-semibold text-sm mb-2 flex items-center gap-2">
                    <span>📋</span> Task
                </h3>
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                    {{ $task?->title ?? '—' }}
                </div>
                @if ($task?->description)
                    <div class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed prose prose-sm max-w-none">
                        {!! $task->description !!}
                    </div>
                @endif
            </div>

            {{-- Candidate note --}}
            @if ($this->record->student_notes)
                <div class="rounded-2xl border border-sky-100 dark:border-sky-900/50
                            bg-sky-50/70 dark:bg-sky-950/20 p-5">
                    <h3 class="font-semibold text-sm mb-2 text-sky-900 dark:text-sky-200">
                        💬 Candidate's Note
                    </h3>
                    <p class="text-sm text-sky-800/80 dark:text-sky-300/80 italic">
                        "{{ $this->record->student_notes }}"
                    </p>
                </div>
            @endif

            {{-- Submitted file --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-900 shadow-sm p-5">
                <h3 class="font-semibold text-sm mb-4 flex items-center gap-2">
                    <span>📎</span> Submitted File
                </h3>

                @if ($isPdf)
                    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 mb-4"
                         style="height: 480px;">
                        <iframe src="{{ $fileViewUrl }}" class="w-full h-full" title="Submission PDF"></iframe>
                    </div>
                @endif

                <a href="{{ $fileDownUrl }}" target="_blank" rel="noopener"
                   class="flex items-center justify-between gap-3 p-4 rounded-xl
                          bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700
                          transition-colors group">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-2xl flex-shrink-0">{{ $isPdf ? '📄' : '📁' }}</span>
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate text-gray-900 dark:text-gray-100">
                                {{ $this->record->file_name }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ number_format(($this->record->file_size ?? 0) / 1024, 1) }} KB
                            </div>
                        </div>
                    </div>
                    <span class="text-primary-600 text-sm font-medium whitespace-nowrap flex-shrink-0">
                        {{ $isPdf ? 'Open ↗' : 'Download ↗' }}
                    </span>
                </a>
            </div>
        </div>

        {{-- ═══════════════ RIGHT — Review Form ══════════════════════════════ --}}
        <div class="lg:col-span-3">

            {{-- Original reviewer scores (for reference) --}}
            @if ($review && ($review->original_score !== null || $review->admin_modified_by))
                <div class="mb-4 rounded-xl border border-violet-200 dark:border-violet-800
                            bg-violet-50 dark:bg-violet-950/30 px-4 py-3">
                    <div class="text-xs font-semibold text-violet-700 dark:text-violet-300 mb-1">
                        🔍 Original Reviewer Values (before admin modification)
                    </div>
                    <div class="text-sm text-violet-700 dark:text-violet-300 space-y-0.5">
                        <div>Score: <strong>{{ $review->original_score ?? '—' }}</strong></div>
                        <div>Comments: {{ Str::limit($review->original_comments ?? '—', 120) }}</div>
                    </div>
                </div>
            @endif

            {{-- The form --}}
            <form wire:submit.prevent="save">
                {{ $this->form }}

                {{-- Action bar --}}
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <x-filament::button
                        wire:click="save"
                        color="primary"
                        icon="heroicon-o-check-circle"
                        class="flex-1"
                    >
                        Save Changes
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.admin.resources.submissions.index') }}"
                        color="gray"
                        outlined
                        icon="heroicon-o-arrow-left"
                        class="flex-1"
                    >
                        Back to Submissions
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
