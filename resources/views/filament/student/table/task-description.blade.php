

<div class="flex flex-col gap-1 text-sm">
    <div>
        <span class="font-bold">{{ $title }}</span>
    </div>

{{--    @if($program)--}}
{{--        <div>--}}
{{--            <span class="text-gray-400">Program: {{ $program }}</span>--}}
{{--        </div>--}}
{{--    @endif--}}

    @if($section)
    <div>
        <span class="text-gray-400">Section: {{ $section }}</span>
    </div>
    @endif
</div>
