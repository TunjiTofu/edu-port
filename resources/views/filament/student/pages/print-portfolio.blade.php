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

            <div class="space-y-6">
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

                @if (($this->summary['completed'] ?? 0) > 0 && $this->downloadUrl)
                    {{-- Plain link (not a Livewire action): the server streams the file from disk. --}}
                    <div x-data="{ preparing: false }" class="space-y-2">
                        <x-filament::button
                            tag="a"
                            :href="$this->downloadUrl"
                            icon="heroicon-o-arrow-down-tray"
                            x-on:click="preparing = true; setTimeout(() => preparing = false, 20000)"
                        >
                            <span x-show="! preparing">Download PDF</span>
                            <span x-show="preparing" x-cloak>Preparing PDF…</span>
                        </x-filament::button>
                        <p x-show="preparing" x-cloak class="text-sm text-gray-500 dark:text-gray-400">
                            Your portfolio is being put together. This can take up to a minute; the download will start by itself.
                        </p>
                    </div>
                @else
                    <x-filament::button icon="heroicon-o-arrow-down-tray" disabled>
                        Download PDF
                    </x-filament::button>
                    @if ($this->summary)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            None of your tasks in this program have been completed yet.
                        </p>
                    @endif
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
