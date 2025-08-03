<div style="display: flex; flex-direction: column; gap: 18px; font-size: 14px; margin-top: 10px">
    <!-- Task Title (truncated to 15 characters) -->
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-weight: bold;" title="{{ $fullTitle }}">{{ $title }}</span>
    </div>

    <!-- Section -->
    @if($section)
        <div>
            <span style="font-size: 12px;">Section: {{ $section }}</span>
        </div>
    @endif

    <!-- Due Date Badge -->
    <div>

        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 9999px; font-size: 12px; font-weight: 500;
            @if($dueDateColor === 'danger')
                background-color: #fecaca; color: #991b1b;
            @elseif($dueDateColor === 'warning')
                background-color: #fef3c7; color: #92400e;
            @elseif($dueDateColor === 'success')
                background-color: #d1fae5; color: #065f46;
            @else
                background-color: #f3f4f6; color: #374151;
            @endif">
            📅 Due Date:  {{ $dueDateText }}
        </span>
    </div>

    <!-- Submission Status Badge -->
    <div>
        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 9999px; font-size: 12px; font-weight: 500;
            @if($statusColor === 'success')
                background-color: #d1fae5; color: #065f46;
            @elseif($statusColor === 'warning')
                background-color: #fef3c7; color: #92400e;
            @elseif($statusColor === 'danger')
                background-color: #fecaca; color: #991b1b;
            @elseif($statusColor === 'info')
                background-color: #dbeafe; color: #1e40af;
            @elseif($statusColor === 'gray')
                background-color: #f3f4f6; color: #374151;
            @else
                background-color: #fecaca; color: #991b1b;
            @endif">
            📋 Task Status: {{ $status }}
        </span>
    </div>
</div>
