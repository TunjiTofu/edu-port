{{--
    resources/views/filament/student/widgets/investiture-status.blade.php

    Prominent pass/fail banner shown on the student dashboard.
    Rendered by App\Filament\Student\Widgets\InvestitureStatusWidget.
--}}

@php
    $data = $this->getViewData();
@endphp

@if ($data['show'])
    <div class="fi-wi-stats-overview-stat rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 mb-4"
         style="background: {{ $data['passes'] === true ? 'linear-gradient(135deg,#d1fae5,#a7f3d0)' : ($data['passes'] === false ? 'linear-gradient(135deg,#fee2e2,#fca5a5)' : 'linear-gradient(135deg,#dbeafe,#bfdbfe)') }};
                border-left: 6px solid {{ $data['passes'] === true ? '#10b981' : ($data['passes'] === false ? '#ef4444' : '#3b82f6') }}">

        <div class="flex items-start gap-4">

            {{-- Icon --}}
            <div class="flex-shrink-0 mt-1">
                @if ($data['passes'] === true)
                    {{-- Trophy / success --}}
                    <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif ($data['passes'] === false)
                    {{-- X / danger --}}
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    {{-- Info / neutral --}}
                    <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @endif
            </div>

            {{-- Content --}}
            <div class="flex-1">
                <h3 class="text-lg font-bold
                    {{ $data['passes'] === true ? 'text-emerald-800' : ($data['passes'] === false ? 'text-red-800' : 'text-blue-800') }}">
                    {{ $data['title'] }}
                </h3>

                <p class="mt-1 text-sm
                    {{ $data['passes'] === true ? 'text-emerald-700' : ($data['passes'] === false ? 'text-red-700' : 'text-blue-700') }}">
                    {{ $data['message'] }}
                </p>

                @if (!empty($data['score']))
                    <span class="inline-block mt-3 px-3 py-1 rounded-full text-xs font-semibold
                        {{ $data['passes'] === true ? 'bg-emerald-600 text-white' : ($data['passes'] === false ? 'bg-red-600 text-white' : 'bg-blue-600 text-white') }}">
                        {{ $data['score'] }}
                    </span>
                @endif

                {{-- Congratulations confetti flourish for final pass --}}
                @if ($data['phase'] === 'final' && $data['passes'] === true)
                    <p class="mt-3 text-emerald-800 font-medium text-sm">
                        🎉 We look forward to welcoming you at the investiture ceremony!
                    </p>
                @endif
            </div>
        </div>
    </div>
@endif
