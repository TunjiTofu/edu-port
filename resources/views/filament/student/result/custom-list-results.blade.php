<x-filament::page>
    <div class="space-y-6 px-4 py-6 sm:px-6">
        @forelse ($sections as $section)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Section Header -->
                <div class="px-8 py-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 border-b border-gray-200 dark:border-gray-600 p-4">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white truncate">
                                {{ $section->name }}
                            </h3>
                            <p class="text-base text-gray-600 dark:text-gray-300 mt-2">
                                {{ $section->trainingProgram->name }}
                            </p>
                        </div>
                        <div class="ml-6 flex-shrink-0">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                {{ $section->tasks->filter(fn($task) => $task->submissions->isNotEmpty())->count() }} results
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Results Content -->
                <div class="p-5">
                    @php
                        $hasResults = $section->tasks->some(fn($task) => $task->submissions->isNotEmpty());
                    @endphp

                    @if ($hasResults)
                        <div class="grid grid-cols-1 gap-4 p-4">
                            @foreach ($section->tasks as $task)
                                @foreach ($task->submissions as $submission)
                                    @php
                                        $review = $submission->review;
                                        $scorePercentage = ($review->score / $task->max_score) * 100;

                                        // Determine performance level with better color scheme
                                         if ($scorePercentage >= 85) {
                                            $textColor = '#16a34a'; // success-600
                                            $bgColor = '#22c55e'; // success-500
                                            $lightBg = '#f0fdf4'; // success-50
                                            $borderColor = '#bbf7d0'; // success-200
                                            $performanceText = 'Excellent';
                                        } elseif ($scorePercentage >= 70) {
                                            $textColor = '#2563eb'; // info-600
                                            $bgColor = '#3b82f6'; // info-500
                                            $lightBg = '#eff6ff'; // info-50
                                            $borderColor = '#bfdbfe'; // info-200
                                            $performanceText = 'Good';
                                        } elseif ($scorePercentage >= 50) {
                                            $textColor = '#d97706'; // warning-600
                                            $bgColor = '#f59e0b'; // warning-500
                                            $lightBg = '#fefce8'; // warning-50
                                            $borderColor = '#fde68a'; // warning-200
                                            $performanceText = 'Fair';
                                        } else {
                                            $textColor = '#ef4444'; // danger-600
                                            $bgColor = '#ef4444'; // danger-500
                                            $lightBg = '#fef2f2'; // danger-50
                                            $borderColor = '#fecaca'; // danger-200
                                            $performanceText = 'Needs Work';
                                        }
                                    @endphp

                                    <div class="bg-white dark:bg-gray-800 rounded-lg border p-4 hover:shadow-md transition-all duration-200"
                                         style="border-color: {{ $borderColor }}">
                                        <!-- Task Header -->
                                        <div class="mb-5">
                                            <h4 class="font-semibold text-gray-900 dark:text-white text-base">
                                                {{ $task->title }}
                                            </h4>
                                            <div class="flex items-center mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                                                <svg class="h-4 w-4 mr-1.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <span>Assignment</span>
                                            </div>
                                        </div>

                                        <!-- Three Boxes in a Row -->
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                                            <!-- Performance Box -->
                                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
                                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Performance</p>
                                                <div class="text-lg font-bold mb-1" style="color: {{ $textColor }}">
                                                    {{ $review->score }}<span class="text-xs font-normal text-gray-500 dark:text-gray-400">/{{ $task->max_score }}</span>
                                                </div>
                                                <span class="inline-block text-xs font-medium px-2 py-1 rounded-full border"
                                                      style="background-color: {{ $lightBg }}; color: {{ $textColor }}; border-color: {{ $borderColor }}">
            {{ $performanceText }}
        </span>
                                                <div class="mt-3">
                                                    <div class="flex justify-center items-center text-xs text-gray-600 dark:text-gray-300 mb-1.5">
                                                        <span class="font-semibold" style="color: {{ $textColor }}">{{ number_format($scorePercentage, 1) }}%</span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                                        <div class="h-2 rounded-full transition-all duration-300"
                                                             style="width: {{ $scorePercentage }}%; background-color: {{ $bgColor }}"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Submitted Box -->
                                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
                                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Submitted</p>
                                                <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm mb-1">
                                                    {{ $submission->submitted_at->format('M j, Y') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $submission->submitted_at->format('g:i A') }}
                                                </p>
                                            </div>

                                            <!-- Reviewed Box -->
                                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
                                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Reviewed</p>
                                                <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm mb-1">
                                                    {{ $review->reviewed_at->format('M j, Y') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $review->reviewed_at->format('g:i A') }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- View Button -->
                                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <a href="{{ \App\Filament\Student\Resources\ResultResource::getUrl('view', ['record' => $submission]) }}"
                                               class="inline-flex items-center text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors duration-200 group">
                                                <svg class="h-4 w-4 mr-1.5 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View detailed feedback
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="py-8 text-center">
                            <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">No results available</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 max-w-sm mx-auto">
                                Your graded submissions for this section will appear here once they're published.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <!-- No Sections State -->
            <div class="max-w-2xl mx-auto bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="text-center py-10 px-6">
                    <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No training programs</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                        You're not currently enrolled in any training programs. Results will appear here once you're enrolled and submissions are graded.
                    </p>
                </div>
            </div>
        @endforelse
    </div>
</x-filament::page>
