<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MG Portfolio — Master Guide Candidate Portal</title>
    <meta name="description" content="The official digital portfolio platform for Master Guide candidates in the Seventh-day Adventist Church Ogun Conference. Submit tasks, track progress, and receive expert feedback.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-50:  #f0fdf4; --green-100: #dcfce7; --green-200: #bbf7d0;
            --green-400: #4ade80; --green-500: #22c55e; --green-600: #16a34a;
            --green-700: #15803d; --green-800: #166534; --green-900: #14532d;
            --gold: #c9a84c; --gold-light: #f0d685; --gold-pale: #fffbeb;
            --ink: #0f1a0f; --ink-soft: #2d3f2d; --mist: #f6fbf6;
            --white: #ffffff; --radius: 16px;
            --shadow-sm: 0 2px 8px rgba(15,26,15,.06);
            --shadow-md: 0 8px 32px rgba(15,26,15,.10);
            --shadow-lg: 0 24px 64px rgba(15,26,15,.14);
        }

        html { scroll-behavior: smooth; }
        body { font-family: 'DM Sans', sans-serif; background: var(--mist); color: var(--ink); line-height: 1.65; overflow-x: hidden; }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        .section    { padding: 96px 0; }
        .section-sm { padding: 64px 0; }
        h1,h2,h3,h4 { font-family: 'Playfair Display', serif; line-height: 1.2; }

        .pill {
            display: inline-block; font-size: .68rem; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: var(--green-700); background: var(--green-100);
            padding: 4px 14px; border-radius: 100px; margin-bottom: 16px;
        }
        .pill.gold { color: #92400e; background: var(--gold-pale); }
        .pill.dark { color: var(--green-400); background: rgba(34,197,94,.12); }

        /* ── Nav ── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            background: rgba(246,251,246,.88);
            border-bottom: 1px solid rgba(34,197,94,.12);
            height: 68px; display: flex; align-items: center;
        }
        .nav-inner {
            width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 24px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .nav-brand img { height: 40px; width: auto; object-fit: contain; }
        .nav-name { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--ink); font-weight: 700; }
        .nav-links { display: flex; gap: 28px; list-style: none; align-items: center; }
        .nav-links a { font-size: .82rem; font-weight: 500; color: var(--ink-soft); text-decoration: none; transition: color .2s; }
        .nav-links a:hover { color: var(--green-700); }

        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 22px; border-radius: 100px;
            font-size: .82rem; font-weight: 600; font-family: 'DM Sans', sans-serif;
            text-decoration: none; transition: all .2s; cursor: pointer; border: none;
        }
        .btn-primary { background: var(--green-600); color: var(--white); box-shadow: 0 4px 16px rgba(22,163,74,.28); }
        .btn-primary:hover { background: var(--green-700); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(22,163,74,.35); }
        .btn-outline { background: transparent; color: var(--green-700); border: 1.5px solid var(--green-500); }
        .btn-outline:hover { background: var(--green-50); transform: translateY(-1px); }
        .btn-gold { background: var(--gold); color: var(--white); box-shadow: 0 4px 16px rgba(201,168,76,.3); }
        .btn-gold:hover { background: #b8943a; transform: translateY(-1px); }

        /* ── Hero ── */
        .hero {
            min-height: 100vh; display: flex; align-items: center;
            padding-top: 68px; position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 70% 40%, rgba(34,197,94,.11) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 15% 80%, rgba(201,168,76,.08) 0%, transparent 50%),
                radial-gradient(ellipse 60% 80% at 92% 90%, rgba(22,163,74,.07) 0%, transparent 50%);
        }
        .hero-ring {
            position: absolute; border-radius: 50%; pointer-events: none;
            border: 1px solid rgba(34,197,94,.1);
        }
        .hero-ring-1 { width: 600px; height: 600px; right: -180px; top: 50%; transform: translateY(-50%); }
        .hero-ring-2 { width: 900px; height: 900px; right: -330px; top: 50%; transform: translateY(-50%); border-color: rgba(34,197,94,.05); }

        .hero-inner {
            position: relative;
            display: grid; grid-template-columns: 1.1fr .9fr; gap: 64px; align-items: center;
        }
        .hero-eyebrow { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .hero-eyebrow span { width: 28px; height: 2px; background: var(--gold); }
        .hero-eyebrow p { font-size: .72rem; letter-spacing: .12em; text-transform: uppercase; color: var(--gold); font-weight: 700; }
        .hero h1 { font-size: clamp(2.4rem, 5vw, 3.9rem); color: var(--ink); margin-bottom: 20px; }
        .hero h1 em { font-style: italic; color: var(--green-700); }
        .hero-desc { font-size: 1rem; color: var(--ink-soft); margin-bottom: 36px; max-width: 460px; font-weight: 300; }
        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .hero-stats { display: flex; gap: 36px; margin-top: 48px; padding-top: 32px; border-top: 1px solid rgba(15,26,15,.08); flex-wrap: wrap; }
        .stat-num { font-family: 'Playfair Display', serif; font-size: 1.9rem; font-weight: 700; color: var(--green-700); }
        .stat-label { font-size: .72rem; color: #6b7280; font-weight: 500; letter-spacing: .04em; }

        .hero-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .hero-card {
            background: var(--white); border-radius: var(--radius); padding: 22px;
            box-shadow: var(--shadow-sm); border: 1px solid rgba(34,197,94,.1);
            transition: transform .25s, box-shadow .25s;
        }
        .hero-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .hero-card:first-child { grid-column: span 2; display: flex; align-items: center; gap: 18px; padding: 24px; }
        .card-icon { width: 42px; height: 42px; flex-shrink: 0; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 19px; }
        .card-icon.g { background: var(--green-100); }
        .card-icon.o { background: rgba(201,168,76,.12); }
        .hero-card h4 { font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 600; margin-bottom: 4px; }
        .hero-card p  { font-size: .76rem; color: #6b7280; line-height: 1.5; }

        /* ── About MG section ── */
        .about-section { background: var(--white); border-top: 1px solid rgba(34,197,94,.08); }
        .about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; }
        .about-text h2 { font-size: clamp(1.6rem, 3vw, 2.5rem); margin-bottom: 20px; }
        .about-text p { color: var(--ink-soft); font-size: .95rem; margin-bottom: 16px; font-weight: 300; line-height: 1.8; }
        .about-quote {
            margin-top: 28px; padding: 24px 28px;
            background: var(--mist); border-left: 4px solid var(--gold);
            border-radius: 0 var(--radius) var(--radius) 0;
            font-style: italic; color: var(--ink-soft); font-size: .9rem; line-height: 1.8;
        }
        .about-pillars { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .pillar-card {
            background: var(--mist); border-radius: 14px; padding: 20px;
            border: 1px solid rgba(34,197,94,.1); text-align: center;
            transition: transform .2s, box-shadow .2s;
        }
        .pillar-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .pillar-icon { font-size: 1.8rem; margin-bottom: 10px; display: block; }
        .pillar-name { font-family: 'Playfair Display', serif; font-size: .95rem; font-weight: 700; color: var(--ink); margin-bottom: 4px; }
        .pillar-sub  { font-size: .72rem; color: var(--green-600); font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }

        /* ── Announcements ── */
        .ann-section { background: var(--mist); border-top: 1px solid rgba(34,197,94,.08); }
        .ann-grid { display: grid; gap: 18px; margin-top: 40px; }
        .ann-card {
            background: var(--white); border-radius: var(--radius); padding: 26px 30px;
            border-left: 4px solid var(--green-500); display: flex; gap: 18px;
            box-shadow: var(--shadow-sm); transition: box-shadow .2s;
        }
        .ann-card:hover { box-shadow: var(--shadow-md); }
        .ann-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--green-500); margin-top: 7px; flex-shrink: 0; box-shadow: 0 0 0 4px rgba(34,197,94,.12); }
        .ann-title { font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700; margin-bottom: 8px; }
        .ann-body  { font-size: .86rem; color: var(--ink-soft); line-height: 1.75; }
        .ann-meta  { display: flex; gap: 16px; margin-top: 10px; }
        .ann-date  { font-size: .7rem; color: #9ca3af; font-weight: 500; }
        .ann-author{ font-size: .7rem; color: var(--green-600); font-weight: 600; }

        /* ── How it works ── */
        .how-section { background: var(--ink); color: var(--white); }
        .how-section h2 { color: var(--white); }
        .steps-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 2px; margin-top: 56px;
            background: rgba(255,255,255,.05); border-radius: var(--radius); overflow: hidden;
        }
        .step { background: transparent; padding: 40px 28px; transition: background .25s; }
        .step:hover { background: rgba(255,255,255,.04); }
        .step-num { font-family: 'Playfair Display', serif; font-size: 3rem; font-weight: 900; color: rgba(34,197,94,.2); line-height: 1; margin-bottom: 18px; }
        .step h4 { font-family: 'DM Sans', sans-serif; font-size: .92rem; font-weight: 600; color: var(--white); margin-bottom: 8px; }
        .step p  { font-size: .8rem; color: rgba(200,200,200,1); line-height: 1.7; }

        /* ── Program Info tabs (Share + Timeline + Requirements) ── */
        .program-section { background: var(--white); }
        .big-tabs { display: flex; gap: 4px; border-bottom: 2px solid var(--green-100); margin-top: 40px; }
        .big-tab {
            padding: 12px 24px; border: none; background: transparent;
            font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 600;
            color: #9ca3af; cursor: pointer; border-bottom: 2px solid transparent;
            margin-bottom: -2px; transition: all .2s; white-space: nowrap;
        }
        .big-tab.active { color: var(--green-700); border-bottom-color: var(--green-600); }
        .big-tab:hover:not(.active) { color: var(--ink-soft); }

        .big-panel { display: none; padding: 40px 0; }
        .big-panel.active { display: block; }

        /* Share section */
        .share-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
        .share-card {
            background: var(--mist); border-radius: 14px; padding: 22px;
            border: 1px solid rgba(34,197,94,.1);
            display: flex; gap: 14px; align-items: flex-start;
            transition: transform .2s, box-shadow .2s;
        }
        .share-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .share-card-icon {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .share-card h4 { font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 600; margin-bottom: 4px; }
        .share-card p  { font-size: .78rem; color: #6b7280; line-height: 1.6; }
        .share-intro { background: var(--green-50); border-radius: var(--radius); padding: 24px 28px; margin-bottom: 28px; border: 1px solid var(--green-100); }
        .share-intro p { color: var(--ink-soft); font-size: .9rem; line-height: 1.75; }

        /* Timeline */
        .timeline-wrap { max-width: 680px; margin: 0 auto; }
        .tl-item { display: flex; gap: 0; margin-bottom: 0; }
        .tl-left { width: 48px; display: flex; flex-direction: column; align-items: center; flex-shrink: 0; }
        .tl-dot { width: 14px; height: 14px; border-radius: 50%; background: var(--green-500); flex-shrink: 0; box-shadow: 0 0 0 4px rgba(34,197,94,.15); margin-top: 16px; }
        .tl-line { flex: 1; width: 2px; background: var(--green-100); margin-top: 4px; margin-bottom: 4px; }
        .tl-item:last-child .tl-line { display: none; }
        .tl-content { flex: 1; padding: 12px 0 24px 16px; }
        .tl-card { background: var(--mist); border-radius: 12px; padding: 16px 20px; border: 1px solid rgba(34,197,94,.1); }
        .tl-card.special { background: linear-gradient(135deg, #dcfce7, #dbeafe); border-color: var(--green-300); }
        .tl-card h4 { font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 600; color: var(--ink); margin-bottom: 4px; }
        .tl-date { font-size: .8rem; font-weight: 600; color: var(--green-600); }
        .tl-card.special h4 { font-weight: 700; }
        .tl-card.special .tl-date { color: var(--green-700); font-size: .85rem; }

        /* Requirements */
        .req-intro { background: var(--mist); border-radius: var(--radius); padding: 24px 28px; margin-bottom: 36px; border-left: 4px solid var(--gold); }
        .req-intro p { font-size: .9rem; color: var(--ink-soft); line-height: 1.8; }
        .req-intro p strong { color: var(--ink); }
        .req-accordion { border: 1px solid rgba(34,197,94,.12); border-radius: var(--radius); overflow: hidden; margin-bottom: 12px; }
        .req-header {
            width: 100%; display: flex; align-items: center; justify-content: space-between;
            padding: 18px 24px; background: var(--mist); border: none; cursor: pointer;
            font-family: 'DM Sans', sans-serif; text-align: left; transition: background .2s;
        }
        .req-header:hover { background: var(--green-50); }
        .req-header.open { background: var(--green-50); }
        .req-title-wrap { display: flex; align-items: center; gap: 12px; }
        .req-icon-badge { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .req-header h3 { font-family: 'DM Sans', sans-serif; font-size: .92rem; font-weight: 700; color: var(--ink); }
        .req-header p  { font-size: .75rem; color: #6b7280; margin-top: 2px; }
        .req-chevron { font-size: 1rem; color: var(--green-600); transition: transform .3s; flex-shrink: 0; }
        .req-header.open .req-chevron { transform: rotate(180deg); }
        .req-body { display: none; padding: 0 24px 24px; background: var(--white); }
        .req-body.open { display: block; }
        .req-body ul { list-style: none; padding: 0; }
        .req-body ul li {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 8px 0; border-bottom: 1px solid rgba(34,197,94,.06);
            font-size: .84rem; color: var(--ink-soft); line-height: 1.6;
        }
        .req-body ul li:last-child { border-bottom: none; }
        .req-body ul li::before { content: '✓'; color: var(--green-600); font-weight: 700; flex-shrink: 0; margin-top: 1px; }
        .req-body ul li.sub { padding-left: 24px; }
        .req-body ul li.sub::before { content: '›'; }
        .req-body p.note { font-size: .8rem; color: #9ca3af; margin-top: 12px; font-style: italic; line-height: 1.6; }
        .req-body h4 { font-family: 'DM Sans', sans-serif; font-size: .82rem; font-weight: 700; color: var(--green-700); margin: 16px 0 8px; text-transform: uppercase; letter-spacing: .06em; }

        /* ── Tutorials ── */
        .tut-section { background: var(--mist); }
        .tab-row { display: flex; gap: 4px; background: var(--green-100); border-radius: 100px; padding: 4px; width: fit-content; margin: 36px 0; flex-wrap: wrap; }
        .tab-btn {
            padding: 8px 20px; border-radius: 100px; border: none; background: transparent;
            font-family: 'DM Sans', sans-serif; font-size: .8rem; font-weight: 600;
            color: var(--green-700); cursor: pointer; transition: all .2s;
        }
        .tab-btn.active { background: var(--white); box-shadow: var(--shadow-sm); color: var(--ink); }
        .tab-panel { display: none; }
        .tab-panel.active { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 22px; }
        .vid-card {
            background: var(--white); border-radius: var(--radius); overflow: hidden;
            box-shadow: var(--shadow-sm); border: 1px solid rgba(34,197,94,.08);
            text-decoration: none; display: block; transition: transform .25s, box-shadow .25s;
        }
        .vid-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .vid-thumb { position: relative; aspect-ratio: 16/9; overflow: hidden; background: var(--ink); }
        .vid-thumb img { width: 100%; height: 100%; object-fit: cover; transition: opacity .3s, transform .3s; }
        .vid-card:hover .vid-thumb img { transform: scale(1.04); opacity: .88; }
        .play-wrap { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
        .play-circle {
            width: 52px; height: 52px; background: rgba(255,255,255,.92); border-radius: 50%;
            box-shadow: 0 4px 20px rgba(0,0,0,.28); display: flex; align-items: center; justify-content: center;
            transition: transform .2s;
        }
        .vid-card:hover .play-circle { transform: scale(1.1); }
        .play-triangle { width: 0; height: 0; border-style: solid; border-width: 9px 0 9px 16px; border-color: transparent transparent transparent var(--green-600); margin-left: 3px; }
        .dur { position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,.72); color: #fff; font-size: .66rem; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
        .vid-info { padding: 18px 20px; }
        .vid-title { font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 600; color: var(--ink); margin-bottom: 5px; line-height: 1.4; }
        .vid-desc  { font-size: .76rem; color: #6b7280; line-height: 1.6; }

        /* ── Access cards ── */
        .access-section { background: var(--ink); }
        .access-section h2 { color: var(--white); }
        .access-section > .container > p { color: rgba(255,255,255,.5); font-size: .9rem; margin-bottom: 48px; }
        .access-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; }
        .access-card {
            border-radius: var(--radius); padding: 36px 26px; text-decoration: none;
            display: block; transition: transform .25s, box-shadow .25s; position: relative; overflow: hidden;
        }
        .access-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .access-card::after { content: '→'; position: absolute; bottom: 26px; right: 26px; font-size: 1.1rem; opacity: .35; transition: opacity .2s, transform .2s; }
        .access-card:hover::after { opacity: 1; transform: translateX(4px); }
        .access-card.cand { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid rgba(34,197,94,.2); }
        .access-card.rev  { background: linear-gradient(135deg, #fffbeb, #fef9c3); border: 1px solid rgba(234,179,8,.22); }
        .access-card.obs  { background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border: 1px solid rgba(14,165,233,.2); }
        .access-card.adm  { background: linear-gradient(135deg, #1e2b1e, #243024); border: 1px solid rgba(255,255,255,.08); }
        .ac-icon { font-size: 2.2rem; margin-bottom: 16px; display: block; }
        .ac-role { font-size: .66rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 8px; }
        .access-card.cand .ac-role { color: var(--green-600); }
        .access-card.rev  .ac-role { color: #a16207; }
        .access-card.obs  .ac-role { color: #0284c7; }
        .access-card.adm  .ac-role { color: var(--green-400); }
        .ac-title { font-family: 'Playfair Display', serif; font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; }
        .access-card.adm .ac-title { color: var(--white); }
        .access-card.adm .ac-desc  { color: rgba(255,255,255,.5); }
        .ac-desc { font-size: .79rem; line-height: 1.6; color: var(--ink-soft); }

        /* ── Footer ── */
        footer { background: #060e06; color: rgba(255,255,255,.4); text-align: center; padding: 36px 24px; font-size: .78rem; }
        footer strong { color: rgba(255,255,255,.75); }
        footer a { color: rgba(255,255,255,.4); text-decoration: none; }
        footer a:hover { color: rgba(255,255,255,.7); }

        /* ── Animations ── */
        @keyframes fadeUp { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }
        .fu { opacity:0; animation: fadeUp .6s ease forwards; }
        .fu1 { animation-delay:.08s; } .fu2 { animation-delay:.2s; }
        .fu3 { animation-delay:.32s; } .fu4 { animation-delay:.44s; }
        .fu5 { animation-delay:.56s; }

        /* ── Mobile ── */
        /* ── Hamburger button (mobile only) ── */
        .nav-hamburger {
            display: none;
            flex-direction: column; justify-content: center; align-items: center;
            width: 40px; height: 40px; border: none; background: transparent;
            cursor: pointer; gap: 5px; border-radius: 8px;
            transition: background .2s;
        }
        .nav-hamburger:hover { background: var(--green-100); }
        .nav-hamburger span {
            display: block; width: 22px; height: 2px;
            background: var(--ink); border-radius: 2px;
            transition: transform .3s, opacity .3s, width .3s;
            transform-origin: center;
        }
        /* X state */
        .nav-hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .nav-hamburger.open span:nth-child(2) { opacity: 0; width: 0; }
        .nav-hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* ── Mobile drawer ── */
        .mobile-menu {
            display: none; /* hidden on desktop */
            position: fixed; top: 68px; left: 0; right: 0; z-index: 99;
            background: rgba(246,251,246,.97);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(34,197,94,.14);
            padding: 0;
            max-height: 0; overflow: hidden;
            transition: max-height .35s ease, padding .35s ease;
        }
        .mobile-menu.open { max-height: 400px; padding: 8px 0 16px; }
        .mobile-menu ul { list-style: none; padding: 0 20px; }
        .mobile-menu ul li a {
            display: block; padding: 13px 12px;
            font-size: .95rem; font-weight: 500; color: var(--ink-soft);
            text-decoration: none; border-radius: 10px;
            transition: background .15s, color .15s;
        }
        .mobile-menu ul li a:hover { background: var(--green-100); color: var(--green-700); }
        .mobile-menu .mobile-register {
            margin: 8px 20px 4px;
        }
        .mobile-menu .mobile-register a {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 13px; border-radius: 100px;
            background: var(--green-600); color: var(--white);
            font-weight: 600; font-size: .9rem; text-decoration: none;
            box-shadow: 0 4px 14px rgba(22,163,74,.28);
            transition: background .2s;
        }
        .mobile-menu .mobile-register a:hover { background: var(--green-700); }

        @media (max-width: 900px) {
            .hero-inner { grid-template-columns: 1fr; gap: 40px; }
            .hero-cards { display: none; }
            .about-grid { grid-template-columns: 1fr; gap: 40px; }
            .steps-grid { grid-template-columns: 1fr 1fr; }
            /* Hide desktop links, show hamburger + mobile menu */
            .nav-links { display: none !important; }
            .btn.btn-primary.nav-register { display: none; } /* hide desktop register btn */
            .nav-hamburger { display: flex; }
            .mobile-menu { display: block; }
        }
        @media (max-width: 600px) {
            .steps-grid { grid-template-columns: 1fr; }
            .section { padding: 64px 0; }
            .about-pillars { grid-template-columns: 1fr 1fr; }
            .big-tabs { overflow-x: auto; }
            .big-tab { font-size: .8rem; padding: 10px 16px; }
        }
    </style>
</head>
<body>

{{-- ── Navigation ── --}}
<nav>
    <div class="nav-inner">
        <a href="/" class="nav-brand">
            <img src="{{ asset('images/logo.png') }}" alt="MG Portfolio Logo">
            <span class="nav-name">Ogun Conference MG Portfolio Portal</span>
        </a>
        <ul class="nav-links">
            <li><a href="#about">About MG</a></li>
            <li><a href="#requirements">Requirements</a></li>
            @if(isset($announcements) && $announcements->isNotEmpty())
                <li><a href="#announcements">Announcements</a></li>
            @endif
            <li><a href="#tutorials">Tutorials</a></li>
            <li><a href="#access">Sign In</a></li>
        </ul>
        <a href="{{ route('candidate.register') }}" class="btn btn-primary nav-register">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Register
        </a>
        {{-- Hamburger — visible on mobile only --}}
        <button class="nav-hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

{{-- ── Mobile menu drawer ── --}}
<div class="mobile-menu" id="mobile-menu" role="navigation" aria-label="Mobile navigation">
    <ul>
        <li><a href="#about"        onclick="closeMobileMenu()">About MG</a></li>
        <li><a href="#requirements" onclick="closeMobileMenu()">Requirements</a></li>
        @if(isset($announcements) && $announcements->isNotEmpty())
            <li><a href="#announcements" onclick="closeMobileMenu()">Announcements</a></li>
        @endif
        <li><a href="#tutorials"    onclick="closeMobileMenu()">Tutorials</a></li>
        <li><a href="#access"       onclick="closeMobileMenu()">Sign In</a></li>
    </ul>
    <div class="mobile-register">
        <a href="{{ route('candidate.register') }}" onclick="closeMobileMenu()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Register as Candidate
        </a>
    </div>
</div>

{{-- ── Hero ── --}}
<section class="hero">
    <div class="hero-ring hero-ring-1"></div>
    <div class="hero-ring hero-ring-2"></div>
    <div class="container">
        <div class="hero-inner">
            <div>
                <div class="hero-eyebrow fu fu1">
                    <span></span>
                    <p>Seventh-day Adventist Church of Ogun Conference · Youth Ministry</p>
                </div>
                <h1 class="fu fu2">
                    The <em>Apex</em> of<br>youth ministry<br>leadership.
                </h1>
                <p class="hero-desc fu fu3">
                    The Master Guide portfolio platform — submit your requirements, receive feedback, and document your journey to the highest level of youth leadership in the SDA Church.
                </p>
                <div class="hero-actions fu fu4">
                    <a href="{{ route('candidate.register') }}" class="btn btn-primary">Begin Your Journey →</a>
                    <a href="{{ route('candidate.guide') }}" class="btn btn-outline">Candidate Guide</a>
                </div>
                <div class="hero-stats fu fu5">
                    <div><div class="stat-num">4</div><div class="stat-label">Growth Pillars</div></div>
                    <div><div class="stat-num">1–3</div><div class="stat-label">Year Program</div></div>
                    <div><div class="stat-num">100%</div><div class="stat-label">Tracked Online</div></div>
                </div>
            </div>
            <div class="hero-cards fu fu3">
                <div class="hero-card">
                    <div class="card-icon g">📋</div>
                    <div>
                        <h4>Portfolio Submission</h4>
                        <p>Upload evidence for each requirement before the deadline. Track status in real time.</p>
                    </div>
                </div>
                <div class="hero-card">
                    <div class="card-icon o">🏆</div>
                    <h4>Expert Review</h4>
                    <p>Assigned reviewers grade with detailed rubrics and leave written feedback.</p>
                </div>
                <div class="hero-card">
                    <div class="card-icon g">📊</div>
                    <h4>Live Progress</h4>
                    <p>Your dashboard shows every task, score, and deadline across all sections.</p>
                </div>
                <div class="hero-card">
                    <div class="card-icon o">📣</div>
                    <h4>Announcements</h4>
                    <p>Admin broadcasts keep all candidates updated without separate emails.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── About Master Guide ── --}}
<section class="section about-section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-text">
                <div class="pill">What is Master Guide all about?</div>
                <h2>The highest level of youth leadership in the church.</h2>
                <p>
                    The Master Guide (MG) curriculum is the General Conference Youth Ministries Department's flagship leadership training program. It is the <strong>"Ph.D." of youth ministry</strong> — the expert, advisor, and promoter for Adventurers and Pathfinders.
                </p>
                <p>
                    MG is not a Pathfinder programme — it is a <strong>Youth Ministry Leadership Program</strong>. It focuses first and foremost on personal spiritual life and growth, then weaves general leadership skills into areas specifically geared to leading youth: understanding God's world of nature, outreach ministry, service to others, and healthy living.
                </p>
                <div class="about-quote">
                    "You can't teach what you don't know, and you can't lead where you won't go." — As leaders we must not only know theory; we must live what we preach and demonstrate it.
                </div>
            </div>
            <div>
                <div style="margin-bottom: 20px;">
                    <div class="pill gold">Four Pillars of Growth</div>
                </div>
                <div class="about-pillars">
                    <div class="pillar-card">
                        <span class="pillar-icon">🧠</span>
                        <div class="pillar-name">Wisdom</div>
                        <div class="pillar-sub">Leadership Identity</div>
                    </div>
                    <div class="pillar-card">
                        <span class="pillar-icon">💪</span>
                        <div class="pillar-name">Stature</div>
                        <div class="pillar-sub">Lifestyle Development</div>
                    </div>
                    <div class="pillar-card">
                        <span class="pillar-icon">🙏</span>
                        <div class="pillar-name">Favour with God</div>
                        <div class="pillar-sub">Spiritual Growth</div>
                    </div>
                    <div class="pillar-card">
                        <span class="pillar-icon">🤝</span>
                        <div class="pillar-name">Favour with Man</div>
                        <div class="pillar-sub">Community Development</div>
                    </div>
                </div>
                <div style="margin-top: 20px; padding: 20px 22px; background: var(--mist); border-radius: 14px; border: 1px solid rgba(34,197,94,.1);">
                    <p style="font-size:.82rem; color:#6b7280; line-height:1.7;">
                        <strong style="color:var(--ink);">Program Duration:</strong> 1–3 years. All prerequisites must be completed before beginning. Any requirements outside the 3-year limit must be repeated (does not apply to honours previously earned).
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Announcements ── --}}
@if(isset($announcements) && $announcements->isNotEmpty())
    <section class="section-sm ann-section" id="announcements">
        <div class="container">
            <div class="pill">📣 From the Coordinators</div>
            <h2 style="font-size: clamp(1.6rem, 3vw, 2.4rem); margin-bottom: 6px;">Announcements</h2>
            <p style="color:#6b7280; font-size:.88rem;">Latest updates from the MG Portfolio team.</p>
            <div class="ann-grid">
                @foreach ($announcements as $ann)
                    <div class="ann-card">
                        <div class="ann-dot"></div>
                        <div style="flex:1;min-width:0;">
                            <div class="ann-title">{{ $ann->title }}</div>
                            <div class="ann-body">{!! $ann->body !!}</div>
                            <div class="ann-meta">
                                <span class="ann-date">{{ $ann->created_at->format('M j, Y') }}</span>
                                @if($ann->author)<span class="ann-author">— {{ $ann->author->name }}</span>@endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── How it works ── --}}
<section class="section how-section">
    <div class="container">
        <div class="pill dark">Simple process</div>
        <h2 style="font-size: clamp(1.6rem, 3vw, 2.4rem);">How the portal works</h2>
        <div class="steps-grid">
            <div class="step">
                <div class="step-num">01</div>
                <h4>Register</h4>
                <p>Create your account with church, district, and mentor details. Verify your phone number via OTP.</p>
            </div>
            <div class="step">
                <div class="step-num">02</div>
                <h4>Enrol in a Program</h4>
                <p>Browse available training programs and enrol. Your sections and tasks appear on your dashboard.</p>
            </div>
            <div class="step">
                <div class="step-num">03</div>
                <h4>Submit Portfolio</h4>
                <p>Complete each task before the deadline and upload your evidence. Track submission status live.</p>
            </div>
            <div class="step">
                <div class="step-num">04</div>
                <h4>Get Reviewed</h4>
                <p>A reviewer grades your submission using rubrics. View scores and comments once results are published.</p>
            </div>
        </div>
    </div>
</section>

{{-- ── Program Info: Share / Timeline / Requirements ── --}}
<section class="section program-section" id="requirements">
    <div class="container">
        <div class="pill">Program Information</div>
        <h2 style="font-size: clamp(1.6rem, 3vw, 2.4rem); margin-bottom: 4px;">Everything you need to know</h2>
        <p style="color:#6b7280; font-size:.88rem;">Requirements, timeline, and how to document your evidence.</p>

        <div class="big-tabs">
            <button class="big-tab active" onclick="switchBig('requirements', this)">📋 Requirements</button>
            <button class="big-tab" onclick="switchBig('timeline', this)">📅 2026 Timeline</button>
            <button class="big-tab" onclick="switchBig('share', this)">🤝 How to Share</button>
        </div>

        {{-- REQUIREMENTS PANEL --}}
        <div class="big-panel active" id="big-requirements">
            <div class="req-intro">
                <p>
                    <strong>All prerequisites must be completed prior to beginning the program.</strong>
                    You must be a baptised SDA member, at least 16 years old, and have completed a background check and child protection course approved by your Conference/Mission.
                </p>
            </div>

            {{-- Prerequisites --}}
            <div class="req-accordion">
                <button class="req-header open" onclick="toggleReq(this)">
                    <div class="req-title-wrap">
                        <div class="req-icon-badge" style="background:#dcfce7;">✅</div>
                        <div>
                            <h3>Prerequisites</h3>
                            <p>Before you begin the program</p>
                        </div>
                    </div>
                    <span class="req-chevron">▲</span>
                </button>
                <div class="req-body open">
                    <ul>
                        <li>Be a baptised member, in regular standing, of the Seventh-day Adventist Church</li>
                        <li>Be at least 16 years of age to start; 18 years at Investiture</li>
                        <li>Complete a background check and child protection course, approved by your Conference/Mission</li>
                        <li>With your mentor, prayerfully discuss what it means to be a Master Guide. Include a written one-page report or video in your Portfolio</li>
                    </ul>
                    <h4>CMT Basic Staff Training Workshops Required</h4>
                    <ul>
                        <li>Club Ministry: Purpose &amp; History</li>
                        <li>Club Organisation</li>
                        <li>Programming &amp; Planning</li>
                        <li>Club Outreach</li>
                        <li>Ceremonies &amp; Drill</li>
                        <li>Developmental Growth</li>
                        <li>Introduction to Teaching</li>
                        <li>Medical, Risk Management, and Child Safety Issues</li>
                    </ul>
                    <p class="note">Candidates under 18 do not need a background check but must be supervised by an adult when working with minors.</p>
                </div>
            </div>

            {{-- Wisdom --}}
            <div class="req-accordion">
                <button class="req-header" onclick="toggleReq(this)">
                    <div class="req-title-wrap">
                        <div class="req-icon-badge" style="background:#dbeafe;">🧠</div>
                        <div>
                            <h3>Leadership Identity &amp; Growth</h3>
                            <p>Pillar: Wisdom</p>
                        </div>
                    </div>
                    <span class="req-chevron">▼</span>
                </button>
                <div class="req-body">
                    <ul>
                        <li>Complete Conference/Mission approved leadership training workshops</li>
                        <li>Read or listen to <em>Education</em> by Ellen White. Write a one-page reflection on what you learned and how to apply it in ministry</li>
                        <li>Read a book on Adventist leadership selected by your Conference/Mission and complete two Share Section options</li>
                        <li>For each of the following, complete a survey and write a two-page reflection paper:
                            <ul style="margin-top:6px;">
                                <li class="sub">Spiritual Gifts</li>
                                <li class="sub">Personalities</li>
                            </ul>
                        </li>
                        <li>For at least one year, be an active staff member in an Adventurer or Pathfinder Club, or teach a Sabbath School for these age groups, including:
                            <ul style="margin-top:6px;">
                                <li class="sub">Attend at least 75% of all staff meetings</li>
                                <li class="sub">Teach three Adventurer awards or two Pathfinder honours</li>
                                <li class="sub">Have or earn the Christian Storytelling honour</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Stature --}}
            <div class="req-accordion">
                <button class="req-header" onclick="toggleReq(this)">
                    <div class="req-title-wrap">
                        <div class="req-icon-badge" style="background:#dcfce7;">💪</div>
                        <div>
                            <h3>Lifestyle Development</h3>
                            <p>Pillar: Stature</p>
                        </div>
                    </div>
                    <span class="req-chevron">▼</span>
                </button>
                <div class="req-body">
                    <h4>Choose One Fitness Option</h4>
                    <ul>
                        <li>Have or earn the Physical Fitness honour</li>
                        <li>Have or earn the Sportsman Master Award</li>
                        <li>Complete the physical fitness section of the AY Silver or Gold Award</li>
                        <li>Complete at least a three-month fitness app programme (approved by mentor)</li>
                        <li>Complete a three-month physical fitness programme recommended by your doctor</li>
                    </ul>
                    <h4>Required Honours</h4>
                    <ul>
                        <li>Basic Water Safety</li>
                        <li>Camp Safety</li>
                        <li>Camping Skills I</li>
                        <li>Camping Skills II</li>
                        <li>Temperance</li>
                    </ul>
                    <h4>At Least Three of These Honours</h4>
                    <ul>
                        <li>Backpacking · Basic Rescue · Camping Skills III · Camping Skills IV</li>
                        <li>Drilling &amp; Marching · Ecology · Fire Building &amp; Camp Cookery</li>
                        <li>Knot Tying · Nutrition · Orienteering · Stewardship</li>
                    </ul>
                    <ul>
                        <li>Hold a current Red Cross First Aid &amp; CPR certificate or equivalent</li>
                    </ul>
                </div>
            </div>

            {{-- Spiritual --}}
            <div class="req-accordion">
                <button class="req-header" onclick="toggleReq(this)">
                    <div class="req-title-wrap">
                        <div class="req-icon-badge" style="background:#fef9c3;">🙏</div>
                        <div>
                            <h3>Spiritual Growth</h3>
                            <p>Pillar: Favour with God</p>
                        </div>
                    </div>
                    <span class="req-chevron">▼</span>
                </button>
                <div class="req-body">
                    <h4>Choose One Reading Plan</h4>
                    <ul>
                        <li>Read or listen to the four Gospels and <em>The Desire of Ages</em> by Ellen G. White — plus two Share options</li>
                        <li>Read or listen to the Encounter Plan, Series 1: Christ the Way — plus two Share options</li>
                    </ul>
                    <ul>
                        <li>Keep a devotional journal for at least one month, summarising daily growth</li>
                        <li>Read or listen to <em>Steps to Christ</em> by Ellen G. White — plus two Share options</li>
                        <li>Write a one-paragraph personal reflection on each of the 28 Fundamental Beliefs</li>
                    </ul>
                    <h4>Choose One Teaching Option</h4>
                    <ul>
                        <li>Teach a three-month Bible class/baptismal study</li>
                        <li>Teach five selected beliefs at a church-approved programme (Creation, Salvation, Growing in Christ, Baptism, Sabbath, Second Coming, etc.)</li>
                    </ul>
                    <h4>Choose One Sanctuary Option</h4>
                    <ul>
                        <li>Have or earn the Sanctuary honour — plus two Share options</li>
                        <li>Attend a Conference/Mission approved Sanctuary workshop — plus two Share options</li>
                    </ul>
                    <h4>Choose One Church Heritage Option</h4>
                    <ul>
                        <li>Have or earn the Adventist Pioneer Heritage honour</li>
                        <li>Watch the series <em>Tell the World</em></li>
                        <li>Watch the series <em>Keepers of the Flame</em></li>
                        <li>Read a Conference/Mission approved book on church heritage</li>
                    </ul>
                </div>
            </div>

            {{-- Community --}}
            <div class="req-accordion">
                <button class="req-header" onclick="toggleReq(this)">
                    <div class="req-title-wrap">
                        <div class="req-icon-badge" style="background:#f3e8ff;">🤝</div>
                        <div>
                            <h3>Community Development</h3>
                            <p>Pillar: Favour with Man</p>
                        </div>
                    </div>
                    <span class="req-chevron">▼</span>
                </button>
                <div class="req-body">
                    <ul>
                        <li>Have or earn the Personal Evangelism honour</li>
                        <li>Have or earn three of: Cultural Diversity Appreciation · Peacemaker · Social Media · One ADRA honour · One Household Arts honour</li>
                        <li>Participate in organising three social/fellowship events with your local church</li>
                    </ul>
                    <h4>Choose One Community Service Option</h4>
                    <ul>
                        <li>Meet with a local government agency, non-profit, or other organisation and participate in a community service project (involve your club or youth group where possible)</li>
                        <li>Work in an outreach initiative with your local ADRA coordinator (or equivalent) for a minimum of three months</li>
                    </ul>
                </div>
            </div>

            {{-- Investiture --}}
            <div class="req-accordion">
                <button class="req-header" onclick="toggleReq(this)">
                    <div class="req-title-wrap">
                        <div class="req-icon-badge" style="background:linear-gradient(135deg,#dcfce7,#dbeafe);">🎓</div>
                        <div>
                            <h3>Investiture Requirements</h3>
                            <p>Final steps before the ceremony</p>
                        </div>
                    </div>
                    <span class="req-chevron">▼</span>
                </button>
                <div class="req-body">
                    <ul>
                        <li>Have a written recommendation from your local church board, stating that you are a baptised member in regular standing</li>
                        <li>Complete all requirements of the Master Guide curriculum and pass a Portfolio review conducted by your Conference/Mission</li>
                        <li>The program must be completed in a minimum of 1 year and a maximum of 3 years. Requirements outside the 3-year limit must be repeated (does not apply to honours previously earned or candidates requiring medical accommodation)</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- TIMELINE PANEL --}}
        <div class="big-panel" id="big-timeline">
            <div class="timeline-wrap">
                @php
                    $events = [
                        ['April 13 - 30, 2026',                  'Registration of Candidates',                       false],
                        ['April 25, 2026',                  'Training of Trainers and Trainees',                false],
                        ['June 30, 2026',                   'First Submission of Portfolio',                    false],
                        ['August 31, 2026',                 'Second / Final Submission of Portfolio',           false],
                        ['September 01 – 30, 2026',         'Review / Assessment of Portfolio',                 false],
                        ['September 19, 2026',               'Short-listing of Qualified Candidates',            false],
                        ['September 26 – October 17, 2026',           'Interview of Candidates',                          false],
                        ['October 31, 2026',                'Release of List of Successful Candidates',         false],
                        ['November 1 – 21, 2026',  'Grooming of Successful Candidates',                false],
                        ['November 28, 2026',               'Investiture Ceremony',                             true],
                    ];
                @endphp
                @foreach ($events as [$date, $name, $special])
                    <div class="tl-item">
                        <div class="tl-left">
                            <div class="tl-dot" style="{{ $special ? 'background:var(--gold);box-shadow:0 0 0 5px rgba(201,168,76,.2)' : '' }}"></div>
                            <div class="tl-line"></div>
                        </div>
                        <div class="tl-content">
                            <div class="tl-card {{ $special ? 'special' : '' }}">
                                <h4>{{ $name }}</h4>
                                <div class="tl-date" style="{{ $special ? 'color:var(--gold);font-weight:700;' : '' }}">{{ $date }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- SHARE PANEL --}}
        <div class="big-panel" id="big-share">
            <div class="share-intro">
                <p>
                    <strong>Share What You Are Learning!</strong> For many requirements, you must include evidence in your portfolio. This can take many forms — choose what works best for you and your context.
                    Include pictures, written summaries, links, or recordings as proof of completion.
                </p>
            </div>
            <div class="share-grid">
                <div class="share-card">
                    <div class="share-card-icon" style="background:#dbeafe;">✍️</div>
                    <div>
                        <h4>Write a Reflection</h4>
                        <p>Write about a specific lesson or teaching that stood out to you. Include what you learned and how you will apply it.</p>
                    </div>
                </div>
                <div class="share-card">
                    <div class="share-card-icon" style="background:#dcfce7;">👥</div>
                    <div>
                        <h4>Group Discussion</h4>
                        <p>Share what you've learned in a group setting — youth group, small group, or Sabbath School class.</p>
                    </div>
                </div>
                <div class="share-card">
                    <div class="share-card-icon" style="background:#f3e8ff;">🖥</div>
                    <div>
                        <h4>Create a Presentation</h4>
                        <p>Share your knowledge through a creative format — PowerPoint, infographic, or illustrated handout.</p>
                    </div>
                </div>
                <div class="share-card">
                    <div class="share-card-icon" style="background:#fef9c3;">🎥</div>
                    <div>
                        <h4>Record &amp; Share</h4>
                        <p>Record a video or podcast summarising three ideas you learned and post it online. Share the link in your portfolio.</p>
                    </div>
                </div>
                <div class="share-card">
                    <div class="share-card-icon" style="background:#fce7f3;">💌</div>
                    <div>
                        <h4>Write Inspirational Cards</h4>
                        <p>Write three inspirational cards and give them to a friend who does not attend church.</p>
                    </div>
                </div>
                <div class="share-card">
                    <div class="share-card-icon" style="background:#dcfce7;">📲</div>
                    <div>
                        <h4>Post on Social Media</h4>
                        <p>Post three of your favourite quotes with brief commentary on social media or a personal blog. Screenshot and include in portfolio.</p>
                    </div>
                </div>
                <div class="share-card">
                    <div class="share-card-icon" style="background:#fff7ed;">🎤</div>
                    <div>
                        <h4>Present a Devotional</h4>
                        <p>Present a devotional to your club or youth group. Document it with notes, photos, or a recording.</p>
                    </div>
                </div>
                <div class="share-card">
                    <div class="share-card-icon" style="background:#f0fdf4;">✨</div>
                    <div>
                        <h4>Creative Option</h4>
                        <p>Share in another creative way approved by your Conference/Mission. Get it approved first, then include evidence in your portfolio.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Tutorials ── --}}
@if(!empty($tutorials))
    <section class="section tut-section" id="tutorials">
        <div class="container">
            <div class="pill">📹 Video Guides</div>
            <h2 style="font-size: clamp(1.6rem, 3vw, 2.4rem); margin-bottom: 4px;">How to use the portal</h2>
            <p style="color:#6b7280; font-size:.88rem;">Step-by-step video guides for every user group.</p>

            @php $groups = collect($tutorials)->groupBy('group'); @endphp
            <div class="tab-row">
                @foreach ($groups as $key => $vids)
                    <button class="tab-btn {{ $loop->first ? 'active' : '' }}" onclick="switchTab('{{ $key }}', this)">
                        {{ $vids->first()['group_label'] }}
                    </button>
                @endforeach
            </div>

            @foreach ($groups as $key => $vids)
                <div class="tab-panel {{ $loop->first ? 'active' : '' }}" id="tab-{{ $key }}">
                    @foreach ($vids as $v)
                        @php
                            preg_match('/(?:v=|youtu\.be\/|embed\/)([A-Za-z0-9_-]{11})/', $v['youtube_url'], $m);
                            $vid = $m[1] ?? null;
                            $thumb = $vid ? "https://img.youtube.com/vi/{$vid}/maxresdefault.jpg" : 'https://placehold.co/640x360/166534/ffffff?text=Video';
                        @endphp
                        <a href="{{ $v['youtube_url'] }}" target="_blank" rel="noopener noreferrer" class="vid-card">
                            <div class="vid-thumb">
                                <img src="{{ $thumb }}" alt="{{ $v['title'] }}" loading="lazy"
                                     onerror="this.src='https://img.youtube.com/vi/{{ $vid }}/hqdefault.jpg'">
                                <div class="play-wrap">
                                    <div class="play-circle"><div class="play-triangle"></div></div>
                                </div>
                                @if(!empty($v['duration']))<span class="dur">{{ $v['duration'] }}</span>@endif
                            </div>
                            <div class="vid-info">
                                <div class="vid-title">{{ $v['title'] }}</div>
                                @if(!empty($v['description']))<div class="vid-desc">{{ $v['description'] }}</div>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- ── Access ── --}}
<section class="section access-section" id="access">
    <div class="container">
        <div class="pill dark">Sign in</div>
        <h2 style="font-size: clamp(1.6rem, 3vw, 2.4rem); margin-bottom: 8px;">Access your portal</h2>
        <p>Each role has its own dedicated space.</p>
        <div class="access-grid">
            <a href="{{ url('/student/login') }}" class="access-card cand">
                <span class="ac-icon">🎓</span>
                <div class="ac-role">Candidate</div>
                <div class="ac-title">Candidate Portal</div>
                <div class="ac-desc">Submit portfolio tasks, track results, and manage your MG journey.</div>
            </a>
            <a href="{{ url('/reviewer/login') }}" class="access-card rev">
                <span class="ac-icon">✍️</span>
                <div class="ac-role">Reviewer</div>
                <div class="ac-title">Reviewer Portal</div>
                <div class="ac-desc">Review submissions, score with rubrics, and leave written feedback.</div>
            </a>
            <a href="{{ url('/observer/login') }}" class="access-card obs">
                <span class="ac-icon">👁</span>
                <div class="ac-role">Observer</div>
                <div class="ac-title">Observer Portal</div>
                <div class="ac-desc">Monitor candidate progress and view published results.</div>
            </a>
{{--            <a href="{{ url('/admin') }}" class="access-card adm">--}}
{{--                <span class="ac-icon">🛡</span>--}}
{{--                <div class="ac-role">Admin</div>--}}
{{--                <div class="ac-title">Admin Panel</div>--}}
{{--                <div class="ac-desc">Manage users, programs, tasks, and platform settings.</div>--}}
{{--            </a>--}}
        </div>
        <div style="text-align:center; margin-top:48px;">
            <p style="color:rgba(255,255,255,.45); font-size:.85rem; margin-bottom:16px;">
                New to MG Portfolio Portal? Register to begin your candidacy.
            </p>
            <a href="{{ route('candidate.register') }}" class="btn btn-gold">Create Candidate Account →</a>
        </div>
    </div>
</section>

{{-- ── Footer ── --}}
<footer>
    <p>
        © {{ date('Y') }} <strong>MG Portfolio</strong> — Seventh-day Adventist Church of Ogun Conference Youth Ministry.
        &nbsp;·&nbsp;
        <a href="{{ route('candidate.terms') }}">Terms &amp; Conditions</a>
        &nbsp;·&nbsp;
        <a href="{{ route('candidate.register') }}">Register</a>
    </p>
</footer>

<script>
    // ── Mobile menu ──────────────────────────────────────────────────────
    const hamburger   = document.getElementById('hamburger');
    const mobileMenu  = document.getElementById('mobile-menu');

    function closeMobileMenu() {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
    }

    hamburger.addEventListener('click', () => {
        const isOpen = mobileMenu.classList.toggle('open');
        hamburger.classList.toggle('open', isOpen);
        hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Close when clicking anywhere outside the nav / drawer
    document.addEventListener('click', (e) => {
        if (! e.target.closest('nav') && ! e.target.closest('.mobile-menu')) {
            closeMobileMenu();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMobileMenu();
    });

    // Requirements accordion
    function toggleReq(btn) {
        const body = btn.nextElementSibling;
        const open = body.classList.contains('open');
        btn.classList.toggle('open', !open);
        body.classList.toggle('open', !open);
        btn.querySelector('.req-chevron').textContent = open ? '▼' : '▲';
    }

    // Big tab switcher (Requirements / Timeline / Share)
    function switchBig(id, btn) {
        document.querySelectorAll('.big-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.big-tab').forEach(b => b.classList.remove('active'));
        document.getElementById('big-' + id).classList.add('active');
        btn.classList.add('active');
    }

    // Tutorial tab switcher
    function switchTab(group, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + group).classList.add('active');
        btn.classList.add('active');
    }

    // Scroll-triggered fade-up
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.opacity = '1';
                e.target.style.transform = 'translateY(0)';
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.08 });

    document.querySelectorAll(
        '.ann-card, .step, .vid-card, .access-card, .hero-card, .pillar-card, .share-card, .req-accordion, .tl-card'
    ).forEach(el => {
        if (!el.closest('.hero')) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(18px)';
            el.style.transition = 'opacity .5s ease, transform .5s ease';
            obs.observe(el);
        }
    });
</script>
</body>
</html>
