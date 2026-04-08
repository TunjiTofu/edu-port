<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Student Information Card --}}
        <x-filament::section>
            <x-slot name="heading">
                Intending MG Information
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</p>
                    <p class="text-base font-semibold">{{ $studentData['student']['name'] }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</p>
                    <p class="text-base">{{ $studentData['student']['email'] }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</p>
                    <p class="text-base">{{ $studentData['student']['phone'] ?? 'N/A' }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Church</p>
                    <p class="text-base">{{ $studentData['student']['church'] ?? 'N/A' }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">District</p>
                    <p class="text-base">{{ $studentData['student']['district'] ?? 'N/A' }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Overall Summary Card --}}
        <x-filament::section>
            <x-slot name="heading">
                Overall Summary
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tasks</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $studentData['summary']['total_tasks'] }}</p>
                </div>

                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Submitted</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $studentData['summary']['submitted_count'] }}</p>
                </div>

                <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Not Submitted</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $studentData['summary']['not_submitted_count'] }}</p>
                </div>

                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completion %</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($studentData['summary']['percentage'], 1) }}%</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Score</p>
                    <p class="text-xl font-bold">{{ number_format($studentData['summary']['total_score'], 1) }} / {{ number_format($studentData['summary']['max_score'], 1) }}</p>
                </div>

                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Score out of 100</p>
                    <p class="text-xl font-bold">{{ number_format($studentData['summary']['score_out_of_100'], 1) }}/100</p>
                </div>

                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Score out of 60</p>
                    <p class="text-xl font-bold">{{ number_format($studentData['summary']['score_out_of_60'], 1) }}/60</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Section Breakdown --}}
        @foreach($studentData['sections'] as $section)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex justify-between items-center w-full">
                        <span>{{ $section['name'] }}</span>
                        <span class="text-sm font-normal">
                        {{ number_format($section['total_score'], 1) }} / {{ number_format($section['max_score'], 1) }}
                        ({{ number_format($section['percentage'], 1) }}%)
                    </span>
                    </div>
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left font-medium">Task</th>
                            <th class="px-4 py-3 text-center font-medium">Max Score</th>
                            <th class="px-4 py-3 text-center font-medium">Score</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Comments</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($section['tasks'] as $task)
                            <tr>
                                <td class="px-4 py-3">{{ $task['title'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $task['max_score'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($task['score'] !== null)
                                        <span class="font-semibold">{{ number_format($task['score'], 1) }}</span>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($task['status'] === 'Submitted')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Submitted
                                    </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Not Submitted
                                    </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="max-w-xs overflow-hidden text-ellipsis">
                                        {{ $task['comments'] ?? 'No comments' }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endforeach

        {{-- Submitted Tasks List --}}
        @if(count($studentData['submitted_tasks']) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Submitted Tasks ({{ count($studentData['submitted_tasks']) }})
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left font-medium">Task</th>
                            <th class="px-4 py-3 text-left font-medium">Section</th>
                            <th class="px-4 py-3 text-center font-medium">Score</th>
                            <th class="px-4 py-3 text-center font-medium">Submitted At</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($studentData['submitted_tasks'] as $task)
                            <tr>
                                <td class="px-4 py-3">{{ $task['title'] }}</td>
                                <td class="px-4 py-3">{{ $task['section'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    {{ $task['score'] ?? 'Not graded' }} / {{ $task['max_score'] }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ $task['submitted_at']?->format('M d, Y H:i') ?? 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Not Submitted Tasks List --}}
        @if(count($studentData['not_submitted_tasks']) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Not Submitted Tasks ({{ count($studentData['not_submitted_tasks']) }})
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left font-medium">Task</th>
                            <th class="px-4 py-3 text-left font-medium">Section</th>
                            <th class="px-4 py-3 text-center font-medium">Max Score</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($studentData['not_submitted_tasks'] as $task)
                            <tr>
                                <td class="px-4 py-3">{{ $task['title'] }}</td>
                                <td class="px-4 py-3">{{ $task['section'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $task['max_score'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
