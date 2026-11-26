<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Portfolio - {{ $student['name'] }}</title>
    @include('pdf.portfolio._styles')
    <style>
        .cover { text-align: center; padding-top: 18mm; }
        .cover .logo { width: 55mm; height: 55mm; margin-bottom: 10mm; }
        .cover .org1 { font-size: 17px; font-weight: bold; color: #003DA5; letter-spacing: .5px; }
        .cover .org2 { font-size: 14px; font-weight: bold; color: #1b2a4a; margin-top: 3mm; }
        .cover .org3 { font-size: 15px; font-weight: bold; color: #b8860b; margin-top: 3mm; letter-spacing: .5px; }
        .cover .rule { border-top: 3px double #003DA5; width: 60%; margin: 12mm auto; }
        .cover .doc  { font-size: 26px; font-weight: bold; color: #003DA5; letter-spacing: 2px; }
        .cover .name { font-size: 18px; margin-top: 8mm; color: #222; }
        .cover .sub  { font-size: 12px; margin-top: 2mm; color: #555; }
        .cover .gen  { font-size: 10px; color: #777; margin-top: 40mm; }
        .photo { width: 38mm; height: 46mm; border: 2px solid #003DA5; }
        .photo-placeholder { width: 38mm; height: 46mm; border: 2px dashed #9aa7c0; color: #9aa7c0;
                             text-align: center; line-height: 46mm; font-size: 10px; }
    </style>
</head>
<body>

{{-- ═════════════ PAGE 1: COVER ═════════════ --}}
<div class="cover">
    @if ($logo)
        <img src="{{ $logo }}" class="logo" alt="Master Guide logo">
    @endif
    <div class="org1">OGUN CONFERENCE OF SEVENTH-DAY ADVENTIST CHURCH</div>
    <div class="org2">ADVENTIST YOUTH MINISTRIES (AYM) DEPARTMENT</div>
    <div class="org3">MASTER GUIDE TRAINING PROGRAM {{ $header_year }}</div>

    <div class="rule"></div>

    <div class="doc">CANDIDATE PORTFOLIO</div>
    <div class="name">{{ $student['name'] }}</div>
    <div class="sub">{{ $student['church'] ?: '' }}@if($student['church'] && $student['district']), @endif{{ $student['district'] ?: '' }}</div>

    <div class="gen">Generated {{ $generated_at }}</div>
</div>

<div class="page-break"></div>

{{-- ═════════════ PAGE 2: CANDIDATE DETAILS ═════════════ --}}
<table class="strip"><tr>
    <td>Master Guide Training Program {{ $header_year }} &middot; Candidate Portfolio</td>
    <td class="right">{{ $program['label'] }}</td>
</tr></table>

<table style="margin-bottom: 8mm;">
    <tr>
        <td style="width: 44mm; vertical-align: top;">
            @if ($student['photo'])
                <img src="{{ $student['photo'] }}" class="photo" alt="Passport photo">
            @else
                <div class="photo-placeholder">No photo</div>
            @endif
        </td>
        <td style="vertical-align: top;">
            <div class="block-title">CANDIDATE DETAILS</div>
            <table class="details">
                <tr><td class="label">Full Name</td><td><strong>{{ $student['name'] }}</strong></td></tr>
                <tr><td class="label">Email</td><td>{{ $student['email'] }}</td></tr>
                <tr><td class="label">Phone</td><td>{{ $student['phone'] ?: 'N/A' }}</td></tr>
                <tr><td class="label">Church</td><td>{{ $student['church'] ?: 'N/A' }}</td></tr>
                <tr><td class="label">District</td><td>{{ $student['district'] ?: 'N/A' }}</td></tr>
                <tr><td class="label">MG Mentor</td><td>{{ $student['mg_mentor'] ?: 'N/A' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="block-title">PROGRAM</div>
<table class="details" style="margin-bottom: 8mm;">
    <tr>
        <td class="label">Program</td>
        <td>{{ $program['name'] }}@if($program['code']) <span class="muted">({{ $program['code'] }})</span>@endif</td>
    </tr>
    <tr><td class="label">Year</td><td>{{ $program['year'] ?: 'N/A' }}</td></tr>
    <tr><td class="label">Program Dates</td><td>{{ $program['start_date'] ?: '—' }} – {{ $program['end_date'] ?: '—' }}</td></tr>
    <tr><td class="label">Enrolled On</td><td>{{ $program['enrolled_at'] ?: 'N/A' }}</td></tr>
    <tr><td class="label">Enrollment Status</td><td>{{ $program['status'] ?: 'N/A' }}</td></tr>
</table>

<div class="block-title">OVERALL SUMMARY</div>
<table class="summary">
    <tr>
        <td><div class="k">Tasks Submitted</div><div class="v">{{ $summary['submitted'] }} / {{ $summary['total_tasks'] }}</div></td>
        <td><div class="k">Completed</div><div class="v">{{ $summary['completed'] }}</div></td>
        <td><div class="k">Resubmitted</div><div class="v">{{ $summary['resubmitted'] }}</div></td>
        <td>
            <div class="k">Score (completed tasks)</div>
            <div class="v">
                @if ($summary['percentage'] !== null)
                    {{ number_format($summary['total_score'], 1) }}/{{ number_format($summary['max_score'], 1) }}
                @else
                    —
                @endif
            </div>
            @if ($summary['percentage'] !== null)
                <div class="k">{{ number_format($summary['percentage'], 1) }}%</div>
            @endif
        </td>
    </tr>
</table>
<p class="note">
    This portfolio contains completed tasks only. Each one follows, with a results page in front of the
    candidate's own document. Tasks awaiting review or needing revision are not included.
</p>

@if (count($sections))
    <div class="block-title" style="margin-top: 8mm;">CONTENTS</div>
    <table class="details">
        @php($n = 0)
        @foreach ($sections as $section)
            @foreach ($section['tasks'] as $task)
                <tr>
                    <td class="label" style="width: 10%; text-align: center;">{{ ++$n }}</td>
                    <td>{{ $task['title'] }} <span class="muted">— {{ $section['name'] }}</span></td>
                    <td style="width: 22%; text-align: right;">
                        @if ($task['reviewed'])
                            {{ number_format($task['score'], 1) }} / {{ number_format($task['max_score'], 1) }}
                        @else
                            <span class="muted">Pending</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endforeach
    </table>
@endif

</body>
</html>
