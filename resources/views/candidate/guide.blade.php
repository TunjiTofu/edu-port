<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Guide — MG Portfolio</title>
    <meta name="description" content="A step-by-step guide for MG Portfolio candidates — from registration to program enrollment to task submission.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green-50: #f0fdf4; --green-100: #dcfce7; --green-500: #22c55e;
            --green-600: #16a34a; --green-700: #15803d; --green-800: #166534;
            --gold: #c9a84c; --gold-pale: #fffbeb;
            --ink: #0f1a0f; --ink-soft: #374151; --mist: #f6fbf6;
            --white: #fff; --radius: 14px;
            --shadow-sm: 0 2px 8px rgba(15,26,15,.06);
            --shadow-md: 0 8px 28px rgba(15,26,15,.10);
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'DM Sans', sans-serif; background: var(--mist); color: var(--ink); line-height: 1.7; }

        /* ── Top bar ── */
        .topbar {
            background: var(--green-800); color: rgba(255,255,255,.9);
            padding: 14px 24px; display: flex; align-items: center; gap: 16px;
        }
        .topbar a { color: rgba(255,255,255,.7); text-decoration: none; font-size: .82rem; transition: color .2s; }
        .topbar a:hover { color: #fff; }
        .topbar .sep { color: rgba(255,255,255,.3); }
        .topbar-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .topbar-brand img { height: 28px; filter: brightness(0) invert(1); }
        .topbar-brand span { font-family: 'Playfair Display', serif; font-size: .95rem; color: #fff; font-weight: 700; }

        /* ── Layout ── */
        .page-wrap { max-width: 860px; margin: 0 auto; padding: 48px 24px 96px; }

        /* ── Hero banner ── */
        .guide-hero {
            background: linear-gradient(135deg, var(--green-800), var(--green-700));
            border-radius: var(--radius); padding: 40px 40px 36px;
            margin-bottom: 48px; position: relative; overflow: hidden;
        }
        .guide-hero::after {
            content: ''; position: absolute;
            width: 300px; height: 300px; border-radius: 50%;
            right: -80px; top: -80px;
            background: rgba(255,255,255,.05); pointer-events: none;
        }
        .guide-hero .badge {
            display: inline-block; background: rgba(255,255,255,.15);
            color: rgba(255,255,255,.9); font-size: .68rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
            padding: 4px 12px; border-radius: 100px; margin-bottom: 16px;
        }
        .guide-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.7rem, 4vw, 2.4rem);
            color: #fff; line-height: 1.2; margin-bottom: 12px;
        }
        .guide-hero p { color: rgba(255,255,255,.75); font-size: .95rem; max-width: 520px; font-weight: 300; }
        .guide-hero .time-chip {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 20px; background: rgba(255,255,255,.12);
            color: rgba(255,255,255,.85); font-size: .78rem; font-weight: 600;
            padding: 6px 14px; border-radius: 100px;
        }

        /* ── Quick nav sidebar (desktop) ── */
        .content-grid {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 40px;
            align-items: start;
        }
        .sticky-nav {
            position: sticky; top: 24px;
            background: var(--white); border-radius: var(--radius);
            border: 1px solid rgba(34,197,94,.12);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        .sticky-nav h4 {
            font-family: 'DM Sans', sans-serif; font-size: .72rem;
            font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
            color: var(--green-600); margin-bottom: 14px;
        }
        .sticky-nav ul { list-style: none; }
        .sticky-nav ul li { margin-bottom: 2px; }
        .sticky-nav ul li a {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 10px; border-radius: 8px;
            font-size: .8rem; font-weight: 500; color: var(--ink-soft);
            text-decoration: none; transition: background .15s, color .15s;
        }
        .sticky-nav ul li a:hover { background: var(--green-50); color: var(--green-700); }
        .sticky-nav ul li a .sn { font-size: .68rem; font-weight: 700; color: var(--green-500); min-width: 18px; }
        .sticky-nav .cta-small {
            margin-top: 20px; padding-top: 16px;
            border-top: 1px solid rgba(34,197,94,.1);
        }
        .sticky-nav .cta-small a {
            display: block; text-align: center;
            background: var(--green-600); color: #fff;
            padding: 10px; border-radius: 100px;
            font-size: .8rem; font-weight: 600; text-decoration: none;
            transition: background .2s;
        }
        .sticky-nav .cta-small a:hover { background: var(--green-700); }

        /* ── Steps ── */
        .steps { display: flex; flex-direction: column; gap: 0; }

        .step-block {
            display: flex; gap: 0; position: relative;
        }

        /* Vertical timeline line */
        .step-line-wrap {
            display: flex; flex-direction: column; align-items: center;
            width: 52px; flex-shrink: 0;
        }
        .step-num-circle {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--green-600); color: #fff;
            font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; box-shadow: 0 0 0 4px rgba(34,197,94,.15);
            position: relative; z-index: 1;
        }
        .step-connector { flex: 1; width: 2px; background: var(--green-100); margin: 4px 0; min-height: 24px; }
        .step-block:last-child .step-connector { display: none; }

        /* Card */
        .step-card {
            flex: 1; background: var(--white); border-radius: var(--radius);
            border: 1px solid rgba(34,197,94,.1);
            padding: 24px 28px; margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
            scroll-margin-top: 32px;
        }
        .step-card:hover { box-shadow: var(--shadow-md); transition: box-shadow .25s; }

        .step-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
        .step-icon { font-size: 1.5rem; flex-shrink: 0; margin-top: 2px; }
        .step-title-wrap {}
        .step-label {
            font-size: .66rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
            color: var(--green-600); margin-bottom: 3px;
        }
        .step-card h2 { font-family: 'DM Sans', sans-serif; font-size: 1.05rem; font-weight: 700; color: var(--ink); }

        .step-desc { color: var(--ink-soft); font-size: .88rem; margin-bottom: 18px; line-height: 1.75; }

        /* Sub-steps */
        .sub-steps { display: flex; flex-direction: column; gap: 10px; }
        .sub-step {
            display: flex; gap: 12px; align-items: flex-start;
            background: var(--mist); border-radius: 10px; padding: 12px 14px;
            border: 1px solid rgba(34,197,94,.08);
        }
        .sub-step-num {
            width: 22px; height: 22px; border-radius: 50%;
            background: var(--green-100); color: var(--green-700);
            font-size: .68rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: 1px;
        }
        .sub-step-body {}
        .sub-step-body strong { font-size: .85rem; font-weight: 600; color: var(--ink); display: block; margin-bottom: 2px; }
        .sub-step-body p { font-size: .8rem; color: var(--ink-soft); line-height: 1.6; }

        /* Note / tip / warning boxes */
        .note { display: flex; gap: 12px; padding: 12px 16px; border-radius: 10px; margin-top: 16px; font-size: .82rem; line-height: 1.65; }
        .note.tip     { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .note.warning { background: #fffbeb; border: 1px solid #fde68a; }
        .note.info    { background: #eff6ff; border: 1px solid #bfdbfe; }
        .note-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
        .note p { color: var(--ink-soft); }
        .note p strong { color: var(--ink); }

        /* Field table */
        .field-table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: .82rem; }
        .field-table th { text-align: left; font-size: .68rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #9ca3af; padding: 8px 12px; border-bottom: 2px solid var(--green-100); }
        .field-table td { padding: 10px 12px; border-bottom: 1px solid rgba(34,197,94,.07); color: var(--ink-soft); vertical-align: top; }
        .field-table td:first-child { font-weight: 600; color: var(--ink); white-space: nowrap; }
        .field-table tr:last-child td { border-bottom: none; }
        .req { color: #dc2626; font-size: .7rem; font-weight: 700; }

        /* Status pills */
        .status { display: inline-block; font-size: .7rem; font-weight: 600; padding: 2px 10px; border-radius: 100px; }
        .s-pending  { background: #eff6ff; color: #1d4ed8; }
        .s-review   { background: #fef9c3; color: #a16207; }
        .s-done     { background: #dcfce7; color: var(--green-700); }
        .s-revision { background: #fce7f3; color: #9d174d; }
        .s-flagged  { background: #fee2e2; color: #b91c1c; }

        /* Final CTA */
        .final-cta {
            background: linear-gradient(135deg, var(--green-700), var(--green-800));
            border-radius: var(--radius); padding: 40px; text-align: center; margin-top: 8px;
        }
        .final-cta h2 { font-family: 'Playfair Display', serif; color: #fff; font-size: 1.6rem; margin-bottom: 10px; }
        .final-cta p  { color: rgba(255,255,255,.7); font-size: .9rem; margin-bottom: 24px; }
        .final-cta a  { display: inline-block; background: #fff; color: var(--green-700); font-weight: 700; font-size: .9rem; padding: 12px 28px; border-radius: 100px; text-decoration: none; box-shadow: 0 4px 16px rgba(0,0,0,.15); transition: transform .2s, box-shadow .2s; }
        .final-cta a:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); }

        /* ── Mobile ── */
        @media (max-width: 720px) {
            .content-grid { grid-template-columns: 1fr; }
            .sticky-nav { display: none; }
            .page-wrap { padding: 32px 16px 64px; }
            .guide-hero { padding: 28px 24px; }
            .step-card { padding: 18px 16px; }
        }
    </style>
</head>
<body>

{{-- ── Top bar ── --}}
<div class="topbar">
    <a href="/" class="topbar-brand">
        <img src="{{ asset('images/logo.png') }}" alt="MG Portfolio">
        <span>MG Portfolio</span>
    </a>
    <span class="sep">›</span>
    <a href="{{ route('candidate.guide') }}">Candidate Guide</a>
</div>

<div class="page-wrap">

    {{-- ── Hero ── --}}
    <div class="guide-hero">
        <div class="badge">📖 Step-by-Step Guide</div>
        <h1>From Registration to Submission</h1>
        <p>Everything a new candidate needs to know to register, enrol in a program, and start submitting portfolio tasks on MG Portfolio.</p>
        <div class="time-chip">⏱ 5-minute read</div>
    </div>

    <div class="content-grid">

        {{-- ── Sidebar ── --}}
        <aside>
            <div class="sticky-nav">
                <h4>On this page</h4>
                <ul>
                    <li><a href="#step-1"><span class="sn">01</span> Create an Account</a></li>
                    <li><a href="#step-2"><span class="sn">02</span> Verify Your Phone</a></li>
                    <li><a href="#step-3"><span class="sn">03</span> Complete Your Profile</a></li>
                    <li><a href="#step-4"><span class="sn">04</span> Enrol in a Program</a></li>
                    <li><a href="#step-5"><span class="sn">05</span> Understand Your Tasks</a></li>
                    <li><a href="#step-6"><span class="sn">06</span> Submit a Task</a></li>
                    <li><a href="#step-7"><span class="sn">07</span> Track Your Results</a></li>
                    <li><a href="#step-8"><span class="sn">08</span> Need Help?</a></li>
                </ul>
                <div class="cta-small">
                    <a href="{{ route('candidate.register') }}">Register Now →</a>
                </div>
            </div>
        </aside>

        {{-- ── Steps ── --}}
        <main>
            <div class="steps">

                {{-- Step 1 --}}
                <div class="step-block">
                    <div class="step-line-wrap">
                        <div class="step-num-circle">1</div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="step-card" id="step-1">
                        <div class="step-header">
                            <div class="step-icon">📝</div>
                            <div class="step-title-wrap">
                                <div class="step-label">Step 01 — Registration</div>
                                <h2>Create Your Candidate Account</h2>
                            </div>
                        </div>
                        <p class="step-desc">
                            Go to <a href="{{ route('candidate.register') }}" style="color:var(--green-700);font-weight:600;">portdev.gratus.com.ng/candidate/register</a> and fill in the registration form. All fields are required.
                        </p>
                        <table class="field-table">
                            <thead>
                            <tr><th>Field</th><th>What to enter</th></tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Passport Photo <span class="req">*</span></td>
                                <td>A clear, front-facing photo of yourself. JPEG or PNG only, max 1 MB, minimum 200×200 px. Tap the circle icon to upload.</td>
                            </tr>
                            <tr>
                                <td>Full Name <span class="req">*</span></td>
                                <td>Your complete legal name as it should appear on your portfolio.</td>
                            </tr>
                            <tr>
                                <td>Email Address <span class="req">*</span></td>
                                <td>A valid email you check regularly — your welcome message will be sent here.</td>
                            </tr>
                            <tr>
                                <td>MG Mentor <span class="req">*</span></td>
                                <td>Full name of the Master Guide who is mentoring you through the Master Guide program.</td>
                            </tr>
                            <tr>
                                <td>Phone Number <span class="req">*</span></td>
                                <td>Your 11-digit Nigerian mobile number (e.g. 08012345678). A verification code will be sent here — make sure this number can receive SMS.</td>
                            </tr>
                            <tr>
                                <td>District <span class="req">*</span></td>
                                <td>Select your church district from the dropdown.</td>
                            </tr>
                            <tr>
                                <td>Church <span class="req">*</span></td>
                                <td>Select your local church. This list only appears after you choose a district.</td>
                            </tr>
                            <tr>
                                <td>Password <span class="req">*</span></td>
                                <td>Choose a secure password of at least 8 characters. Use the eye icon to reveal what you have typed.</td>
                            </tr>
                            <tr>
                                <td>Terms &amp; Conditions <span class="req">*</span></td>
                                <td>Read and tick the checkbox to confirm you accept the program terms.</td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="note tip" style="margin-top:18px;">
                            <span class="note-icon">💡</span>
                            <p><strong>Tip:</strong> Double-check your phone number before submitting. A 6-digit code will be sent to it in the next step and it cannot be changed until you complete registration.</p>
                        </div>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="step-block">
                    <div class="step-line-wrap">
                        <div class="step-num-circle">2</div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="step-card" id="step-2">
                        <div class="step-header">
                            <div class="step-icon">📱</div>
                            <div class="step-title-wrap">
                                <div class="step-label">Step 02 — Verification</div>
                                <h2>Verify Your Phone Number</h2>
                            </div>
                        </div>
                        <p class="step-desc">
                            After submitting the registration form you will be taken to a verification screen. A 6-digit code will be sent to your phone via SMS.
                        </p>
                        <div class="sub-steps">
                            <div class="sub-step">
                                <div class="sub-step-num">1</div>
                                <div class="sub-step-body">
                                    <strong>Enter the 6-digit code</strong>
                                    <p>Type the code into the large input box. The page will auto-submit as soon as all 6 digits are entered — you do not need to press a button.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">2</div>
                                <div class="sub-step-body">
                                    <strong>Code expires in 10 minutes</strong>
                                    <p>A countdown timer shows how long remains. The code turns orange at 60 seconds and red at 30 seconds. If it expires, the input field locks and the resend buttons unlock.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">3</div>
                                <div class="sub-step-body">
                                    <strong>Didn't receive the code?</strong>
                                    <p>Wait for the countdown to expire, then tap <strong>Resend via SMS</strong>. If SMS still does not arrive, tap <strong>Call Me Instead</strong> — an automated voice call will read the code aloud. You can request a maximum of 3 resends per 10 minutes.</p>
                                </div>
                            </div>
                        </div>
                        <div class="note warning">
                            <span class="note-icon">⚠️</span>
                            <p><strong>Important:</strong> Do not share your OTP with anyone. The MG Portfolio team will never ask for it.</p>
                        </div>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="step-block">
                    <div class="step-line-wrap">
                        <div class="step-num-circle">3</div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="step-card" id="step-3">
                        <div class="step-header">
                            <div class="step-icon">👤</div>
                            <div class="step-title-wrap">
                                <div class="step-label">Step 03 — Profile</div>
                                <h2>Complete Your Profile</h2>
                            </div>
                        </div>
                        <p class="step-desc">
                            Once your phone is verified and your account is created, you will be redirected to the login page. Log in and you will be prompted to complete your profile before accessing anything else.
                        </p>
                        <div class="sub-steps">
                            <div class="sub-step">
                                <div class="sub-step-num">1</div>
                                <div class="sub-step-body">
                                    <strong>Log in at /student/login</strong>
                                    <p>Enter the email and password you used during registration.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">2</div>
                                <div class="sub-step-body">
                                    <strong>Fill in any missing profile fields</strong>
                                    <p>Your name, email, and district are pre-filled. Ensure your phone number, church, MG mentor name, and passport photo are all saved. Your profile is considered complete when all four of these are set.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">3</div>
                                <div class="sub-step-body">
                                    <strong>Save your profile</strong>
                                    <p>Click <strong>Save Changes</strong>. You will be taken to your dashboard once your profile is complete.</p>
                                </div>
                            </div>
                        </div>
                        <div class="note info">
                            <span class="note-icon">ℹ️</span>
                            <p>Your profile picture appears in the top-right corner of the portal navigation bar and on your admin's user list. Use a clear, passport photo of yourself on Youth Uniform.</p>
                        </div>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="step-block">
                    <div class="step-line-wrap">
                        <div class="step-num-circle">4</div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="step-card" id="step-4">
                        <div class="step-header">
                            <div class="step-icon">🎓</div>
                            <div class="step-title-wrap">
                                <div class="step-label">Step 04 — Enrollment</div>
                                <h2>Enrol in a Training Program</h2>
                            </div>
                        </div>
                        <p class="step-desc">
                            Before you can access tasks, you must enrol in a training program. Programs are created by the admin and may open or close based on capacity.
                        </p>
                        <div class="sub-steps">
                            <div class="sub-step">
                                <div class="sub-step-num">1</div>
                                <div class="sub-step-body">
                                    <strong>Go to "Available Programs" in the sidebar</strong>
                                    <p>This page lists all currently open training programs you are eligible to join.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">2</div>
                                <div class="sub-step-body">
                                    <strong>Review the program details</strong>
                                    <p>Each program card shows the program name, description, and image. Tap the card to view full details.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">3</div>
                                <div class="sub-step-body">
                                    <strong>Click "Enrol"</strong>
                                    <p>Confirm your enrollment in the modal that appears. You cannot enrol in more than one active program at the same time.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">4</div>
                                <div class="sub-step-body">
                                    <strong>Your tasks now appear in the sidebar</strong>
                                    <p>Once enrolled, the <strong>Tasks</strong> menu item becomes active and you can see all tasks assigned to your program, organised by section.</p>
                                </div>
                            </div>
                        </div>
                        <div class="note warning">
                            <span class="note-icon">⚠️</span>
                            <p><strong>Note:</strong> If no programs are listed, the enrollment window may not have opened yet, or all available spots are filled. Contact your administrator for guidance.</p>
                        </div>
                    </div>
                </div>

                {{-- Step 5 --}}
                <div class="step-block">
                    <div class="step-line-wrap">
                        <div class="step-num-circle">5</div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="step-card" id="step-5">
                        <div class="step-header">
                            <div class="step-icon">📋</div>
                            <div class="step-title-wrap">
                                <div class="step-label">Step 05 — Tasks</div>
                                <h2>Understand Your Tasks</h2>
                            </div>
                        </div>
                        <p class="step-desc">
                            Tasks are the individual portfolio requirements you must complete. They are grouped into <strong>sections</strong> within your program. Each task has a title, description, due date, maximum score, and grading rubrics.
                        </p>
                        <div class="sub-steps">
                            <div class="sub-step">
                                <div class="sub-step-num">1</div>
                                <div class="sub-step-body">
                                    <strong>Go to "Tasks" in the sidebar</strong>
                                    <p>You will see a table or card list of all tasks in your program, sorted by due date. Each row shows the task title, section, due date, and your current submission status.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">2</div>
                                <div class="sub-step-body">
                                    <strong>Understand the status icons</strong>
                                    <p>🔴 = overdue and not yet submitted &nbsp; 📝 = not yet submitted &nbsp; submitted tasks show a coloured status badge.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">3</div>
                                <div class="sub-step-body">
                                    <strong>Click a task to read it in full</strong>
                                    <p>The task detail view shows the full description, grading instructions, rubrics (what the reviewer will score), any attached files, and your submission history if you have already submitted.</p>
                                </div>
                            </div>
                        </div>
                        <div class="note tip">
                            <span class="note-icon">💡</span>
                            <p><strong>Pay close attention to rubrics.</strong> Each task has a set of grading criteria that your reviewer uses to score your work. Read them before preparing your submission — they tell you exactly what is expected.</p>
                        </div>
                        <div class="note warning" style="margin-top:10px;">
                            <span class="note-icon">⏰</span>
                            <p><strong>Deadlines are hard.</strong> Once the due date passes, the Submit button disappears and the task is permanently locked. There are no extensions — plan your submissions before the deadline.</p>
                        </div>
                    </div>
                </div>

                {{-- Step 6 --}}
                <div class="step-block">
                    <div class="step-line-wrap">
                        <div class="step-num-circle">6</div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="step-card" id="step-6">
                        <div class="step-header">
                            <div class="step-icon">📤</div>
                            <div class="step-title-wrap">
                                <div class="step-label">Step 06 — Submission</div>
                                <h2>Submit a Task</h2>
                            </div>
                        </div>
                        <p class="step-desc">
                            When you are ready to submit, open the task and click the green <strong>Submit</strong> button. A submission wizard will open. Follow the steps inside it.
                        </p>
                        <div class="sub-steps">
                            <div class="sub-step">
                                <div class="sub-step-num">1</div>
                                <div class="sub-step-body">
                                    <strong>Upload your file</strong>
                                    <p>Select the file that contains your portfolio evidence (written reflection, presentation, recorded video, photo evidence, etc.). Accepted formats and size limits are shown in the uploader. Make sure your file is clearly named.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">2</div>
                                <div class="sub-step-body">
                                    <strong>Add optional notes</strong>
                                    <p>Use the notes field to give your reviewer context about what you submitted — which Share Section option you used, any special circumstances, or a brief summary of your evidence.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">3</div>
                                <div class="sub-step-body">
                                    <strong>Confirm and submit</strong>
                                    <p>Review the summary in the wizard and click <strong>Submit Assignment</strong>. Your submission will appear in the task with a status of <span class="status s-pending">Pending Review</span>.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">4</div>
                                <div class="sub-step-body">
                                    <strong>Resubmit if needed (before review starts)</strong>
                                    <p>If your submission is still at <span class="status s-pending">Pending Review</span> and you realise you uploaded the wrong file, you can click <strong>Resubmit</strong> to replace it. This option disappears once a reviewer picks up your submission.</p>
                                </div>
                            </div>
                        </div>
                        <div class="note info">
                            <span class="note-icon">ℹ️</span>
                            <p><strong>Submission statuses explained:</strong></p>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;">
                                <div><span class="status s-pending">Pending Review</span> &nbsp; Submitted, waiting for a reviewer</div>
                                <div><span class="status s-review">Under Review</span> &nbsp; A reviewer is currently grading it</div>
                                <div><span class="status s-done">Completed</span> &nbsp; Graded — check your score once results are published</div>
                                <div><span class="status s-revision">Needs Revision</span> &nbsp; Reviewer requests changes — re-read feedback and resubmit</div>
                                <div><span class="status s-flagged">Flagged</span> &nbsp; Flagged for plagiarism — contact your coordinator immediately</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 7 --}}
                <div class="step-block">
                    <div class="step-line-wrap">
                        <div class="step-num-circle">7</div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="step-card" id="step-7">
                        <div class="step-header">
                            <div class="step-icon">📊</div>
                            <div class="step-title-wrap">
                                <div class="step-label">Step 07 — Results</div>
                                <h2>Track Your Progress &amp; Results</h2>
                            </div>
                        </div>
                        <p class="step-desc">
                            Your dashboard gives you a live view of how you are progressing through the program. Results are visible once an administrator publishes them.
                        </p>
                        <div class="sub-steps">
                            <div class="sub-step">
                                <div class="sub-step-num">1</div>
                                <div class="sub-step-body">
                                    <strong>Dashboard widgets</strong>
                                    <p>The dashboard shows your overall progress (tasks submitted vs. total), upcoming deadlines, recent submission statuses, and your performance chart over time.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">2</div>
                                <div class="sub-step-body">
                                    <strong>Viewing a result</strong>
                                    <p>When results are published, open the task and scroll to the Submission section. You will see your score (e.g. 8 / 10), the reviewer's rubric-by-rubric breakdown, and any written comments they left for you.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">3</div>
                                <div class="sub-step-body">
                                    <strong>Performance chart</strong>
                                    <p>The performance chart on your dashboard plots your scores as results are published. Use it to identify which areas you are strong in and which need more attention.</p>
                                </div>
                            </div>
                        </div>
                        <div class="note tip">
                            <span class="note-icon">💡</span>
                            <p><strong>Scores are not visible immediately after review</strong> — the administrator must publish results for a task before you can see them. This is done at a set time for all candidates, not individually.</p>
                        </div>
                    </div>
                </div>

                {{-- Step 8 --}}
                <div class="step-block">
                    <div class="step-line-wrap">
                        <div class="step-num-circle">8</div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="step-card" id="step-8">
                        <div class="step-header">
                            <div class="step-icon">🆘</div>
                            <div class="step-title-wrap">
                                <div class="step-label">Step 08 — Support</div>
                                <h2>Need Help?</h2>
                            </div>
                        </div>
                        <p class="step-desc">
                            If you run into any issue during registration or while using the portal, here is what to do.
                        </p>
                        <div class="sub-steps">
                            <div class="sub-step">
                                <div class="sub-step-num">1</div>
                                <div class="sub-step-body">
                                    <strong>Check the announcements widget on your dashboard</strong>
                                    <p>The admin broadcasts updates and notices to all candidates. Check this first — your question may already be answered there.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">2</div>
                                <div class="sub-step-body">
                                    <strong>Can't log in?</strong>
                                    <p>If you see "Your account has been deactivated" or "Your candidacy has been suspended", your account has been locked by an administrator. Contact your program coordinator directly — this cannot be resolved through the portal.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">3</div>
                                <div class="sub-step-body">
                                    <strong>OTP never arrived?</strong>
                                    <p>Wait for the full 10 minutes, then use the "Resend via SMS" button. If SMS consistently fails, use "Call Me Instead". If both fail, contact your administrator who can provide a backup code.</p>
                                </div>
                            </div>
                            <div class="sub-step">
                                <div class="sub-step-num">4</div>
                                <div class="sub-step-body">
                                    <strong>Technical errors during submission?</strong>
                                    <p>Check your file size (max varies by task) and file format. Try a different browser or clear your cache. If the issue persists, note the exact error message and send it to your administrator.</p>
                                </div>
                            </div>
                        </div>
                        <div class="note info">
                            <span class="note-icon">ℹ️</span>
                            <p><strong>Change your password</strong> at any time via the user menu (your name/photo in the top-right corner) → <strong>Change Password</strong>.</p>
                        </div>
                    </div>
                </div>

                {{-- Final CTA --}}
                <div class="final-cta">
                    <h2>Ready to begin?</h2>
                    <p>Create your candidate account now — it takes less than 3 minutes.</p>
                    <a href="{{ route('candidate.register') }}">Register as a Candidate →</a>
                </div>

            </div>{{-- /steps --}}
        </main>

    </div>{{-- /content-grid --}}
</div>{{-- /page-wrap --}}

<footer style="background:#060e06;color:rgba(255,255,255,.4);text-align:center;padding:28px 24px;font-size:.78rem;">
    <p>© {{ date('Y') }} <strong style="color:rgba(255,255,255,.7);">MG Portfolio</strong> ·
        <a href="{{ route('candidate.terms') }}" style="color:rgba(255,255,255,.4);text-decoration:none;">Terms &amp; Conditions</a> ·
        <a href="/" style="color:rgba(255,255,255,.4);text-decoration:none;">Home</a>
    </p>
</footer>

<script>
    // Highlight active sidebar link on scroll
    const sections = document.querySelectorAll('.step-card[id]');
    const navLinks  = document.querySelectorAll('.sticky-nav a[href^="#"]');

    const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                const id = e.target.id;
                navLinks.forEach(a => {
                    const active = a.getAttribute('href') === '#' + id;
                    a.style.background = active ? 'var(--green-100)' : '';
                    a.style.color      = active ? 'var(--green-700)' : '';
                    a.style.fontWeight = active ? '700' : '500';
                });
            }
        });
    }, { rootMargin: '-30% 0px -60% 0px' });

    sections.forEach(s => io.observe(s));
</script>

</body>
</html>
