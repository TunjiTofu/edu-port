<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $portalName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #16a34a 0%, #059669 100%); padding: 40px 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 26px; font-weight: 700; margin-top: 12px; }
        .header p { color: rgba(255,255,255,.85); margin-top: 6px; font-size: 14px; }
        .body { padding: 36px 32px; }
        .greeting { font-size: 20px; font-weight: 600; color: #111827; margin-bottom: 16px; }
        .text { color: #4b5563; line-height: 1.7; margin-bottom: 16px; font-size: 15px; }
        .card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .card p { margin-bottom: 6px; font-size: 14px; color: #374151; }
        .card strong { color: #111827; }
        .btn-wrap { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background: #16a34a; color: #fff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600; font-size: 15px; letter-spacing: .3px; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 32px; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; line-height: 1.6; }
        @media (max-width: 480px) {
            .wrapper { margin: 0; border-radius: 0; }
            .header { padding: 28px 20px; }
            .body { padding: 24px 20px; }
            .footer { padding: 20px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div style="font-size:48px;">🎉 🎊 🥳</div>
        <h1>Welcome to the Ogun Conference Master Guide Portfolio Portal </h1>
        <p>Your candidate account is ready</p>
    </div>
    <div class="body">
        <p class="greeting">Hello, {{ $name }}! 👋</p>
        <p class="text">
            We're excited to have you join the MG Training program for the year {{ date('Y') }} . Your account has been created and verified successfully.
        </p>
        <p class="text">
            You can now log in to the Candidate Portal to view tasks, submit your portfolio, and track your progress.
        </p>

        <div class="card">
            <p><strong>📧 Email:</strong> {{ $email }}</p>
            <p><strong>🔗 Portal:</strong> Candidate Portal</p>
            <p><strong>📅 Account Created:</strong> {{ now()->format('M j, Y') }}</p>
        </div>

        <div class="btn-wrap">
            <a href="{{ $loginUrl }}" class="btn" style="color: #ffffff">Log In to Your Portal →</a>
        </div>

        <hr class="divider">

        <p class="text" style="font-size:13px; color:#6b7280;">
            If you didn't create this account, please ignore this email or contact your district coordinator immediately.
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
