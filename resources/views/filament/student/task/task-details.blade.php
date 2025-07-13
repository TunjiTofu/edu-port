<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Submission UI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        dark: {
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .card {
                @apply bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm transition-all duration-200 hover:shadow-md;
            }
            .status-badge {
                @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium;
            }
            .section-title {
                @apply text-xl font-bold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2;
            }
            .info-label {
                @apply block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1 flex items-center gap-1.5;
            }
            .info-value {
                @apply text-sm text-gray-900 dark:text-gray-100 font-medium;
            }
            .content-block {
                @apply mb-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-gray-200 dark:border-gray-700;
            }
            .content-title {
                @apply text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2;
            }
            .prose-content {
                @apply prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 prose-p:my-2 prose-ul:pl-5 prose-ul:list-disc prose-ol:pl-5 prose-ol:list-decimal;
            }
            .divider {
                @apply border-t border-gray-200 dark:border-gray-700 my-6;
            }
        }
    </style>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900 p-4 md:p-8">
<div class="max-w-4xl space-y-6">
    <!-- Task Information Card -->
    <div class="card">
        <div class="content-block">
            <h3 class="content-title font-bold">
                <i class="fas fa-tasks text-primary-500"></i>
                Task
            </h3>
            <h4 class="text-l font-medium text-gray-900 dark:text-white flex items-center gap-2">
                {{ $task->title }}
            </h4>
        </div>

        <!-- Task Content Sections -->
        <div class="space-y-6 pt-4">
            @if($task->description)
                <div class="content-block">
                    <h3 class="content-title font-bold">
                        <i class="fas fa-file-alt text-primary-500"></i>
                        Description
                    </h3>
                    <div class="prose-content pt-2">
                        {!! nl2br(e($task->description)) !!}
                    </div>
                </div>
            @endif

            @if($task->instructions)
                <div class="content-block">
                    <h3 class="content-title font-bold">
                        <i class="fas fa-list-check text-primary-500"></i>
                        Instructions
                    </h3>
                    <div class="prose-content pt-2">
                        {!! nl2br(e($task->instructions)) !!}
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6 pt-4">
            <div class="content-block">
                <h3 class="content-title">
                    <i class="fas fa-layer-group text-primary-500"></i>
                    <span class="font-bold">Section</span>: {{ $task->section->name }}
                </h3>
            </div>
        </div>

        <div class="space-y-6 pt-4">
            <div class="content-block">
                <h3 class="content-title">
                    <i class="fas fa-layer-group text-primary-500"></i>
                    <span class="font-bold">Due Date</span>:
                    @if($task->due_date)
                        {{ \Carbon\Carbon::parse($task->due_date)->format('F j, Y g:i A') }} &nbsp; &nbsp;
                        @php
                            $daysLeft = number_format(now()->diffInDays(\Carbon\Carbon::parse($task->due_date), false), 0);
                        @endphp
                        @if($daysLeft < 0)
                            <span class="ml-2 status-badge bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Overdue
                                </span>
                        @elseif($daysLeft == 0)
                            <span class="ml-2 status-badge bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Due Today
                                </span>
                        @elseif($daysLeft <= 3)
                            <span class="ml-2 status-badge bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                    <i class="fas fa-clock mr-1"></i> {{ $daysLeft }} day{{ $daysLeft > 1 ? 's' : '' }} left
                                </span>
                        @else
                            <span class="ml-2 status-badge bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                    <i class="fas fa-check-circle mr-1"></i> {{ $daysLeft }} days left
                                </span>
                        @endif
                    @else
                        <span class="text-gray-500 dark:text-gray-400">No due date set</span>
                    @endif
                </h3>
            </div>
        </div>

        @if($task->max_score)
        <div class="space-y-6 pt-4">
            <div class="content-block">
                <h3 class="content-title">
                    <i class="fas fa-star text-primary-500"></i>
                    <span class="font-bold">Max Score</span>: {{ $task->max_score }}
                </h3>
            </div>
        </div>
        @endif

    </div>

    @if($hasSubmission)
        <div class="space-y-6">
            <div class="content-block">
                <h3 class="content-title">
                    <i class="fas fa-hourglass-half text-primary-500"></i>
                    <span class="font-bold">Submission Status</span>: {{ str_replace('_', ' ', ucfirst($submission->status))}}
                </h3>
            </div>
        </div>

        <div class="space-y-6">
            <div class="content-block">
                <h3 class="content-title">
                    <i class="fas fa-calendar-check text-primary-500"></i>
                    <span class="font-bold">Submitted At</span>: {{ $submission->submitted_at->format('F j, Y g:i A')  }}
                </h3>
            </div>
        </div>
    @endif

    <!-- Submission Status Card -->
    <div class="card">
        @if($hasSubmission)
            <div class="space-y-6 pt-4">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="info-label font-bold">
                            <i class="fas fa-file-alt text-primary-500"></i>
                            Submitted File Name
                        </label>
                        <p class="info-value truncate">{{ $submission->file_name }}</p>
                    </div>

                    <div>
                        <label class="info-label font-bold">
                            <i class="fas fa-weight text-primary-500"></i>
                            File Size
                        </label>
                        <p class="info-value">{{ number_format($submission->file_size / 1024, 2) }} KB</p>
                    </div>
                </div>

                @if($submission->student_notes)
                    <div class="content-block">
                        <h3 class="content-title font-bold">
                            <i class="fas fa-sticky-note text-primary-500"></i>
                            Your Notes
                        </h3>
                        <div class="text-sm text-gray-900 dark:text-gray-100 bg-yellow-50 dark:bg-yellow-900/10 p-4 rounded-md border-l-4 border-yellow-400">
                            {!! nl2br(e($submission->student_notes)) !!}
                        </div>
                    </div>
                @endif

                @if($submission?->review?->comments)
                    <div class="content-block">
                        <h3 class="content-title font-bold">
                            <i class="fas fa-comment-dots text-primary-500"></i>
                            Instructor Feedback
                        </h3>
                        <div class="text-sm text-gray-900 dark:text-gray-100 bg-blue-50 dark:bg-blue-900/10 p-4 rounded-md border-l-4 border-blue-400">
                            {!! nl2br(e($submission?->review?->comments)) !!}
                        </div>
                    </div>
                @endif

