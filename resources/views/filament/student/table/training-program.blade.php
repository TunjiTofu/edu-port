@php
    use Illuminate\Support\Facades\Storage;

    // Image handling
    $imageUrl = $record->image && Storage::disk(config('filesystems.default'))->exists($record->image)
        ? Storage::disk(config('filesystems.default'))->url($record->image)
        : $defaultImageUrl;

    // Counts
    $sectionsCount = $record->sections_count ?? $record->sections->count();
    $tasksCount = $record->sections->sum(fn($section) => $section->tasks->count());

    // Enrollment data
    $enrollment = $record->enrollments->first();
    $enrolledAt = $enrollment?->enrolled_at?->format('M d, Y');
    $status = $enrollment?->status;
@endphp

<div class="p-4 border rounded-lg shadow-sm bg-white">
    <div class="flex items-start gap-4">
        <!-- Image -->
        <div class="shrink-0">
            <img
                src="{{ $imageUrl }}"
                alt="Program image"
                class="rounded-full h-16 w-16 object-cover border-2 border-gray-200"
            >
        </div>

        <!-- Content -->
        <div class="flex-1 space-y-2">
            <!-- Name and Description -->
            <div>
                <h3 class="font-bold text-lg text-gray-900">{{ $record->name }}</h3>
                @if($record->description)
                    <p class="text-sm text-gray-500 mt-1">{{ $record->description }}</p>
                @endif
            </div>

            <!-- Stats -->
            <div class="flex flex-wrap gap-2 items-center">
                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                    {{ $sectionsCount }} Sections
                </span>
                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                    {{ $tasksCount }} Tasks
                </span>
                @if($enrolledAt)
                    <span class="text-xs text-gray-500">
                        Enrolled: {{ $enrolledAt }}
                    </span>
                @endif
            </div>

            <!-- Status -->
            @if($status)
                @php
                    $statusColors = [
                        \App\Enums\ProgramEnrollmentStatus::ACTIVE->value => 'bg-green-100 text-green-800',
                        \App\Enums\ProgramEnrollmentStatus::COMPLETED->value => 'bg-yellow-100 text-yellow-800',
                        \App\Enums\ProgramEnrollmentStatus::PAUSED->value => 'bg-red-100 text-red-800',
                    ];
                @endphp
                <div class="mt-2">
                    <span class="px-2.5 py-1 text-xs rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($status) }}
                    </span>
                </div>
            @endif
        </div>

        <!-- View Action -->
        <div class="shrink-0">
            @php
                $viewAction = \Filament\Tables\Actions\ViewAction::make()
                    ->color('primary')
                    ->icon('heroicon-o-eye');
            @endphp
            {{ $viewAction->record($record) }}
        </div>
    </div>
</div>
