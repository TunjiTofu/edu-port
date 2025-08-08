<!-- resources/views/filament/observer/pages/force-change-password.blade.php -->
<x-filament-panels::page>
    <div class="max-w-2xl">
        @if(!auth()->user()->password_changed_at)
            <div class="mb-6 p-4 bg-warning-50 border border-warning-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-warning-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-warning-800">
                            Password Change Required
                        </h3>
                        <div class="mt-2 text-sm text-warning-700">
                            <p>For security reasons, you must change your default password before accessing the system.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <x-filament::section>
            <x-slot name="heading">
                {{ $this->getHeading() }}
            </x-slot>

            <form wire:submit="updatePassword">
                {{ $this->form }}

                <div class="mt-6">
                    {{ $this->getFormActions() }}
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
