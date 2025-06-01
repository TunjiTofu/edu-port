

<div class="flex flex-col gap-1 text-sm">
    <div>
        <span class="font-bold">{{ $title }}</span>
    </div>

    @if($section)
    <div>
        <span class="text-gray-400">Section: {{ $section }}</span>
    </div>
    @endif
</div>