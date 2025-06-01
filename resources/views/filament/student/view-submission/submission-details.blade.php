<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h4 class="font-semibold text-gray-300">Submitted At:</h4>
            <p class="text-gray-400">{{ $submission->created_at->format('M d, Y \a\t g:i A') }}</p>
        </div>
        <div>
            <h4 class="font-semibold text-gray-300">Status:</h4>
            <p class="text-gray-400">{{ ucfirst($submission->status ?? 'Submitted') }}</p>
        </div>
    </div>
    
    @if($submission->comments)
        <div>
            <h4 class="font-semibold text-gray-300">Comments:</h4>
            <p class="text-gray-400 whitespace-pre-wrap">{{ $submission->comments }}</p>
        </div>
    @endif
    
    @if($submission->file_path)
        <div>
            <h4 class="font-semibold text-gray-300">File Name:</h4>
            <p class="text-gray-400">{{ basename($submission->file_name) }}</p>
        </div>
    @endif

    @if($submission->student_notes)
        <div>
            <h4 class="font-semibold text-gray-300">Student Notes:</h4>
            <p class="text-gray-400">{{ basename($submission->student_notes) }}</p>
        </div>
    @endif
    
    @if($submission->grade)
        <div>
            <h4 class="font-semibold text-gray-300">Grade:</h4>
            <p class="text-gray-400 font-medium">{{ $submission->grade }}{{ $submission->max_grade ? '/' . $submission->max_grade : '' }}</p>
        </div>
    @endif
</div>