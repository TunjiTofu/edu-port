<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision Needed — {{ $portalName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 24px; font-weight: 700; margin-top: 12px; }
        .header p { color: rgba(255,255,255,.9); margin-top: 6px; font-size: 14px; }
        .body { padding: 36px 32px; }
        .greeting { font-size: 20px; font-weight: 600; color: #111827; margin-bottom: 16px; }
        .text { color: #4b5563; line-height: 1.7; margin-bottom: 16px; font-size: 15px; }
        .card { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .card p { margin-bottom: 6px; font-size: 14px; color: #374151; }
        .card strong { color: #111827; }
        .feedback {
            background: #f9fafb; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0;
            padding: 16px 20px; margin: 20px 0; font-size: 14px; color: #374151; line-height: 1.7;
            font-style: italic;
        }
        .btn-wrap { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background: #f59e0b; color: #fff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600; font-size: 15px; letter-spacing: .3px; }
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
        <div style="font-size:48px;">✏️</div>
        <h1>Revision Needed</h1>
        <p>Your reviewer has requested a few changes</p>
    </div>
    <div class="body">
        <p class="greeting">Hello, {{ $candidateName }} 👋</p>
        <p class="text">
            Your submission for the task below has been reviewed, and your reviewer
            has asked you to make some revisions before it can be approved. This is
            a normal part of the process — please review the feedback and resubmit
            when ready.
        </p>

        <div class="card">
            <p><strong>📝 Task:</strong> {{ $taskTitle }}</p>
            @if ($sectionName)
                <p><strong>📂 Section:</strong> {{ $sectionName }}</p>
            @endif
            @if ($dueDate)
                <p><strong>📅 Original Due Date:</strong> {{ $dueDate }}</p>
            @endif
        </div>

        @if ($comments)
            <p class="text" style="margin-bottom: 8px;"><strong>Reviewer's Feedback:</strong></p>
            <div class="feedback">
                "{{ $comments }}"
            </div>
        @endif

        <p class="text">
            Log in to your portal, review the comments on this task, and upload a
            revised submission whenever you're ready.
        </p>

        <div class="btn-wrap">
            <a href="{{ $taskUrl }}" class="btn">View Task &amp; Resubmit →</a>
        </div>

        <hr class="divider">

        <p class="text" style="font-size: 13px; color: #9ca3af;">
            If you have questions about this feedback, please reach out to your
            mentor or program coordinator.
        </p>
    </div>
    <div class="footer">
        <p>This is an automated message from {{ $portalName }}.</p>
        <p>&copy; {{ date('Y') }} {{ $portalName }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
