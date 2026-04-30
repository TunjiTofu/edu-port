<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\RoleTypes;
use App\Http\Controllers\Controller;
use App\Mail\CandidateWelcomeMail;
use App\Models\Church;
use App\Models\District;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\TermiiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\PendingRegistration;
use Illuminate\Support\Facades\Storage;

class CandidateRegistrationController extends Controller
{
    public function __construct(private TermiiService $termii) {}

    // ── Step 1: Show registration form ────────────────────────────────────────

    public function showRegister()
    {
        $districts = District::active()->orderBy('name')->get();

        // Pass registration status to the view so the form can show
        // a closed banner before the user fills anything in.
        $registrationOpen    = SiteSetting::isRegistrationOpen();
        $registrationMessage = $registrationOpen ? null : SiteSetting::registrationClosedMessage();

        return view('candidate.register', compact('districts', 'registrationOpen', 'registrationMessage'));
    }

    // ── Step 2: Validate, store passport photo, send OTP ─────────────────────

    public function submitRegister(Request $request)
    {
        // ── Server-side registration deadline check ────────────────────────
        // Even though the form shows a closed message, validate server-side
        // so a stale page or direct POST cannot bypass the deadline.
        if (! SiteSetting::isRegistrationOpen()) {
            return back()->with('error', SiteSetting::registrationClosedMessage());
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'       => [
                'required', 'string', 'regex:/^0[789][01]\d{8}$/',
                'unique:users,phone',
            ],
            'mg_mentor'   => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'exists:districts,id'],
            'church_id'   => ['required', 'exists:churches,id'],
            'password'    => ['required', 'confirmed', 'min:8'],
            'passport_photo' => [
                'required',
                'image',
                'mimes:jpeg,png',
                'max:1024',
                'dimensions:min_width=200,min_height=200',
            ],
            'terms'       => ['accepted'],
        ], [
            'phone.regex'               => 'Phone must be a valid 11-digit Nigerian number (e.g. 08012345678).',
            'phone.unique'              => 'This phone number is already registered.',
            'email.unique'              => 'This email address is already registered.',
            'mg_mentor.required'        => 'Please enter the full name of your MG mentor.',
            'passport_photo.required'   => 'A passport photograph is required.',
            'passport_photo.max'        => 'Passport photo must be under 1 MB.',
            'passport_photo.dimensions' => 'Photo must be at least 200×200 pixels.',
            'terms.accepted'            => 'You must accept the Terms & Conditions to register.',
        ]);

        // Verify church belongs to selected district
        $church = Church::where('id', $validated['church_id'])
            ->where('district_id', $validated['district_id'])
            ->where('is_active', true)
            ->first();

        if (! $church) {
            return back()
                ->withErrors(['church_id' => 'The selected church does not belong to the chosen district.'])
                ->withInput();
        }

        // Store the passport photo on the configured disk
        $disk      = config('filesystems.default');
        $photoPath = $request->file('passport_photo')->store('passport-photos', $disk);

        // ── Store pending registration in database ────────────────────────────
        // Using a dedicated database table instead of cache or session.
        // This works reliably on all hosting environments regardless of
        // CACHE_STORE setting, and is unaffected by the admin panel's
        // background Livewire requests that can corrupt the session.
        $token = PendingRegistration::store([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'phone'          => $validated['phone'],
            'mg_mentor'      => $validated['mg_mentor'],
            'district_id'    => $validated['district_id'],
            'church_id'      => $validated['church_id'],
            'password'       => $validated['password'],
            'passport_photo' => $photoPath,
        ], 15); // 15 minute expiry — OTP is valid 10 min, extra buffer

        Log::info('Candidate registration: form submitted, sending OTP', [
            'event' => 'candidate_registration_form_submitted',
            'email' => $validated['email'],
            'phone' => substr($validated['phone'], 0, 4) . '****',
            'photo' => $photoPath,
        ]);

        $result = $this->termii->sendOtp($validated['phone'], 'registration');

        if (! $result['success']) {
            // Clean up uploaded photo and cache entry if OTP send fails
            Storage::disk($disk)->delete($photoPath);
            PendingRegistration::where('token', $token)->delete();

            return back()
                ->withErrors(['phone' => $result['message']])
                ->withInput();
        }

        return redirect()->route('candidate.verify-otp', ['token' => $token])
            ->with('success', 'A 6-digit verification code has been sent to your phone.');
    }

    // ── Step 3: Show OTP verification form ───────────────────────────────────

    public function showVerifyOtp(Request $request)
    {
        $token   = $request->query('token');
        $pending = $token ? PendingRegistration::findValid($token) : null;
        Log::warning('pending row', [
            'token' => $token,
            'pending' => $pending,
        ]);

        if (! $pending) {
            return redirect()->route('candidate.register')
                ->with('error', 'Your registration session has expired or is invalid. Please fill in the form again.');
        }

        return view('candidate.verify-otp', compact('token'));
    }

    // ── Step 4: Verify OTP, create account ───────────────────────────────────

    public function submitVerifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ], [
            'otp.size'  => 'The OTP must be exactly 6 digits.',
            'otp.regex' => 'The OTP must contain only digits.',
        ]);

        $token      = $request->input('token');
        $pendingRow = $token ? PendingRegistration::findValid($token) : null;

        if (! $pendingRow) {
            return redirect()->route('candidate.register')
                ->with('error', 'Your registration session has expired. Please fill in the form again.');
        }

        $pending = $pendingRow->toRegistrationData();

        $result = $this->termii->verifyOtp($pending['phone'], $request->otp, 'registration');

        if (! $result['success']) {
            return redirect()
                ->route('candidate.verify-otp', ['token' => $token])
                ->withErrors(['otp' => $result['message']]);
        }

        $studentRole = Role::where('name', RoleTypes::STUDENT->value)->first();

        if (! $studentRole) {
            Log::error('Candidate registration: Student role not found', [
                'event' => 'candidate_registration_role_missing',
            ]);
            return back()->withErrors(['otp' => 'Account creation failed. Please contact the administrator.']);
        }

        // ── Idempotency guard ──────────────────────────────────────────────
        // If the form was somehow submitted twice (auto-submit JS + manual click,
        // or a browser retry), the first submission will have already created
        // the account and deleted the pending row. The second submission will
        // hit a null $pendingRow above and redirect to register. But as an extra
        // safety net, also check if the email is already registered — if so,
        // just redirect to login rather than showing a confusing error.
        $existingUser = User::where('email', $pending['email'])->first();
        if ($existingUser) {
            Log::info('Candidate registration: duplicate submission detected, user already exists', [
                'event'   => 'candidate_registration_duplicate_submit',
                'user_id' => $existingUser->id,
                'email'   => $existingUser->email,
            ]);
            PendingRegistration::where('token', $token)->delete(); // clean up if still there
            return redirect('/student/login')
                ->with('success', "Your account is ready, {$existingUser->name}. Please log in.");
        }

        $user = User::create([
            'name'                => $pending['name'],
            'email'               => $pending['email'],
            'phone'               => $pending['phone'],
            'mg_mentor'           => $pending['mg_mentor'],
            'password'            => Hash::make($pending['password']),
            'role_id'             => $studentRole->id,
            'district_id'         => $pending['district_id'],
            'church_id'           => $pending['church_id'],
            'passport_photo'      => $pending['passport_photo'],
            'is_active'           => true,
            'password_updated_at' => now(),
        ]);

        // Mark profile complete immediately if all data came through registration
        $user->markProfileComplete();

        Log::info('Candidate registration: account created', [
            'event'   => 'candidate_account_created',
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        // Delete the pending registration row now that the account is created.
        PendingRegistration::where('token', $token)->delete();

        // ── Non-blocking welcome email ─────────────────────────────────────
        // dispatch()->afterResponse() tells Laravel to run this closure AFTER
        // the HTTP response has been flushed to the browser and the session
        // has been written. This means:
        //   1. The user is redirected immediately — no waiting for SMTP.
        //   2. An SSL timeout or mail failure CANNOT cause "Session expired"
        //      because the session is already committed before mail runs.
        //   3. No queue worker is needed — works on cPanel sync hosting.
        $userId = $user->id;
        $userEmail = $user->email;
        $userName  = $user->name;

        dispatch(function () use ($userId, $userEmail, $userName) {
            try {
                // Re-fetch the user inside the closure — the original $user
                // model instance may not serialize cleanly across the response boundary.
                $freshUser = \App\Models\User::find($userId);

                if ($freshUser) {
                    \Illuminate\Support\Facades\Mail::to($userEmail)
                        ->send(new \App\Mail\CandidateWelcomeMail($freshUser));

                    Log::info('Candidate registration: welcome email sent', [
                        'event'   => 'candidate_welcome_email_sent',
                        'user_id' => $userId,
                        'email'   => $userEmail,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Candidate registration: welcome email failed', [
                    'event'   => 'candidate_welcome_email_failed',
                    'user_id' => $userId,
                    'error'   => $e->getMessage(),
                ]);
                // Swallow the exception — user is already registered and logged in.
                // The welcome email is informational; its failure must never
                // surface to the candidate or block their onboarding.
            }
        })->afterResponse();

        return redirect('/student/login')
            ->with('success', "Welcome, {$user->name}! Your account is ready. Please log in.");
    }

    // ── Resend OTP (SMS or voice) ─────────────────────────────────────────────

    public function resendOtp(Request $request)
    {
        $token      = $request->input('token');
        $pendingRow = $token ? PendingRegistration::findValid($token) : null;

        if (! $pendingRow) {
            return redirect()->route('candidate.register')
                ->with('error', 'Your registration session has expired. Please fill in the form again.');
        }

        $pending = $pendingRow->toRegistrationData();

        $channel = in_array($request->input('channel'), ['sms', 'voice'], true)
            ? $request->input('channel')
            : 'sms';

        $result = $this->termii->resendOtp($pending['phone'], 'registration', $channel);

        if ($result['success']) {
            $label = $channel === 'voice' ? 'voice call' : 'SMS';
            return redirect()
                ->route('candidate.verify-otp', ['token' => $token])
                ->with('success', "A new code has been sent via {$label}.");
        }

        return redirect()
            ->route('candidate.verify-otp', ['token' => $token])
            ->withErrors(['otp' => $result['message']]);
    }

    // ── AJAX: churches for a district ────────────────────────────────────────

    public function getChurches(Request $request)
    {
        $churches = Church::where('district_id', $request->district_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($churches);
    }
}
