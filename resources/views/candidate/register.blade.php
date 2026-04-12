<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Register — MG Portfolio Candidate Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-50 via-white to-emerald-50 flex flex-col justify-center py-8 px-4 sm:px-6">

{{-- Brand --}}
<div class="text-center mb-6">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-600 rounded-2xl shadow-lg mb-3">
        <span class="text-3xl">
            <img src="{{ asset('images/logo.png') }}" alt="MG Portfolio Logo">
        </span>
{{--        <img src="{{ asset('images/logo.png') }}" alt="MG Portfolio Logo">--}}

    </div>
    <h1 class="text-2xl font-bold text-gray-900">MG Portfolio Portal</h1>
    <p class="text-sm text-gray-500 mt-1">New Candidate Registration</p>
</div>

<div class="w-full max-w-lg mx-auto bg-white rounded-2xl shadow-xl overflow-hidden">

    {{-- Progress --}}
    <div class="flex">
        <div class="flex-1 h-1 bg-green-500"></div>
        <div class="flex-1 h-1 bg-gray-200"></div>
    </div>
    <div class="flex justify-between px-6 pt-3 pb-1 text-xs">
        <span class="font-semibold text-green-600">Step 1 of 2 — Your Details</span>
        <span class="text-gray-400">Step 2 — Verify Phone</span>
    </div>

    <div class="px-6 pb-8 pt-3">

        @if ($errors->any())
            <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm font-semibold text-red-700 mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 p-4 bg-orange-50 border border-orange-200 rounded-xl flex gap-3">
                <span class="text-orange-500 text-lg flex-shrink-0">⚠️</span>
                <p class="text-sm text-orange-700 font-medium">{{ session('error') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('candidate.register.submit') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Passport Photo --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Passport Photograph <span class="text-red-500">*</span>
                </label>

                {{-- Photo preview + upload area --}}
                <div class="flex flex-col items-center gap-3">
                    <div id="photo-preview-wrap" class="relative">
                        {{-- Shown before any photo is chosen --}}
                        <div id="photo-placeholder"
                             class="w-28 h-28 rounded-full border-4 border-green-200 shadow bg-gray-100 flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                            </svg>
                        </div>
                        {{-- Shown after a photo is chosen --}}
                        <img id="photo-preview"
                             src=""
                             class="w-28 h-28 rounded-full object-cover border-4 border-green-500 shadow hidden"
                             alt="Passport photo preview">
                        <label for="passport_photo"
                               class="absolute bottom-0 right-0 bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center cursor-pointer shadow hover:bg-green-700 transition"
                               title="Upload photo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </label>
                    </div>
                    <input type="file" id="passport_photo" name="passport_photo"
                           accept="image/jpeg,image/png" class="sr-only" required>
                    <p class="text-xs text-gray-400 text-center">
                        Clear face, square or portrait. JPEG/PNG, max 2 MB, min 200×200px. <br/>
                        <span class="text-sm text-red-500 text-center">This must be a passport photo of you on AYM Uniform.</span>
                    </p>
                </div>
                @error('passport_photo')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <hr class="border-gray-100">

            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. John Adeyemi"
                       class="w-full px-4 py-3 border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       placeholder="you@example.com"
                       class="w-full px-4 py-3 border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
            </div>

            {{-- MG Mentor --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    MG Mentor <span class="text-red-500">*</span>
                </label>
                <input type="text" name="mg_mentor" value="{{ old('mg_mentor') }}" required
                       placeholder="Full name of your MG mentor"
                       class="w-full px-4 py-3 border {{ $errors->has('mg_mentor') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <p class="text-xs text-gray-400 mt-1">The full name of the minister who is mentoring you.</p>
            </div>

            {{-- Phone --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                <input type="tel" name="phone" value="{{ old('phone') }}" required
                       placeholder="08012345678" maxlength="11" inputmode="numeric"
                       class="w-full px-4 py-3 border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <p class="text-xs text-gray-400 mt-1">11-digit Nigerian number. Verification code will be sent here.</p>
            </div>

            {{-- District & Church --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">District <span class="text-red-500">*</span></label>
                    <select name="district_id" id="district_id" required
                            class="w-full px-4 py-3 border {{ $errors->has('district_id') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white transition">
                        <option value="">— Select district —</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ old('district_id') == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Church <span class="text-red-500">*</span></label>
                    <select name="church_id" id="church_id" required
                            class="w-full px-4 py-3 border {{ $errors->has('church_id') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white transition"
                        {{ old('district_id') ? '' : 'disabled' }}>
                        <option value="">— Select church —</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Select a district first.</p>
                </div>
            </div>

            {{-- Password with toggle --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" name="password" id="password" required minlength="8"
                           placeholder="Min. 8 characters"
                           class="w-full px-4 py-3 pr-12 border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-300' }} rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                    <button type="button" onclick="togglePassword('password', 'eye1')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg id="eye1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Confirm Password with toggle --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                           placeholder="Repeat your password"
                           class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                    <button type="button" onclick="togglePassword('password_confirmation', 'eye2')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg id="eye2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Terms & Conditions --}}
            <div class="flex items-start gap-3">
                <input type="checkbox" name="terms" id="terms" value="1"
                       class="mt-0.5 w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                       {{ old('terms') ? 'checked' : '' }} required>
                <label for="terms" class="text-sm text-gray-600">
                    I have read and agree to the
                    <a href="{{ route('candidate.terms') }}" target="_blank"
                       class="text-green-600 font-medium underline hover:text-green-700">
                        Terms &amp; Conditions
                    </a>
                    of the MG Portfolio program.
                </label>
            </div>
            @error('terms')
            <p class="text-xs text-red-500 -mt-3">{{ $message }}</p>
            @enderror

            {{-- Submit --}}
            <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-semibold py-3.5 rounded-xl text-sm transition shadow-md mt-1">
                Send Verification Code →
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-5">
            Already have an account?
            <a href="/student/login" class="text-green-600 font-medium hover:underline">Log in</a>
        </p>
    </div>
</div>

<p class="text-center text-xs text-gray-400 mt-6 px-4">
    © {{ date('Y') }} MG Portfolio. All rights reserved.
</p>

<script>
    // ── Password visibility toggle ─────────────────────────────────────
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const eye   = document.getElementById(iconId);
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';

        // Swap SVG paths: open eye ↔ closed eye
        eye.innerHTML = show
            ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                       d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                  `
            : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                       d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                       d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  `;
    }

    // ── Passport photo preview ─────────────────────────────────────────
    document.getElementById('passport_photo').addEventListener('change', function () {
        const file        = this.files[0];
        const preview     = document.getElementById('photo-preview');
        const placeholder = document.getElementById('photo-placeholder');

        if (!file) return;

        if (file.size > 1024 * 1024) {
            alert('Photo must be under 1 MB. Please choose a smaller file.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    });

    // ── District → Church dynamic load ────────────────────────────────
    const districtSel = document.getElementById('district_id');
    const churchSel   = document.getElementById('church_id');

    districtSel.addEventListener('change', function () {
        const districtId = this.value;
        churchSel.innerHTML = '<option value="">Loading...</option>';
        churchSel.disabled  = true;

        if (!districtId) {
            churchSel.innerHTML = '<option value="">— Select church —</option>';
            return;
        }

        fetch(`/candidate/churches?district_id=${districtId}`)
            .then(r => r.json())
            .then(churches => {
                churchSel.innerHTML = '<option value="">— Select church —</option>';
                churches.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    if ('{{ old('church_id') }}' == c.id) opt.selected = true;
                    churchSel.appendChild(opt);
                });
                churchSel.disabled = false;
            })
            .catch(() => {
                churchSel.innerHTML = '<option value="">Failed to load. Refresh and retry.</option>';
                churchSel.disabled = false;
            });
    });

    @if (old('district_id'))
    districtSel.dispatchEvent(new Event('change'));
    @endif
</script>
</body>
</html>
