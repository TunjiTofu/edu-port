<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Student Information Card --}}
        <x-filament::section>
            <x-slot name="heading">
                Student Information
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->record->name }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->record->email }}</p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->record->phone ?? 'N/A' }}</p>
                </div>

                @if($this->record->church)
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Church</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->record->church->name }}</p>
                    </div>
                @endif

                @if($this->record->district)
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">District</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->record->district->name }}</p>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Submissions Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Submissions
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