{{--                @if($submission?->review?->score && $task->max_score)--}}
{{--                    <div class="flex items-center justify-center p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg border border-indigo-200 dark:border-indigo-700">--}}
{{--                        <div class="text-center">--}}
{{--                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Your Score</p>--}}
{{--                            <div class="text-3xl font-bold text-indigo-700 dark:text-indigo-300">--}}
{{--                                {{ $submission?->review?->score }}<span class="text-lg text-gray-500 dark:text-gray-400">/{{ $task->max_score }}</span>--}}
{{--                            </div>--}}
{{--                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">--}}
{{--                                @php--}}
{{--                                    $percentage = ($submission?->review?->score / $task->max_score) * 100;--}}
{{--                                @endphp--}}
{{--                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mt-2">--}}
{{--                                    <div class="bg-indigo-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>--}}
{{--                                </div>--}}
{{--                                <div class="mt-1">{{ number_format($percentage, 1) }}%</div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                @endif--}}
            </div>
        @else
            <div class="text-center py-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No Submission Yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">You haven't submitted this assignment yet.</p>

                @if($task->due_date && now()->isAfter($task->due_date))
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 max-w-md mx-auto">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-red-800 dark:text-red-200">Assignment Overdue</h3>
                                <p class="text-sm text-red-700 dark:text-red-300 mt-1">This assignment was due on {{ \Carbon\Carbon::parse($task->due_date)->format('F j, Y g:i A') }}. Contact your instructor if you need an extension.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 max-w-md mx-auto">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-green-800 dark:text-green-200">Ready to Submit</h3>
                                <p class="text-sm text-green-700 dark:text-green-300 mt-1">Use the "Submit" button in task page.</p>
                            </div>
                        </div>
                    </div>

                @endif
            </div>
        @endif
    </div>
</div>

</body>
</html>
