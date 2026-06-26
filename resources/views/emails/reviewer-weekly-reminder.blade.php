<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Review Reminder — {{ $portalName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 640px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 24px; font-weight: 700; margin-top: 12px; }
        .header p  { color: rgba(255,255,255,.9); margin-top: 6px; font-size: 14px; }
        .body { padding: 36px 32px; }
        .greeting { font-size: 20px; font-weight: 600; margin-bottom: 16px; }
        .text { color: #4b5563; line-height: 1.7; margin-bottom: 16px; font-size: 15px; }
        .stat { text-align: center; margin: 24px 0; }
        .stat-number { font-size: 48px; font-weight: 800; color: #f59e0b; line-height: 1; }
        .stat-label { font-size: 14px; color: #9ca3af; margin-top: 4px; }
        .table-wrap { overflow-x: auto; margin: 24px 0; border-radius: 10px; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead { background: #fffbeb; }
        thead th { padding: 10px 14px; text-align: left; font-weight: 600; color: #92400e; border-bottom: 1px solid #fde68a; white-space: nowrap; }
        tbody td { padding: 10px 14px; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        tbody tr:last-child td { border-bottom: none; }
        .candidate { font-weight: 600; color: #111827; }
        .task { color: #0284c7; }
        .meta { color: #9ca3af; font-size: 11px; margin-top: 2px; }
        .waiting { color: #f59e0b; font-size: 11px; font-weight: 500; }
        .btn-wrap { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background: #f59e0b; color: #fff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600; font-size: 15px; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 32px; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; line-height: 1.6; }
        @media (max-width: 480px) {
            .wrapper { margin: 0; border-radius: 0; }
            .header, .body { padding: 24px 16px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div style="font-size:48px;">⏰</div>
        <h1>Weekly Review Reminder</h1>
        <p>{{ now()->format('l, F j, Y') }}</p>
    </div>
    <div class="body">
        <p class="greeting">Hello, {{ $reviewerName }} 👋</p>
        <p class="text">
            This is your weekly reminder that you have candidate submissions waiting
            for your review. The candidates are counting on your timely feedback
            to progress in the programme.
        </p>

        <div class="stat">
            <div class="stat-number">{{ $count }}</div>
            <div class="stat-label">submission{{ $count === 1 ? '' : 's' }} waiting in your queue</div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Candidate</th>
                    <th>Task / Section</th>
                    <th>Submitted</th>
                    <th>Waiting</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($submissions as $i => $s)
                    <tr>
                        <td style="color:#9ca3af;">{{ $i + 1 }}</td>
                        <td><div class="candidate">{{ $s['candidate'] }}</div></td>
                        <td>
                            <div class="task">{{ $s['task'] }}</div>
                            <div class="meta">{{ $s['section'] }}</div>
                        </td>
                        <td style="white-space:nowrap;">{{ $s['submitted'] }}</td>
                        <td><span class="waiting">{{ $s['waiting'] }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <p class="text">
            Please aim to complete your reviews as soon as possible. Candidates with
            submissions waiting more than 7 days are flagged as overdue in the admin
            dashboard.
        </p>

        <div class="btn-wrap">
            <a href="{{ $queueUrl }}" class="btn">Go to My Review Queue →</a>
        </div>
    </div>
    <div class="footer">
        <p>This is an automated weekly reminder from {{ $portalName }}.</p>
        <p>You receive this because you are a reviewer in the MG Training Programme.</p>
        <p>&copy; {{ date('Y') }} {{ $portalName }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
