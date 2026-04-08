<div class="space-y-4">
    {{-- Task Information --}}
    <div class="border-b pb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ $submission->task->title }}
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Section: {{ $submission->task->section->name ?? 'N/A' }}
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Submitted: {{ $submission->submitted_at?->format('M d, Y H:i') ?? 'N/A' }}
        </p>
    </div>

    {{-- Text Content --}}
    @if($submission->text_content)
        <div>
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Submission Content:
            </h4>
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                    {{ $submission->text_content }}
                </p>
            </div>
        </div>
    @endif

    {{-- File Information --}}
    @if($submission->file_path && $submission->file_name)
        <div>
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Attached File:
            </h4>
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $submission->file_name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $submission->file_path }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Review Information --}}
    @if($submission->review)
        <div class="border-t pt-4">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Review Information:
            </h4>

            <div class="space-y-2">
                @if($submission->review->reviewer)
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Reviewer:</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100 ml-2">
                    {{ $submission->review->reviewer->name }}
                </span>
                    </div>
                @endif

                @if($submission->review->score !== null)
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Score:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 ml-2">
                    {{ $submission->review->score }}/{{ $submission->task->max_score ?? 10 }}
                </span>
                    </div>
                @endif

                @if($submission->review->comments)
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Comments:</span>
                        <div class="mt-1 bg-gray-50 dark:bg-gray-800 p-3 rounded">
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $submission->review->comments }}
                            </p>
                        </div>
                    </div>
                @endif

                @if($submission->review->reviewed_at)
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Reviewed:</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100 ml-2">
                    {{ $submission->review->reviewed_at->format('M d, Y H:i') }}
                </span>
                    </div>
                @endif

                @if($submission->review->admin_override)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 p-3 rounded">
                        <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                            ⚠️ Admin Override
                        </p>
                        @if($submission->review->override_reason)
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                {{ $submission->review->override_reason }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
