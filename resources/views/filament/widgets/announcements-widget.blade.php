<div class="fi-wi-announcements">
    @if ($announcements->isNotEmpty())
        <div class="p-4 sm:p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/30">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Announcements</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $announcements->count() }} active {{ str('announcement')->plural($announcements->count()) }}
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                @foreach ($announcements as $announcement)
                    <div class="rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/50 dark:bg-blue-950/20 p-4 transition hover:bg-blue-50 dark:hover:bg-blue-950/30">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white leading-tight mb-1">
                                    {{ $announcement->title }}
                                </h3>
                                <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed prose prose-sm dark:prose-invert max-w-none">
                                    {!! $announcement->body !!}
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $announcement->created_at->format('M j, Y') }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                From: {{ $announcement->author?->name ?? 'Admin' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
