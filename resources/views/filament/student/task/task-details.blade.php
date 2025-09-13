@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Submission UI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
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
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        warning: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .dark .card-hover:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        .progress-bar {
            transition: width 0.5s ease-in-out;
        }

        .icon-primary {
            color: #0ea5e9 !important;
        }

        .icon-success {
            color: #22c55e !important;
        }

        .icon-warning {
            color: #f59e0b !important;
        }

        .icon-danger {
            color: #ef4444 !important;
        }

        .icon-info {
            color: #6b7280 !important;
        }

        .dark .icon-primary {
            color: #38bdf8 !important;
        }

        .dark .icon-success {
            color: #4ade80 !important;
        }

        .dark .icon-warning {
            color: #fbbf24 !important;
        }

        .dark .icon-danger {
            color: #f87171 !important;
        }

        .dark .icon-info {
            color: #9ca3af !important;
        }

        .rubric-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            border: 1px solid gray;
            padding: 1rem;
        }
        .rubric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .rubric-card-result {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
        }
        .rubric-card-result:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .evaluation-badge {
            transition: all 0.3s ease;
        }
        .evaluation-badge:hover {
            transform: scale(1.05);
        }
        .performance-bar {
            height: 8px;
            border-radius: 4px;
        }
        .dark .rubric-card {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2), 0 2px 4px -1px rgba(0, 0, 0, 0.15);
        }
        .dark .rubric-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.25), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Enhanced gradient classes */
        .bg-blue-gradient {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        }
        .bg-green-gradient {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        .bg-purple-gradient {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        .bg-yellow-gradient {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        .bg-red-gradient {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        /* Ensure dark mode colors work */
        .dark {
            color-scheme: dark;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 min-h-screen">
<!-- Header Section -->
<div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-6xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-primary-100 dark:bg-primary-900">
                    <i class="fas fa-tasks icon-primary text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $task->title }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Section: {{ $task->section->name }}</p>
                </div>
            </div>

        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                @if($task->due_date)
                    @php
//                        $daysLeft = number_format(now()->diffInDays(Carbon::parse($task->due_date), false), 0);
                        $dueDate = Carbon::parse($task->due_date)->endOfDay();
                        $daysLeft = floor(now()->diffInDays($dueDate, false));
                    @endphp
                    @if($daysLeft < 0)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-md font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 gap-x-3">
                                <i class="fas fa-exclamation-triangle icon-danger mr-1"></i> Overdue
                            </span>
                    @elseif($daysLeft == 0)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-md font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 gap-x-3">
                                <i class="fas fa-exclamation-circle icon-danger mr-1"></i> Due Today
                            </span>
                    @elseif($daysLeft <= 3)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-md font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 gap-x-3">
                                <i class="fas fa-clock icon-warning mr-1"></i> {{ $daysLeft }} day{{ $daysLeft > 1 ? 's' : '' }} left
                            </span>
                    @else
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-md font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 gap-x-3">
                                <i class="fas fa-check-circle icon-success mr-1"></i> {{ $daysLeft }} days left
                            </span>
                    @endif
                @endif

                @if($hasSubmission)
                    <div class="mt-1">
                            <span
                                class="inline-flex items-center px-2 py-1 rounded text-md font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 gap-x-3">
                                <i class="fas fa-check icon-primary mr-1"></i> {{ str_replace('_', ' ', ucfirst($submission->status))}}
                            </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content Column -->
        <div class="lg:col-span-2 space-y-6">

            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 card-hover">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-x-2">
                            <i class="fas fa-info-circle icon-primary mr-3"></i>
                            Task Details
                        </h2>
                    </div>

                    <div class="space-y-3">
                        <div
                            class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gradient-to-r from-gray-50 to-gray-50 dark:from-gray-700/30 dark:to-gray-700/30 hover:from-primary-50 hover:to-blue-50 dark:hover:from-primary-900/20 dark:hover:to-blue-900/20 transition-all duration-200">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    <i class="fas fa-file-alt icon-info mr-2"></i> Description
                                </h4>
                            </div>
                            @if($task->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400">{!! nl2br($task->description) !!}</p>
                            @endif
                        </div>

                        <div
                            class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gradient-to-r from-gray-50 to-gray-50 dark:from-gray-700/30 dark:to-gray-700/30 hover:from-primary-50 hover:to-blue-50 dark:hover:from-primary-900/20 dark:hover:to-blue-900/20 transition-all duration-200">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    <i class="fas fa-list-check icon-info mr-2"></i> Instructions
                                </h4>
                            </div>
                            @if($task->instructions)
                                <p class="text-sm text-gray-600 dark:text-gray-400">{!! nl2br($task->instructions) !!}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Rubric Comparison System -->
            @if(isset($rubrics) && $rubrics->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 fade-in">
                        @if(isset($rubricReview) && $rubricReview)
                            <!-- REVIEW EXISTS: COMPARISON VIEW -->
                            <div class="flex flex-col md:flex-row justify-between gap-6 mb-8">
                                <div>
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-x-3">
                                        <i class="fas fa-balance-scale icon-primary"></i>
                                        Evaluation Comparison
                                    </h2>
                                    <p class="text-slate-500 dark:text-slate-400 mt-2">
                                        Compare expectations with your actual results
                                    </p>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-4">
                                    <div class="bg-blue-gradient text-white p-4 rounded-xl min-w-[180px]">
                                        <div class="text-sm opacity-90">Possible Points</div>
                                        <div class="text-3xl font-bold mt-1">{{ $rubricReview['total_possible'] }}</div>
                                    </div>

                                    <div class="bg-green-gradient text-white p-4 rounded-xl min-w-[180px]">
                                        <div class="text-sm opacity-90">Points Awarded</div>
                                        <div class="text-3xl font-bold mt-1">{{ $rubricReview['total_awarded'] }}</div>
                                    </div>

                                    <div class="bg-purple-gradient text-white p-4 rounded-xl min-w-[180px]">
                                        <div class="text-sm opacity-90">Percentage</div>
                                        <div class="text-3xl font-bold mt-1">{{ number_format($rubricReview['score_percentage'], 1) }}%</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Indicators -->

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 border-2 border-slate-200 dark:border-slate-700 rounded-md mt-3">
                                <div class="bg-slate-50 dark:bg-slate-700/40 p-4 rounded-lg">
                                    <div class="flex justify-between mb-2">
                                        <span class="font-medium text-slate-700 dark:text-slate-300">Completion Status</span>
                                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $rubricReview['checked_count'] }}/{{ $rubricReview['total_count'] }}  criteria</span>
                                    </div>
                                    <div class="relative pt-1">
                                        <div class="flex items-center justify-between">
                                            <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-full h-4">
                                                <div class="
                                                 @if($rubricReview['completion_percentage'] >= 80) bg-green-gradient dark:bg-green-gradient
                                                    @elseif($rubricReview['completion_percentage'] >= 50) bg-yellow-gradient dark:bg-yellow-gradient
                                                    @else bg-red-gradient dark:bg-red-gradient
                                                    @endif
                                                    h-4 rounded-full progress-bar" style="width: {{ $rubricReview['completion_percentage'] }}%"></div>
                                            </div>
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 ml-3">{{ number_format($rubricReview['completion_percentage'], 1) }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-slate-50 dark:bg-slate-700/40 p-4 rounded-lg">
                                    <div class="flex justify-between mb-2">
                                        <span class="font-medium text-slate-700 dark:text-slate-300">Performance Level</span>
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                            @if($rubricReview['score_percentage'] >= 80) Excellent
                                            @elseif($rubricReview['score_percentage'] >= 50) Satisfactory
                                            @else Needs Improvement @endif
                                        </span>
                                    </div>
                                    <div class="relative pt-1">
                                        <div class="flex items-center justify-between">
                                            <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-full h-4">
{{--                                                <div class="h-4 rounded-full bg-green-gradient progress-bar" style="width: {{$rubricReview['score_percentage']}}%"></div>--}}
                                                <div class="h-4 rounded-full
                                                    @if($rubricReview['score_percentage'] >= 80) bg-green-gradient dark:bg-green-gradient
                                                    @elseif($rubricReview['score_percentage'] >= 50) bg-yellow-gradient dark:bg-yellow-gradient
                                                    @else bg-red-gradient dark:bg-red-gradient
                                                    @endif progress-bar" style="width: {{$rubricReview['score_percentage']}}%">
                                                </div>
                                            </div>
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 ml-3">{{$rubricReview['score_percentage']}}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Comparison Cards -->
                            <div class="space-y-6 mt-3">
                                @foreach($rubrics as $rubric)
                                    @php
                                        $reviewDetail = collect($rubricReview['rubric_details'])->firstWhere('task_rubric.id', $rubric->id);
                                        $maxPoints = $rubric->max_points;

                                        if ($reviewDetail) {
                                            $percentage = $reviewDetail['percentage'];
                                            $performanceClass = $percentage >= 75 ? '#22c55e' :
                                                              ($percentage >= 50 ? '#f59e0b' : '#ef4444');
                                        }
                                    @endphp

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" >
                                        <!-- Expected Criteria -->
                                        <div class="border-l-4 rounded-r-lg rubric-card">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <i class="fas fa-bullseye icon-primary"></i>
                                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Expected</h3>
                                                    </div>
                                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 text-lg mb-2">{{ $rubric->title }}</h4>
                                                    @if($rubric->description)
                                                        <p class="text-gray-600 dark:text-white">{{ $rubric->description }}</p>
                                                    @endif
                                                </div>
                                                <div class="bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 font-bold px-3 py-1 rounded-full text-sm">
                                                    {{ $maxPoints }} pts
                                                </div>
                                            </div>
                                        </div>



                                        <!-- Actual Result -->
                                        <div class="border-l-4 {{ $reviewDetail ? $performanceClass : 'border-gray-400' }} {{ $reviewDetail ? 'bg-white dark:bg-gray-800' : 'bg-gray-100 dark:bg-gray-700/30' }} p-5 rounded-r-lg rubric-card-result"
                                             style="border: 1px solid {{$performanceClass}};  border-radius: 0.5rem; padding: 0.5rem;">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="flex items-center gap-2 mb-2">
                                                        @if($reviewDetail)
                                                            @if($percentage >= 75)
                                                                <i class="fas fa-check-circle icon-success"></i>
                                                            @elseif($percentage >= 50)
                                                                <i class="fas fa-check icon-warning"></i>
                                                            @else
                                                                <i class="fas fa-times-circle icon-danger"></i>
                                                            @endif
                                                        @else
                                                            <i class="fas fa-clock icon-info"></i>
                                                        @endif
                                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Your Result</h3>
                                                    </div>

                                                    @if($reviewDetail)

                                                        <h4 class="font-bold text-gray-800 dark:text-gray-200 text-md mb-1">
                                                            {{ $reviewDetail['points_awarded'] }}/{{ $maxPoints }} points
                                                        </h4>
                                                        @if($reviewDetail['review_rubric']['comments'])
                                                            <h3 class="font-bold icon-primary text-sm mb-1">
                                                                Comments: {{ $reviewDetail['review_rubric']['comments'] }}
                                                            </h3>
                                                        @else
                                                            <h3 class="font-bold icon-info text-sm mb-1">
                                                                No comments provided
                                                            </h3>
                                                        @endif
                                                        <div class="flex items-center gap-3 mt-3">
                                                            <div class="w-3/4 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                                                <div class="h-2.5 rounded-full
                                                        @if($percentage >= 75) bg-green-gradient
                                                        @elseif($percentage >= 50) bg-yellow-gradient
                                                        @else bg-red-gradient @endif"
                                                                     style="width: {{ $percentage }}%">
                                                                </div>
                                                            </div>
                                                            <span class="text-sm font-semibold
                                                    @if($percentage >= 75) icon-success
                                                    @elseif($percentage >= 50) icon-warning
                                                    @else icon-danger @endif">
                                                    {{ number_format($percentage, 1) }}%
                                                </span>
                                                        </div>
                                                    @else
                                                        <div class="text-gray-500 dark:text-gray-400 italic py-3">
                                                            Not yet evaluated
                                                        </div>
                                                    @endif
                                                </div>

                                                @if($reviewDetail)
                                                    <div class="text-sm font-bold
                                            @if($percentage >= 75) icon-success
                                            @elseif($percentage >= 50) icon-warning
                                            @else icon-danger @endif">
                                                        @if($percentage >= 75) Excellent
                                                        @elseif($percentage >= 50) Good
                                                        @else Needs Work @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <hr style="border-color: #ffffff;" class="my-4">


                                @endforeach
                            </div>
                        @else
                            <!-- NO REVIEW: EXPECTATIONS ONLY -->
                            <div class="text-center py-8">
                                <div class="inline-block bg-blue-100 dark:bg-blue-900/30 text-blue-500 p-4 rounded-full mb-4">
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Evaluation Criteria</h2>
                                <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-6">
                                    Your submission will be evaluated based on the following criteria. Review these requirements before submitting your work.
                                </p>

                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800 p-5 rounded-xl inline-block mb-8">
                                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $rubrics->sum('max_points') }}</div>
                                    <div class="text-gray-600 dark:text-gray-400">Total Possible Points</div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-4">
                                    @foreach($rubrics as $rubric)
                                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-white dark:bg-gray-800 hover:shadow-md transition-shadow rubric-card">
                                            <div class="flex justify-between items-start mb-3">
                                                <h3 class="font-bold text-gray-900 dark:text-white">{{ $rubric->title }}</h3>
                                                <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-bold text-sm px-2.5 py-1 rounded-full">
                                                    {{ $rubric->max_points }} pts
                                                </div>
                                            </div>
                                            @if($rubric->description)
                                                <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $rubric->description }}</p>
                                            @endif
{{--                                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-end">--}}
{{--                                    <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded">--}}
{{--                                        Evaluation Criteria--}}
{{--                                    </span>--}}
{{--                                            </div>--}}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Submission Content (if exists) -->
            @if($hasSubmission)
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 card-hover">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-x-2 mb-4">
                            <i class="fas fa-file-check icon-success"></i>
                            Your Submission
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div
                                class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center gap-x-2 mb-2">
                                    <i class="fas fa-file-alt icon-primary"></i>
                                    <span class="text-sm font-medium text-gray-700 dark:text-white">File Name</span>
                                </div>
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-medium truncate">{{ $submission->file_name }}</p>
                            </div>

                            <div
                                class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center gap-x-2 mb-2">
                                    <i class="fas fa-weight icon-primary"></i>
                                    <span class="text-sm font-medium text-gray-700 dark:text-white">File Size</span>
                                </div>
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ number_format($submission->file_size / 1024, 2) }}
                                    KB</p>
                            </div>
                        </div>

                        @if($submission->student_notes)
                            <div class="mb-4 mt-3">
                                <h3 class="text-md font-medium text-gray-700 dark:text-white mb-2 flex items-center gap-x-2">
                                    <i class="fas fa-sticky-note icon-warning"></i>
                                    Your Notes
                                </h3>
                                <div
                                    class="bg-yellow-50 dark:bg-yellow-800 p-4 rounded-lg border-l-4 border-yellow-400">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {!! nl2br($submission->student_notes) !!}
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($submission?->review?->comments)
                            <div class="mb-6">
                                <h3 class="text-md font-medium text-gray-700 dark:text-white mb-2 flex items-center gap-x-2">
                                    <i class="fas fa-comment-dots icon-primary"></i>
                                    Instructor Feedback
                                </h3>
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-l-4 border-blue-400">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {!! nl2br($submission?->review?->comments) !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            @endif
        </div>

        <!-- Sidebar Column -->
        <div class="lg:col-span-1 space-y-6">

            <!-- Quick Info Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 card-hover">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-4">
                        <i class="fas fa-clock icon-primary mr-2"></i>
                        Assignment Info
                    </h2>


                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <!-- Due Date Card -->
                        <div
                            class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center gap-x-2 mb-2">
                                <i class="fas fa-calendar-day text-primary-500"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-white">Due Date</span>
                            </div>

{{--                            @if($task->due_date)--}}
{{--                                <div>--}}
{{--                                    <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">--}}
{{--                                        {{ Carbon::parse($task->due_date)->format('M j, Y') }}--}}
{{--                                    </p>--}}
{{--                                    <p class="text-xs text-gray-500 dark:text-gray-400">--}}
{{--                                        {{ Carbon::parse($task->due_date)->format('g:i A') }}--}}
{{--                                    </p>--}}

{{--                                </div>--}}
{{--                            @else--}}
{{--                                <p class="text-sm text-gray-500 dark:text-gray-400">No due date set</p>--}}
{{--                            @endif--}}

                            @if($task->due_date)
                                @php
                                    $due = Carbon::parse($task->due_date);

                                    // if the time is midnight (default when only a date is stored), assume end of day
                                    if ($due->isStartOfDay()) {
                                        $due->endOfDay();
                                    }
                                @endphp

                                <div>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $due->format('M j, Y') }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $due->format('g:i A') }}
                                    </p>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No due date set</p>
                            @endif

                        </div>

                        <!-- Max Score Card -->
                        <div
                            class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center gap-x-2 mb-2">
                                <i class="fas fa-star text-yellow-500"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-white">Max Score</span>
                            </div>
                            <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ $task->max_score ?? 'Not specified' }}
                            </p>
                        </div>

                        <!-- Submission Status Card -->
                        @if($hasSubmission)
                            <div
                                class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center gap-x-2 mb-2">
                                    <i class="fas fa-paper-plane text-blue-500"></i>
                                    <span class="text-sm font-medium text-gray-700 dark:text-white">Submitted</span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $submission->submitted_at->format('M j, Y g:i A') }}
                                    </p>

                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            <!-- Action Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 card-hover">
                <div class="p-6">
                    @if($hasSubmission)
                        <div class="text-center">
                            <div
                                class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 mb-3">
                                <i class="fas fa-check icon-success text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Submitted!</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Your assignment has been submitted
                                successfully.</p>

