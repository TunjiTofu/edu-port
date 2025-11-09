<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Intending MGs Detailed Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #003DA5;
            padding-bottom: 8px;
        }
        .header h1 {
            color: #003DA5;
            margin: 0 0 5px 0;
            font-size: 20px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            background-color: #003DA5;
            color: white;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th {
            background-color: #003DA5;
            color: white;
            padding: 5px 8px;
            text-align: left;
            font-size: 10px;
        }
        td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            font-size: 10px;
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
            background-color: #32CD32;
            color: white;
        }
        .status-not-submitted {
            background-color: #C00000;
            color: white;
        }
        .page-break {
            page-break-after: always;
        }
        .student-header {
            background-color: #003DA5;
            color: white;
            padding: 8px 12px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
{{-- Cover Page --}}
<div class="header">
    <h1>All Intending MGs Detailed Results</h1>
    <p style="margin: 0; font-size: 10px;">Generated on {{ now()->format('F d, Y') }}</p>
    <p style="margin: 5px 0 0 0; font-size: 12px; font-weight: bold; color: #003DA5;">Total Students: {{ count($students) }}</p>
</div>

{{-- Summary Table --}}
<div class="section">
    <div class="section-title">SUMMARY OF ALL INTENDING MGs</div>
    <table>
        <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 25%;">Intending MG Name</th>
            <th style="width: 20%;">Email</th>
            <th style="width: 15%;">Church</th>
            <th style="text-align: center; width: 8%;">Tasks</th>
            <th style="text-align: center; width: 7%;">Done</th>
            <th style="text-align: center; width: 10%;">Score</th>
            <th style="text-align: center; width: 5%;">/100</th>
            <th style="text-align: center; width: 5%;">/60</th>
        </tr>
        </thead>
        <tbody>
        @foreach($students as $index => $studentData)
            <tr>
                <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                <td><strong>{{ $studentData['student']['name'] }}</strong></td>
                <td>{{ $studentData['student']['email'] }}</td>
                <td>{{ $studentData['student']['church'] ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $studentData['summary']['total_tasks'] }}</td>
                <td style="text-align: center;">{{ $studentData['summary']['submitted_count'] }}</td>
                <td style="text-align: center;">
                    {{ number_format($studentData['summary']['total_score'], 1) }}/{{ number_format($studentData['summary']['max_score'], 1) }}
                </td>
                <td style="text-align: center;">{{ number_format($studentData['summary']['score_out_of_100'], 1) }}</td>
                <td style="text-align: center;">{{ number_format($studentData['summary']['score_out_of_60'], 1) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Individual Student Details --}}
@foreach($students as $studentIndex => $studentData)
    <div class="page-break"></div>

    {{-- Student Header --}}
    <div class="student-header">
        {{ $studentIndex + 1 }}. {{ $studentData['student']['name'] }} - Detailed Results
    </div>

    {{-- Student Information --}}
    <div class="section">
        <div class="section-title">INTENDING MG INFORMATION</div>
        <div style="padding: 8px 12px; background-color: #f5f5f5; border: 1px solid #ddd; font-size: 11px; line-height: 1.6;">
            <strong style="color: #003DA5;">{{ $studentData['student']['name'] }}</strong> | {{ $studentData['student']['email'] }} | {{ $studentData['student']['phone'] ?? 'N/A' }}<br>
            <strong>Church:</strong> {{ $studentData['student']['church'] ?? 'N/A' }} | <strong>District:</strong> {{ $studentData['student']['district'] ?? 'N/A' }}
        </div>
    </div>

    {{-- Overall Summary --}}
    <div class="section">
        <div class="section-title">OVERALL SUMMARY</div>

        <table style="margin-bottom: 0;">
            <tr>
                <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #E7F0FF; border: 1px solid #003DA5;">
                    <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Total Tasks</div>
                    <div style="font-size: 16px; font-weight: bold; color: #003DA5;">{{ $studentData['summary']['total_tasks'] }}</div>
                </td>
                <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #E2F0D9; border: 1px solid #32CD32;">
                    <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Submitted</div>
                    <div style="font-size: 16px; font-weight: bold; color: #32CD32;">{{ $studentData['summary']['submitted_count'] }}</div>
                </td>
                <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #FFE7E7; border: 1px solid #C00000;">
                    <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Not Done</div>
                    <div style="font-size: 16px; font-weight: bold; color: #C00000;">{{ $studentData['summary']['not_submitted_count'] }}</div>
                </td>
                <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #F2E7FF; border: 1px solid #7030A0;">
                    <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Complete %</div>
                    <div style="font-size: 16px; font-weight: bold; color: #7030A0;">{{ number_format($studentData['summary']['percentage'], 1) }}%</div>
                </td>
                <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #f5f5f5; border: 1px solid #ddd;">
                    <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Total Score</div>
                    <div style="font-size: 14px; font-weight: bold;">{{ number_format($studentData['summary']['total_score'], 1) }}/{{ number_format($studentData['summary']['max_score'], 1) }}</div>
                </td>
                <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #f5f5f5; border: 1px solid #ddd;">
                    <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Score /100</div>
                    <div style="font-size: 14px; font-weight: bold;">{{ number_format($studentData['summary']['score_out_of_100'], 1) }}</div>
                </td>
                <td style="width: 14.28%; text-align: center; padding: 8px; background-color: #f5f5f5; border: 1px solid #ddd;">
                    <div style="font-size: 8px; color: #666; margin-bottom: 2px;">Score /60</div>
                    <div style="font-size: 14px; font-weight: bold;">{{ number_format($studentData['summary']['score_out_of_60'], 1) }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Sections Breakdown --}}
    @foreach($studentData['sections'] as $section)
        <div class="section">
            <div class="section-title">
                {{ strtoupper($section['name']) }} -
                {{ number_format($section['total_score'], 1) }}/{{ number_format($section['max_score'], 1) }}
                ({{ number_format($section['percentage'], 1) }}%)
            </div>

            <table>
                <thead>
                <tr>
                    <th style="width: 35%;">Task</th>
                    <th style="width: 10%; text-align: center;">Max</th>
                    <th style="width: 10%; text-align: center;">Score</th>
                    <th style="width: 15%; text-align: center;">Status</th>
                    <th style="width: 30%;">Comments</th>
                </tr>
                </thead>
                <tbody>
                @foreach($section['tasks'] as $task)
                    <tr>
                        <td>{{ $task['title'] }}</td>
                        <td style="text-align: center;">{{ $task['max_score'] }}</td>
                        <td style="text-align: center;">
                            @if($task['score'] !== null)
                                <strong>{{ number_format($task['score'], 1) }}</strong>
                            @else
                                N/A
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($task['status'] === 'Submitted')
                                <span class="status-badge status-submitted">Submitted</span>
                            @else
                                <span class="status-badge status-not-submitted">Not Submitted</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($task['comments'] ?? 'No comments', 60) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endforeach

<div class="footer">
    <p style="margin: 0 0 3px 0;">This is an official report of the MG Coordinator for the Ogun Conference.</p>
    <p style="margin: 0;">© {{ now()->year }} - All Rights Reserved</p>
</div>
</body>
</html>
