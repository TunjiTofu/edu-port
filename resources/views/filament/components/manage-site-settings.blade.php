<x-filament-panels::page>

    {{-- ── Status summary card ── --}}
    @php
        $isOpen   = \App\Models\SiteSetting::isRegistrationOpen();
        $deadline = \App\Models\SiteSetting::get('registration_deadline');
        $open     = \App\Models\SiteSetting::get('registration_open', '1');
    @endphp

{{--    <div class="mb-6 rounded-xl border p-5--}}
{{--        {{ $isOpen ? 'bg-green-50 border-green-200 dark:bg-green-950/20 dark:border-green-900' : 'bg-red-50 border-red-200 dark:bg-red-950/20 dark:border-red-900' }}">--}}
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ $isOpen ? '✅' : '🚫' }}</span>
            <div>
                <p class="font-bold text-sm {{ $isOpen ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                    Registration is currently {{ $isOpen ? 'OPEN' : 'CLOSED' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    @if ($isOpen)
                        @if ($deadline)
                            Closes automatically after midnight on <strong>{{ \Carbon\Carbon::parse($deadline)->format('l, F j, Y') }}</strong>.
                        @else
                            No deadline set — open until manually closed.
                        @endif
                    @else
                        @if (! $open || $open === '0')
                            Manually closed by admin.
                        @elseif ($deadline)
                            Deadline of <strong>{{ \Carbon\Carbon::parse($deadline)->format('F j, Y') }}</strong> has passed.
                        @endif
                    @endif
                </p>
            </div>
        </div>
{{--    </div>--}}

    {{-- ── Settings form ── --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check">
                Save Settings
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />

</x-filament-panels::page>
