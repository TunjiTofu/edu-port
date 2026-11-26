<x-filament-panels::page>
    @if (empty($this->programs))
        <x-filament::section>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-academic-cap class="w-10 h-10 mx-auto mb-3" />
                <p class="font-medium">You are not enrolled in any training program yet.</p>
                <p class="text-sm mt-1">Your portfolio will be available once you enrol and submit tasks.</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Download your portfolio</x-slot>
            <x-slot name="description">
                One PDF with a cover page, your candidate details, and every completed task in the
                selected program: a results page (score, reviewer's comment, resubmitted or not)
                followed by your uploaded document. Tasks still awaiting review or needing revision
                are not included.
            </x-slot>

            <form wire:submit="download" class="space-y-6">
                {{ $this->form }}

                @if ($s = $this->summary)
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="rounded-lg border border-gray-200 dark:border-white/10 p-4">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Tasks submitted</dt>
                            <dd class="mt-1 text-xl font-semibold">{{ $s['submitted'] }} / {{ $s['total_tasks'] }}</dd>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-white/10 p-4">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Completed (in portfolio)</dt>
                            <dd class="mt-1 text-xl font-semibold">{{ $s['completed'] }}</dd>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-white/10 p-4">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Resubmitted</dt>
                            <dd class="mt-1 text-xl font-semibold">{{ $s['resubmitted'] }}</dd>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-white/10 p-4">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Score (completed tasks)</dt>
                            <dd class="mt-1 text-xl font-semibold">
                                @if ($s['percentage'] !== null)
                                    {{ number_format($s['percentage'], 1) }}%
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                @endif

                <x-filament::button
                    type="submit"
                    icon="heroicon-o-arrow-down-tray"
                    :disabled="($this->summary['completed'] ?? 0) === 0"
                >
                    <span wire:loading.remove wire:target="download">Download PDF</span>
                    <span wire:loading wire:target="download">Preparing PDF…</span>
                </x-filament::button>
            </form>
        </x-filament::section>
    @endif
</x-filament-panels::page>