{{--                            <!-- Score Display (if available) -->--}}
{{--                                                        @if($submission?->review?->score && $task->max_score && $submission->isResultPublished)--}}
{{--                                                            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-700 mb-4">--}}
{{--                                                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Your Score</p>--}}
{{--                                                                <div class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">--}}
{{--                                                                    {{ $submission?->review?->score }}<span class="text-lg text-gray-500 dark:text-gray-400">/{{ $task->max_score }}</span>--}}
{{--                                                                </div>--}}
{{--                                                                @php--}}
{{--                                                                    $percentage = ($submission?->review?->score / $task->max_score) * 100;--}}
{{--                                                                @endphp--}}
{{--                                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">--}}
{{--                                                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2 rounded-full progress-bar" style="width: {{ $percentage }}%"></div>--}}
{{--                                                                </div>--}}
{{--                                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($percentage, 1) }}%</div>--}}
{{--                                                            </div>--}}
{{--                                                        @endif--}}
                        </div>
                    @else
                        <div class="text-center">
                            @if($task->due_date && now()->isAfter($task->due_date))
                                <div
                                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 mb-3">
                                    <i class="fas fa-exclamation-triangle icon-danger text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-red-700 dark:text-red-300 mb-2">Assignment
                                    Overdue</h3>
                                <p class="text-sm text-red-600 dark:text-red-400 mb-4">
                                    This assignment was due
                                    on {{ Carbon::parse($task->due_date)->format('M j, Y g:i A') }}.
                                </p>
                                <div
                                    class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                                    <p class="text-xs text-red-700 dark:text-red-300">
                                        Contact your instructor if you need an extension.
                                    </p>
                                </div>
                            @else
                                <div
                                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 mb-3">
                                    <i class="fas fa-upload icon-primary text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Ready to
                                    Submit</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">You haven't submitted this
                                    assignment yet.</p>

                                <div
                                    class="bg-gradient-to-r from-primary-500 to-blue-500 text-white rounded-lg p-4 mb-4">
                                    <p class="text-sm font-medium">Use the "Submit" button in the task page to upload
                                        your work.</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Animate progress bars on page load
    document.querySelectorAll('.progress-bar').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 300);
    });
</script>
</body>
</html>
