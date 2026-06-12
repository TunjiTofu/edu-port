<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Submission Assigned — {{ $portalName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); padding: 40px 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 24px; font-weight: 700; margin-top: 12px; }
        .header p { color: rgba(255,255,255,.9); margin-top: 6px; font-size: 14px; }
        .body { padding: 36px 32px; }
        .greeting { font-size: 20px; font-weight: 600; color: #111827; margin-bottom: 16px; }
        .text { color: #4b5563; line-height: 1.7; margin-bottom: 16px; font-size: 15px; }
        .card { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .card p { margin-bottom: 6px; font-size: 14px; color: #374151; }
        .card strong { color: #111827; }
        .btn-wrap { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background: #0ea5e9; color: #fff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600; font-size: 15px; letter-spacing: .3px; }
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
        <div style="font-size:48px;">📥</div>
        <h1>New Submission Assigned</h1>
        <p>A candidate's work is waiting for your review</p>
    </div>
    <div class="body">
        <p class="greeting">Hello, {{ $reviewerName }} 👋</p>
        <p class="text">
            A new submission has been assigned to you for review. Here are the details:
        </p>

        <div class="card">
            <p><strong>👤 Candidate:</strong> {{ $candidateName }}</p>
            <p><strong>📝 Task:</strong> {{ $taskTitle }}</p>
            @if ($sectionName)
                <p><strong>📂 Section:</strong> {{ $sectionName }}</p>
            @endif
            @if ($programName)
                <p><strong>🎓 Program:</strong> {{ $programName }}</p>
            @endif
            @if ($submittedAt)
                <p><strong>📅 Submitted:</strong> {{ $submittedAt }}</p>
            @endif
        </div>

        <p class="text">
            Head over to your Review Queue to view the submission, score it against the
            task rubrics, and leave feedback for the candidate.
        </p>

        <div class="btn-wrap">
            <a href="{{ $queueUrl }}" class="btn">Open Review Queue →</a>
        </div>

        <hr class="divider">

        <p class="text" style="font-size: 13px; color: #9ca3af;">
            This submission now appears in your Review Queue, sorted by submission date.
        </p>
    </div>
    <div class="footer">
        <p>This is an automated message from {{ $portalName }}.</p>
        <p>&copy; {{ date('Y') }} {{ $portalName }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
