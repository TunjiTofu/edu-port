<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $announcement->title }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #059669 100%); padding: 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; margin-top: 8px; }
        .header p { color: rgba(255,255,255,.8); font-size: 13px; margin-top: 4px; }
        .body { padding: 32px; }
        .greeting { font-size: 16px; color: #374151; margin-bottom: 16px; }
        .announcement-box { background: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 0 8px 8px 0; padding: 20px; margin: 20px 0; }
        .announcement-title { font-size: 18px; font-weight: 700; color: #0c4a6e; margin-bottom: 12px; }
        .announcement-body { color: #374151; line-height: 1.7; font-size: 15px; }
        .announcement-body p { margin-bottom: 12px; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 32px; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; line-height: 1.6; }
        @media (max-width: 480px) {
            .wrapper { margin: 0; border-radius: 0; }
            .header, .body { padding: 24px 20px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div style="font-size:36px;">📢</div>
        <h1>{{ $portalName }}</h1>
        <p>Important Announcement</p>
    </div>

    <div class="body">
        <p class="greeting">Hello, {{ $recipient->name }},</p>

        <p style="color:#4b5563; margin-bottom:20px; font-size:14px;">
            You have a new announcement from the {{ $portalName }} team.
        </p>

        <div class="announcement-box">
            <div class="announcement-title">{{ $announcement->title }}</div>
            <div class="announcement-body">
                {!! $announcement->body !!}
            </div>
        </div>

        <p style="color:#9ca3af; font-size:13px; margin-top:24px;">
            This announcement was sent to: {{ $announcement->audienceLabel() }}<br>
            Sent on {{ $announcement->created_at->format('M j, Y') }}
        </p>
    </div>

    <div class="footer">
        <p>
            © {{ date('Y') }} {{ $portalName }}. All rights reserved.<br>
            This is an automated message — please do not reply to this email.
        </p>
    </div>
</div>
</body>
</html>
