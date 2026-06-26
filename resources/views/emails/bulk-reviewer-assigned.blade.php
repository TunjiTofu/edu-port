<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Submissions Assigned — {{ $portalName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 640px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); padding: 40px 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 24px; font-weight: 700; margin-top: 12px; }
        .header p  { color: rgba(255,255,255,.9); margin-top: 6px; font-size: 14px; }
        .body { padding: 36px 32px; }
        .greeting { font-size: 20px; font-weight: 600; margin-bottom: 16px; }
        .text { color: #4b5563; line-height: 1.7; margin-bottom: 16px; font-size: 15px; }
        .count-badge {
            display: inline-block; background: #0ea5e9; color: #fff;
            font-size: 28px; font-weight: 800; padding: 10px 28px;
            border-radius: 50px; margin-bottom: 28px;
        }
        .table-wrap { overflow-x: auto; margin: 24px 0; border-radius: 10px; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead { background: #f9fafb; }
        thead th { padding: 10px 14px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
        tbody td { padding: 10px 14px; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f9fafb; }
        .candidate { font-weight: 600; color: #111827; }
        .task { color: #0284c7; }
        .meta { color: #9ca3af; font-size: 11px; margin-top: 2px; }
        .btn-wrap { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background: #0ea5e9; color: #fff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600; font-size: 15px; }
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
        <div style="font-size:48px;">📥</div>
        <h1>New Submissions Assigned</h1>
        <p>You have been assigned a batch of submissions to review</p>
    </div>
    <div class="body">
        <p class="greeting">Hello, {{ $reviewerName }} 👋</p>
        <p class="text">
            The administrator has assigned the following
            <strong>{{ $count }} submission{{ $count === 1 ? '' : 's' }}</strong>
            to your review queue:
        </p>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Candidate</th>
                    <th>Task / Section</th>
                    <th>Submitted</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($submissions as $i => $s)
                    <tr>
                        <td style="color:#9ca3af;">{{ $i + 1 }}</td>
                        <td>
                            <div class="candidate">{{ $s['candidate'] }}</div>
                        </td>
                        <td>
                            <div class="task">{{ $s['task'] }}</div>
                            <div class="meta">{{ $s['section'] }} · {{ $s['program'] }}</div>
                        </td>
                        <td style="white-space:nowrap;">{{ $s['submitted'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <p class="text">
            Head over to your Review Queue to start reviewing — submissions are sorted
            oldest-first so you can work through them in the right order.
        </p>

        <div class="btn-wrap">
            <a href="{{ $queueUrl }}" class="btn">Open Review Queue →</a>
        </div>
    </div>
    <div class="footer">
        <p>This is an automated message from {{ $portalName }}.</p>
        <p>&copy; {{ date('Y') }} {{ $portalName }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
