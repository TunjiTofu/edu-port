<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Result - {{ $student['name'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #4472C4;
            margin: 0;
            font-size: 24px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #4472C4;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .info-item {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 11px;
        }
        .info-value {
            color: #000;
            font-size: 12px;
        }
        .summary-boxes {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .summary-box {
            text-align: center;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .summary-box.blue { border-color: #4472C4; background-color: #E7F0FF; }
        .summary-box.green { border-color: #70AD47; background-color: #E2F0D9; }
        .summary-box.red { border-color: #C00000; background-color: #FFE7E7; }
        .summary-box.purple { border-color: #7030A0; background-color: #F2E7FF; }
        .summary-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
        }
        .score-boxes {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .score-box {
            padding: 12px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-submitted {
            background-color: #70AD47;
            color: white;
        }
        .status-not-submitted {
            background-color: #C00000;
            color: white;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
{{-- Header - Compact --}}
<div class="header" style="margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #003DA5;">
    <h1 style="font-size: 20px; margin: 0 0 5px 0; color: #003DA5;">Student Academic Result Report</h1>
    <p style="margin: 0; font-size: 10px;">Generated on {{ now()->format('F d, Y') }}</p>
</div>

{{-- Student Information - Compact --}}
<div class="section" style="margin-bottom: 15px;">
    <div class="section-title" style="padding: 5px 12px; font-size: 13px; margin-bottom: 8px; background-color: #003DA5;">STUDENT INFORMATION</div>
    <div style="padding: 8px 12px; background-color: #f5f5f5; border: 1px solid #ddd; font-size: 11px; line-height: 1.6;">
        <strong style="color: #003DA5;">{{ $student['name'] }}</strong> | {{ $student['email'] }} | {{ $student['phone'] ?? 'N/A' }}<br>
        <strong>Church:</strong> {{ $student['church'] ?? 'N/A' }} | <strong>District:</strong> {{ $student['district'] ?? 'N/A' }}
    </div>
</div>

{{-- Overall Summary - Truly Compact Single Row --}}
<div class="section" style="margin-bottom: 15px;">
    <div class="section-title" style="padding: 5px 12px; font-size: 13px; margin-bottom: 8px; background-color: #003DA5;">OVERALL SUMMARY</div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <tr>
            <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #E7F0FF; border: 1px solid #003DA5;">
                <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Total Tasks</div>
                <div style="font-size: 16px; font-weight: bold; color: #003DA5;">{{ $summary['total_tasks'] }}</div>
            </td>
            <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #E2F0D9; border: 1px solid #32CD32;">
                <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Submitted</div>
                <div style="font-size: 16px; font-weight: bold; color: #32CD32;">{{ $summary['submitted_count'] }}</div>
            </td>
            <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #FFE7E7; border: 1px solid #C00000;">
                <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Not Done</div>
                <div style="font-size: 16px; font-weight: bold; color: #C00000;">{{ $summary['not_submitted_count'] }}</div>
            </td>
            <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #F2E7FF; border: 1px solid #7030A0;">
                <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Complete %</div>
                <div style="font-size: 16px; font-weight: bold; color: #7030A0;">{{ number_format($summary['percentage'], 1) }}%</div>
            </td>
            <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #f5f5f5; border: 1px solid #ddd;">
                <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Total Score</div>
                <div style="font-size: 14px; font-weight: bold;">{{ number_format($summary['total_score'], 1) }}/{{ number_format($summary['max_score'], 1) }}</div>
            </td>
            <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #f5f5f5; border: 1px solid #ddd;">
                <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Score /100</div>
                <div style="font-size: 14px; font-weight: bold;">{{ number_format($summary['score_out_of_100'], 1) }}</div>
            </td>
            <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #f5f5f5; border: 1px solid #ddd;">
                <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Score /60</div>
                <div style="font-size: 14px; font-weight: bold;">{{ number_format($summary['score_out_of_60'], 1) }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- Section Breakdown --}}
@foreach($sections as $sectionIndex => $section)
    @if($sectionIndex > 0 && $sectionIndex % 2 === 0)
        <div class="page-break"></div>
    @endif

    <div class="section" style="margin-bottom: 15px;">
        <div class="section-title" style="padding: 5px 12px; font-size: 12px; margin-bottom: 8px; background-color: #003DA5;">
            {{ strtoupper($section['name']) }} -
            {{ number_format($section['total_score'], 1) }}/{{ number_format($section['max_score'], 1) }}
            ({{ number_format($section['percentage'], 1) }}%)
        </div>

        <table style="margin-bottom: 10px;">
            <thead>
            <tr>
                <th style="width: 35%; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Task</th>
                <th style="width: 10%; text-align: center; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Max</th>
                <th style="width: 10%; text-align: center; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Score</th>
                <th style="width: 15%; text-align: center; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Status</th>
                <th style="width: 30%; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Comments</th>
            </tr>
            </thead>
            <tbody>
            @foreach($section['tasks'] as $task)
                <tr>
                    <td style="padding: 4px 8px; font-size: 10px;">{{ $task['title'] }}</td>
                    <td style="text-align: center; padding: 4px 8px; font-size: 10px;">{{ $task['max_score'] }}</td>
                    <td style="text-align: center; padding: 4px 8px; font-size: 10px;">
                        @if($task['score'] !== null)
                            <strong>{{ number_format($task['score'], 1) }}</strong>
                        @else
                            N/A
                        @endif
                    </td>
                    <td style="text-align: center; padding: 4px 8px;">
                        @if($task['status'] === 'Submitted')
                            <span class="status-badge status-submitted">Submitted</span>
                        @else
                            <span class="status-badge status-not-submitted">Not Submitted</span>
                        @endif
                    </td>
                    <td style="padding: 4px 8px; font-size: 10px;">{{ $task['comments'] ?? 'No comments' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endforeach

{{-- Task Lists --}}
<div class="page-break"></div>

@if(count($submitted_tasks) > 0)
    <div class="section" style="margin-bottom: 15px;">
        <div class="section-title" style="padding: 5px 12px; font-size: 12px; margin-bottom: 8px; background-color: #003DA5;">SUBMITTED TASKS ({{ count($submitted_tasks) }})</div>
        <table style="margin-bottom: 10px;">
            <thead>
            <tr>
                <th style="width: 40%; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Task</th>
                <th style="width: 25%; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Section</th>
                <th style="width: 15%; text-align: center; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Score</th>
                <th style="width: 20%; text-align: center; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Submitted At</th>
            </tr>
            </thead>
            <tbody>
            @foreach($submitted_tasks as $task)
                <tr>
                    <td style="padding: 4px 8px; font-size: 10px;">{{ $task['title'] }}</td>
                    <td style="padding: 4px 8px; font-size: 10px;">{{ $task['section'] }}</td>
                    <td style="text-align: center; padding: 4px 8px; font-size: 10px;">
                        {{ $task['score'] ?? 'Not graded' }} / {{ $task['max_score'] }}
                    </td>
                    <td style="text-align: center; padding: 4px 8px; font-size: 10px;">
                        {{ $task['submitted_at']?->format('M d, Y H:i') ?? 'N/A' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(count($not_submitted_tasks) > 0)
    <div class="section" style="margin-bottom: 15px;">
        <div class="section-title" style="padding: 5px 12px; font-size: 12px; margin-bottom: 8px; background-color: #003DA5;">NOT SUBMITTED TASKS ({{ count($not_submitted_tasks) }})</div>
        <table style="margin-bottom: 10px;">
            <thead>
            <tr>
                <th style="width: 50%; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Task</th>
                <th style="width: 35%; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Section</th>
                <th style="width: 15%; text-align: center; padding: 5px 8px; font-size: 10px; background-color: #003DA5;">Max Score</th>
            </tr>
            </thead>
            <tbody>
            @foreach($not_submitted_tasks as $task)
                <tr>
                    <td style="padding: 4px 8px; font-size: 10px;">{{ $task['title'] }}</td>
                    <td style="padding: 4px 8px; font-size: 10px;">{{ $task['section'] }}</td>
                    <td style="text-align: center; padding: 4px 8px; font-size: 10px;">{{ $task['max_score'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="footer" style="margin-top: 20px; padding-top: 8px;">
    <p style="margin: 0 0 3px 0;">This is an official report of the MG Coordinator for the Ogun Conference.</p>
    <p style="margin: 0;">© {{ now()->year }} - All Rights Reserved</p>
</div>
</body>
</html>
