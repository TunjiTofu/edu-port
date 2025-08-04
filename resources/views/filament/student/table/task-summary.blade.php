<div style="display: flex; flex-direction: column; gap: 16px; font-size: 14px; margin: 12px 0;">
    <!-- Task Title -->
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-weight: 600; color: #fff1f2;" title="{{ $fullTitle }}">{{ $title }}</span>
    </div>

    <!-- Section -->
    @if($section)
        <div>
            <span style="font-size: 12px; color: #fffbeb;">Section: {{ $section }}</span>
        </div>
    @endif

    <!-- Due Date Badge -->
    <div>
        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500;
            @if($dueDateColor === 'danger')
                background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;
            @elseif($dueDateColor === 'warning')
                background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a;
            @elseif($dueDateColor === 'success')
                background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;
            @else
                background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;
            @endif">
            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Due: {{ $dueDateText }}
        </span>
    </div>

    <!-- Submission Status Badge -->
    <div>
        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500;
            @if($statusColor === 'success')
                background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;
            @elseif($statusColor === 'warning')
                background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a;
            @elseif($statusColor === 'danger')
                background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;
            @elseif($statusColor === 'info')
                background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;
            @elseif($statusColor === 'gray')
                background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;
            @else
                background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;
            @endif">
            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Status: {{ $status }}
        </span>
    </div>
</div>
