<x-filament-panels::page>

    @php
        $position  = $this->getQueuePosition();
        $task      = $this->record->task;
        $student   = $this->record->student;
        $locked    = $this->isLocked();

        $extension = strtolower(pathinfo($this->record->file_name ?? '', PATHINFO_EXTENSION));
        $isPdf     = $extension === 'pdf';

        // Secure, permission-checked file route — NOT a public storage URL.
        // Works regardless of APP_URL, storage symlinks, or folder permissions
        // because it streams the file through Laravel.
        $fileViewUrl     = route('reviewer.submissions.file', $this->record);
        $fileDownloadUrl = route('reviewer.submissions.file', ['submission' => $this->record, 'download' => 1]);
    @endphp

    {{-- ── Queue progress bar ── --}}
    @if ($position['total'] > 0)
        <div class="mb-5 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
            @if ($position['position'])
                <span class="font-medium whitespace-nowrap">
                    📋 Reviewing {{ $position['position'] }} of {{ $position['total'] }}
                </span>
                <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden max-w-xs">
                    <div class="h-full bg-green-500 rounded-full transition-all"
                         style="width: {{ ($position['position'] / $position['total']) * 100 }}%"></div>
                </div>
            @else
                <span>✏️ Reviewing a previously-handled submission</span>
            @endif
        </div>
    @endif

    @if ($locked)
        @php
            $modRequest = $this->getModificationRequest();
        @endphp

        @if ($modRequest && $modRequest->isPending())
            <div class="mb-5 p-4 rounded-xl bg-purple-50 border border-purple-200 dark:bg-purple-950/30 dark:border-purple-800 flex items-start gap-3">
                <span class="text-xl">⏳</span>
                <div class="text-sm text-purple-800 dark:text-purple-300 leading-relaxed">
                    <strong>Modification request pending.</strong> Your administrator has been notified
                    and will review your request to edit this completed review.
                </div>
            </div>
        @elseif ($modRequest && $modRequest->isRejected())
            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 dark:bg-red-950/30 dark:border-red-800 flex items-start gap-3">
                <span class="text-xl">❌</span>
                <div class="text-sm text-red-800 dark:text-red-300 leading-relaxed">
                    <strong>Modification request declined.</strong>
                    @if ($modRequest->admin_comments)
                        Admin note: "{{ $modRequest->admin_comments }}"
                    @endif
                </div>
            </div>
        @else
            <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 dark:bg-amber-950/30 dark:border-amber-800 flex items-start gap-3">
                <span class="text-xl">🔒</span>
                <div class="text-sm text-amber-800 dark:text-amber-300 leading-relaxed">
                    <strong>This review is locked.</strong> It has already been completed and submitted.
                    Use the <strong>"Request Modification"</strong> button above if you need to change
                    the score or feedback.
                </div>
            </div>
        @endif
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

        {{-- ═══════════════ LEFT COLUMN — Candidate & Submission Info ═══════════════ --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- ── Candidate card ── --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex items-center gap-4">
                        <img src="{{ $student?->passport_photo_url ?? asset('storage/passport-photos/default-avatar.jpg') }}"
                             onerror="this.onerror=null;this.src='{{ asset('storage/passport-photos/default-avatar.jpg') }}'"
                             class="w-16 h-16 rounded-full object-cover ring-2 ring-sky-400/40 flex-shrink-0"
                             alt="{{ $student?->name }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-semibold text-base text-gray-900 dark:text-gray-100 truncate">
                                    {{ $student?->name }}
                                </div>
                                @php
                                    $statusBadge = match ($this->record->status) {
                                        \App\Enums\SubmissionTypes::PENDING_REVIEW->value => ['🆕', 'New', 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300'],
                                        \App\Enums\SubmissionTypes::UNDER_REVIEW->value   => ['👀', 'In Progress', 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300'],
                                        \App\Enums\SubmissionTypes::COMPLETED->value      => ['✅', 'Completed', 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300'],
                                        \App\Enums\SubmissionTypes::NEEDS_REVISION->value => ['✏️', 'Needs Revision', 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300'],
                                        \App\Enums\SubmissionTypes::FLAGGED->value        => ['🚩', 'Flagged', 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300'],
                                        default => ['📄', $this->record->status, 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300'],
                                    };
                                @endphp
                                <span class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full {{ $statusBadge[2] }}">
                                    {{ $statusBadge[0] }} {{ $statusBadge[1] }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 truncate mt-0.5">{{ $student?->email }}</div>
                        </div>
                    </div>
                </div>

                {{-- Details grid — icon + label/value pairs, 2 columns on larger screens --}}
                <div class="border-t border-gray-100 dark:border-gray-800 px-5 sm:px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-950/40 flex items-center justify-center text-base">
                                🎓
                            </span>
                            <div class="min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Program</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-snug">
                                    {{ $task->section?->trainingProgram?->name ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center text-base">
                                📂
                            </span>
                            <div class="min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Section</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-snug">
                                    {{ $task->section?->name ?? '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-base">
                                📤
                            </span>
                            <div class="min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Submitted</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-snug">
                                    {{ $this->record->submitted_at?->format('M j, Y') ?? '—' }}
                                    <span class="text-gray-400 font-normal">{{ $this->record->submitted_at?->format('· g:i A') }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($task->due_date)
                            <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center text-base">
                                ⏰
                            </span>
                                <div class="min-w-0">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Due Date</div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-snug">
                                        {{ $task->due_date->format('M j, Y') }}
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- ── Task description ── --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-5 sm:p-6">
                <h3 class="font-semibold text-sm mb-3 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                    <span>📝</span> Task Description
                </h3>
                <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed prose prose-sm max-w-none">
                    {!! $task->description ?? '<em>No description provided.</em>' !!}
                </div>
            </div>

            {{-- ── Candidate's note ── --}}
            @if ($this->record->student_notes)
                <div class="rounded-2xl border border-sky-100 dark:border-sky-900/50 bg-sky-50/70 dark:bg-sky-950/20 p-5 sm:p-6">
                    <h3 class="font-semibold text-sm mb-3 flex items-center gap-2 text-sky-900 dark:text-sky-200">
                        <span>💬</span> Candidate's Note
                    </h3>
                    <p class="text-sm text-sky-800/80 dark:text-sky-300/80 leading-relaxed italic">
                        "{{ $this->record->student_notes }}"
                    </p>
                </div>
            @endif

            {{-- ── Submitted file ── --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-5 sm:p-6">
                <h3 class="font-semibold text-sm mb-4 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                    <span>📎</span> Submitted File
                </h3>

                @if ($isPdf)
                    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 mb-4" style="height: 480px;">
                        <iframe src="{{ $fileViewUrl }}" class="w-full h-full" title="Submission preview"></iframe>
                    </div>
                @endif

                <a href="{{ $isPdf ? $fileViewUrl : $fileDownloadUrl }}" target="_blank" rel="noopener"
                   class="flex items-center justify-between gap-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
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
                    <span class="text-sky-600 group-hover:text-sky-700 text-sm font-medium whitespace-nowrap flex-shrink-0">
                        {{ $isPdf ? 'Open' : 'Download' }} →
                    </span>
                </a>
            </div>
        </div>

        {{-- ═══════════════ RIGHT COLUMN — Scoring Form ═══════════════ --}}
        <div class="lg:col-span-3">
            <form wire:submit.prevent="save">
                {{ $this->form }}

                {{-- Sticky action bar --}}
                <div class="sticky bottom-0 mt-6 -mx-1 px-1 py-4 bg-gradient-to-t from-white via-white dark:from-gray-900 dark:via-gray-900 to-transparent">
                    <div class="flex flex-col sm:flex-row gap-3">
                        @unless ($locked)
                            <x-filament::button
                                wire:click="save"
                                color="gray"
                                icon="heroicon-o-bookmark"
                                class="flex-1"
                            >
                                Save
                            </x-filament::button>

                            <x-filament::button
                                wire:click="saveAndNext"
                                color="primary"
                                icon="heroicon-o-arrow-right-circle"
                                class="flex-1"
                            >
                                Save &amp; Next
                            </x-filament::button>
                        @endunless

                        <x-filament::button
                            wire:click="saveAndClose"
                            color="gray"
                            outlined
                            icon="heroicon-o-x-mark"
                            class="flex-1"
                        >
                            {{ $locked ? 'Back to Queue' : 'Save & Close' }}
                        </x-filament::button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Tighten up rubric rows so they read like a clean scoring table */
        .rubric-row {
            padding: 10px 0;
            border-bottom: 1px solid rgba(229, 231, 235, 0.6);
            align-items: center !important;
        }
        .rubric-row:last-child {
            border-bottom: none;
        }
        .dark .rubric-row {
            border-bottom-color: rgba(55, 65, 81, 0.6);
        }
    </style>

</x-filament-panels::page>
