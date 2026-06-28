{{--
    Shared layout for StatsOverviewWidget subclasses that need a per-widget
    year/program filter. The filter select uses wire:model.live so Livewire
    re-renders the stats automatically when the user changes it.

    Requires the widget class to define four public wrapper methods:
      getWidgetHeading(), getWidgetDescription(), getWidgetFilters(), getWidgetStats()
--}}
<x-filament-widgets::widget>

    {{-- ── Header: title + filter select ── --}}
    <div class="flex flex-col gap-y-1 px-6 pt-5 pb-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-base font-semibold text-gray-950 dark:text-white">
                {{ $this->getWidgetHeading() }}
            </p>
            @if ($desc = $this->getWidgetDescription())
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $desc }}</p>
            @endif
        </div>

        @php $filters = $this->getWidgetFilters(); @endphp

        @if ($filters && count($filters) > 1)
            <div class="mt-2 sm:mt-0 sm:ml-4 flex-shrink-0">
                <select
                    wire:model.live="filter"
                    class="block w-full rounded-lg border-0 bg-white py-1.5 pl-3 pr-8 text-sm leading-5
                           text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300
                           focus:ring-2 focus:ring-inset focus:ring-primary-500
                           dark:bg-gray-800 dark:text-white dark:ring-gray-700
                           dark:focus:ring-primary-400"
                >
                    @foreach ($filters as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected($value === ($this->filter ?? ''))
                        >{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    {{-- ── Stats grid ── --}}
    <div class="grid grid-cols-1 divide-y divide-gray-200 border-t border-gray-200
                dark:divide-gray-700 dark:border-gray-700
                sm:grid-cols-2 sm:divide-x sm:divide-y-0
                xl:grid-cols-4">

        @foreach ($this->getWidgetStats() as $stat)
            @php
                $rawColor = $stat->getColor() ?? 'gray';
                $color    = is_string($rawColor) ? $rawColor : 'gray';

                [$textClass, $descClass] = match ($color) {
                    'primary' => ['text-primary-600 dark:text-primary-400',   'text-primary-500  dark:text-primary-400'],
                    'success' => ['text-success-600 dark:text-success-400',   'text-success-500  dark:text-success-400'],
                    'danger'  => ['text-danger-600  dark:text-danger-400',    'text-danger-500   dark:text-danger-400'],
                    'warning' => ['text-warning-600 dark:text-warning-400',   'text-warning-500  dark:text-warning-400'],
                    'info'    => ['text-info-600    dark:text-info-400',      'text-info-500     dark:text-info-400'],
                    default   => ['text-gray-950    dark:text-white',         'text-gray-500     dark:text-gray-400'],
                };

                $desc     = $stat->getDescription();
                $descIcon = $stat->getDescriptionIcon();
            @endphp

            <div class="flex flex-col gap-y-2 px-6 py-4">
                {{-- Label --}}
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $stat->getLabel() }}
                </p>

                {{-- Value --}}
                <p class="text-3xl font-bold tracking-tight {{ $textClass }}">
                    {{ $stat->getValue() }}
                </p>

                {{-- Description + icon --}}
                @if ($desc || $descIcon)
                    <div class="flex items-center gap-x-1.5 text-sm {{ $descClass }}">
                        @if ($descIcon)
                            <x-filament::icon
                                :icon="$descIcon"
                                class="h-4 w-4 flex-shrink-0"
                            />
                        @endif
                        @if ($desc)
                            <span>{{ $desc }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach

    </div>

</x-filament-widgets::widget>
