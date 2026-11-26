<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $task['title'] }}</title>
    @include('pdf.portfolio._styles')
    <style>
        .task-no { font-size: 11px; color: #b8860b; font-weight: bold; letter-spacing: 1px; }
        .task-title { font-size: 22px; font-weight: bold; color: #003DA5; margin-top: 2mm; }
        .task-section { font-size: 12px; color: #555; margin-top: 1mm; }
        .score-box { border: 2px solid #003DA5; background: #eef3fc; text-align: center; padding: 8mm 4mm; }
        .score-box .k { font-size: 10px; color: #555; text-transform: uppercase; letter-spacing: 1px; }
        .score-box .v { font-size: 30px; font-weight: bold; color: #003DA5; margin-top: 2mm; }
        .score-box .pct { font-size: 12px; color: #555; margin-top: 1mm; }
        .score-box.pending { border-color: #d9b44a; background: #fffaeb; }
        .score-box.pending .v { color: #8a6d00; font-size: 20px; }
        .comment { border: 1px solid #cfd8e8; border-left: 4px solid #003DA5; padding: 4mm 5mm; white-space: pre-line; }
        .follows { margin-top: 10mm; text-align: center; font-size: 10px; color: #555;
                   border-top: 1px dashed #b7c2d6; padding-top: 4mm; }
        .warn { border: 1px solid #e0a800; background: #fff8e1; color: #7a5b00; padding: 4mm 5mm; margin-top: 8mm; }
    </style>
</head>
<body>

<table class="strip"><tr>
    <td>Master Guide Training Program {{ $header_year }} &middot; {{ $student_name }}</td>
    <td class="right">{{ $program_label }}</td>
</tr></table>

<div class="task-no">TASK {{ $number }} OF {{ $total }}</div>
<div class="task-title">{{ $task['title'] }}</div>
<div class="task-section">Section: {{ $task['section'] }}</div>

<table style="margin-top: 8mm; margin-bottom: 8mm;">
    <tr>
        <td style="width: 38%; vertical-align: top; padding-right: 6mm;">
            @if ($task['reviewed'])
                <div class="score-box">
                    <div class="k">Score</div>
                    <div class="v">{{ number_format($task['score'], 1) }} / {{ number_format($task['max_score'], 1) }}</div>
                    @if ($task['max_score'] > 0)
                        <div class="pct">{{ number_format($task['score'] / $task['max_score'] * 100, 1) }}%</div>
                    @endif
                </div>
            @else
                <div class="score-box pending">
                    <div class="k">Score</div>
                    <div class="v">Pending</div>
                    <div class="pct">Out of {{ number_format($task['max_score'], 1) }}</div>
                </div>
            @endif
        </td>
        <td style="vertical-align: top;">
            <table class="details">
                <tr><td class="label">Submitted</td><td>{{ $task['submitted_at'] ?: 'N/A' }}</td></tr>
                <tr>
                    <td class="label">Status</td>
                    <td>
                        @php
                            $cls = match ($task['status']) {
                                'Reviewed'       => 'b-reviewed',
                                'Needs Revision' => 'b-revision',
                                default          => 'b-pending',
                            };
                        @endphp
                        <span class="badge {{ $cls }}">{{ $task['status'] }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Resubmitted</td>
                    <td>
                        @if ($task['resubmitted'])
                            <span class="badge b-resub">Yes &mdash; resubmitted</span>
                        @else
                            <span class="badge b-original">No &mdash; original submission</span>
                        @endif
                    </td>
                </tr>
                <tr><td class="label">File</td><td style="word-break: break-all;">{{ $task['file_name'] }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="block-title">REVIEWER'S COMMENT</div>
@if ($task['reviewed'])
    @if ($task['comments'])
        <div class="comment">{{ $task['comments'] }}</div>
    @else
        <div class="comment muted">No comment was left by the reviewer.</div>
    @endif
@else
    <div class="comment muted">This task has not been reviewed yet.</div>
@endif

@if ($embedded)
    <div class="follows">
        The candidate's submitted document follows
        ({{ $page_count }} {{ \Illuminate\Support\Str::plural('page', $page_count) }}).
    </div>
@else
    <div class="warn">
        <strong>The candidate's document could not be included in this portfolio.</strong><br>
        {{ $embed_error }}
        It can still be viewed in the Candidate Portal under My Tasks.
    </div>
@endif

</body>
</html>
