<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Verify Phone — MG Portfolio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-50 via-white to-emerald-50 flex flex-col justify-center py-8 px-4 sm:px-6">

<div class="text-center mb-6">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-600 rounded-2xl shadow-lg mb-3">
        <span class="text-3xl">📱</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">MG Portfolio</h1>
    <p class="text-sm text-gray-500 mt-1">Phone Verification</p>
</div>

<div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl overflow-hidden">

    {{-- Progress --}}
    <div class="flex">
        <div class="flex-1 h-1 bg-green-500"></div>
        <div class="flex-1 h-1 bg-green-500"></div>
    </div>
    <div class="flex justify-between px-6 pt-3 pb-1 text-xs">
        <span class="text-gray-400">Step 1 ✓</span>
        <span class="font-semibold text-green-600">Step 2 — Verify Phone</span>
    </div>

    <div class="px-6 pb-8 pt-3">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3">
                <span class="text-green-500 text-lg flex-shrink-0">✅</span>
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl flex gap-3">
                <span class="text-red-500 text-lg flex-shrink-0">❌</span>
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm font-semibold text-red-700 mb-1">Verification failed:</p>
                <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h2 class="text-lg font-semibold text-gray-900 mb-1">Enter your verification code</h2>
        <p class="text-sm text-gray-500 mb-6">
            We've sent a 6-digit code to your phone. Enter it below to activate your account.
        </p>

        {{-- OTP form --}}
        <form method="POST" action="{{ route('candidate.verify-otp.submit') }}" id="otp-form">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2 text-center">
                    6-Digit Code
                </label>
                <input
                    type="text"
                    name="otp"
                    id="otp"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code"
                    required
                    placeholder="000000"
                    autofocus
                    class="w-full text-center text-4xl font-bold tracking-[1.2rem] px-4 py-4 border-2 {{ $errors->has('otp') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-2xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <p class="text-xs text-center text-gray-400 mt-2">Code expires in <span id="countdown" class="font-semibold text-green-600">10:00</span></p>
            </div>

            <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3.5 rounded-xl text-sm transition shadow-md">
                Verify &amp; Create Account ✓
            </button>
        </form>

        {{-- Resend section — buttons locked until code expires --}}
        <div class="mt-6 p-4 bg-gray-50 rounded-xl">

            {{-- Status message --}}
            <div id="resend-locked-msg" class="text-center mb-3">
                <p class="text-sm text-gray-500 font-medium">
                    Your code is still active.
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    Resend options will become available once the current code expires.
                </p>
            </div>
            <div id="resend-unlocked-msg" class="text-center mb-3 hidden">
                <p class="text-sm font-semibold text-orange-600">
                    ⏰ Your code has expired.
                </p>
                <p class="text-xs text-gray-500 mt-0.5">
                    Request a new code via SMS or have us call you.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                {{-- Resend via SMS --}}
                <form method="POST" action="{{ route('candidate.resend-otp') }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="channel" value="sms">
                    <button type="submit" id="btn-sms" disabled
                            class="resend-btn w-full flex items-center justify-center gap-2 border border-gray-300 text-gray-400 font-medium py-2.5 px-4 rounded-lg text-sm transition cursor-not-allowed"
                            data-active-class="border-green-600 text-green-700 hover:bg-green-50 cursor-pointer"
                            data-inactive-class="border-gray-300 text-gray-400 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        Resend via SMS
                    </button>
                </form>

                {{-- Call Me Instead --}}
                <form method="POST" action="{{ route('candidate.resend-otp') }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="channel" value="voice">
                    <button type="submit" id="btn-voice" disabled
                            class="resend-btn w-full flex items-center justify-center gap-2 border border-gray-300 text-gray-400 font-medium py-2.5 px-4 rounded-lg text-sm transition cursor-not-allowed"
                            data-active-class="border-blue-600 text-blue-700 hover:bg-blue-50 cursor-pointer"
                            data-inactive-class="border-gray-300 text-gray-400 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Call Me Instead
                    </button>
                </form>
            </div>

            <p class="text-xs text-center text-gray-400 mt-2">
                "Call Me Instead" will place an automated voice call that reads your code aloud.
            </p>
        </div>

        <p class="text-center text-sm text-gray-400 mt-4">
            <a href="{{ route('candidate.register') }}" class="hover:text-gray-600 transition">← Back to registration</a>
        </p>
    </div>
</div>

<script>
    // ── State ─────────────────────────────────────────────────────────
    let seconds = 10 * 60;   // 10 minutes, matching OTP cache TTL
    let expired = false;

    const countdownEl      = document.getElementById('countdown');
    const lockedMsg        = document.getElementById('resend-locked-msg');
    const unlockedMsg      = document.getElementById('resend-unlocked-msg');
    const resendBtns       = document.querySelectorAll('.resend-btn');
    const otpInput         = document.getElementById('otp');

    // ── Countdown ─────────────────────────────────────────────────────
    const timer = setInterval(() => {
        seconds--;

        const m = Math.floor(seconds / 60).toString().padStart(2, '0');
        const s = (seconds % 60).toString().padStart(2, '0');
        countdownEl.textContent = `${m}:${s}`;

        // Colour transitions
        if (seconds <= 60 && seconds > 30) {
            countdownEl.className = 'font-semibold text-orange-500';
        } else if (seconds <= 30 && seconds > 0) {
            countdownEl.className = 'font-semibold text-red-500';
        }

        if (seconds <= 0) {
            clearInterval(timer);
            countdownEl.textContent = 'Expired';
            countdownEl.className   = 'font-semibold text-red-600';
            unlockResend();
        }
    }, 1000);

    // ── Unlock resend buttons when code expires ────────────────────────
    function unlockResend() {
        expired = true;

        // Swap status messages
        lockedMsg.classList.add('hidden');
        unlockedMsg.classList.remove('hidden');

        // Enable each button and restore its active colour classes
        resendBtns.forEach(btn => {
            btn.disabled = false;
            const inactive = btn.dataset.inactiveClass.split(' ');
            const active   = btn.dataset.activeClass.split(' ');
            btn.classList.remove(...inactive);
            btn.classList.add(...active);
        });

        // Disable the verify button — the code is gone
        const verifyBtn = document.querySelector('#otp-form button[type="submit"]');
        if (verifyBtn) {
            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Code expired — request a new one';
            verifyBtn.className = 'w-full bg-gray-300 text-gray-500 font-semibold py-3.5 rounded-xl text-sm cursor-not-allowed';
        }
    }

    // ── Auto-submit on 6 digits ───────────────────────────────────────
    otpInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
        if (this.value.length === 6 && !expired) {
            setTimeout(() => document.getElementById('otp-form').submit(), 300);
        }
    });
</script>
</body>
</html>
