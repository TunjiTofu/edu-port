<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\RoleTypes;
use App\Http\Controllers\Controller;
use App\Mail\CandidateWelcomeMail;
use App\Models\Church;
use App\Models\District;
use App\Models\Role;
use App\Models\User;
use App\Services\TermiiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class CandidateRegistrationController extends Controller
{
    public function __construct(private TermiiService $termii) {}

    // ── Step 1: Show registration form ────────────────────────────────────────

    public function showRegister()
    {
        $districts = District::active()->orderBy('name')->get();
        return view('candidate.register', compact('districts'));
    }

    // ── Step 2: Validate, store passport photo, send OTP ─────────────────────

    public function submitRegister(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'       => [
                'required', 'string', 'regex:/^0[789][01]\d{8}$/',
                'unique:users,phone',
            ],
            'district_id' => ['required', 'exists:districts,id'],
            'church_id'   => ['required', 'exists:churches,id'],
            'password'    => [
                'required',
                'confirmed',
                'min:8',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'passport_photo' => [
                'required',
                'image',
                'mimes:jpeg,png',
                'max:1024',             // 1 MB max
                'dimensions:min_width=200,min_height=200',
            ],
            'terms'       => ['accepted'],
        ], [
            'phone.regex'                    => 'Phone must be a valid 11-digit Nigerian number (e.g. 08012345678).',
            'phone.unique'                   => 'This phone number is already registered.',
            'email.unique'                   => 'This email address is already registered.',
            'passport_photo.required'        => 'A passport photograph is required.',
            'passport_photo.max'             => 'Passport photo must be under 1 MB.',
            'passport_photo.dimensions'      => 'Photo must be at least 200×200 pixels.',
            'terms.accepted'                 => 'You must accept the Terms & Conditions to register.',
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

        // Store registration data in session (pending OTP verification)
        Session::put('candidate_registration', [
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'phone'          => $validated['phone'],
            'district_id'    => $validated['district_id'],
            'church_id'      => $validated['church_id'],
            'password'       => $validated['password'],
            'passport_photo' => $photoPath,
        ]);

        Log::info('Candidate registration: form submitted, sending OTP', [
            'event' => 'candidate_registration_form_submitted',
            'email' => $validated['email'],
            'phone' => substr($validated['phone'], 0, 4) . '****',
            'photo' => $photoPath,
        ]);

        $result = $this->termii->sendOtp($validated['phone'], 'registration');

        if (! $result['success']) {
            // Clean up uploaded photo if OTP fails
            Storage::disk($disk)->delete($photoPath);
            Session::forget('candidate_registration');

            return back()
                ->withErrors(['phone' => $result['message']])
                ->withInput();
        }

        return redirect()->route('candidate.verify-otp')
            ->with('success', 'A 6-digit verification code has been sent to your phone.');
    }

    // ── Step 3: Show OTP verification form ───────────────────────────────────

    public function showVerifyOtp()
    {
        if (! Session::has('candidate_registration')) {
            return redirect()->route('candidate.register')
                ->with('error', 'Please complete the registration form first.');
        }

        return view('candidate.verify-otp');
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

        $pending = Session::get('candidate_registration');

        if (! $pending) {
            return redirect()->route('candidate.register')
                ->with('error', 'Session expired. Please start over.');
        }

        $result = $this->termii->verifyOtp($pending['phone'], $request->otp, 'registration');

        if (! $result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        $studentRole = Role::where('name', RoleTypes::STUDENT->value)->first();

        if (! $studentRole) {
            Log::error('Candidate registration: Student role not found', [
                'event' => 'candidate_registration_role_missing',
            ]);
            return back()->withErrors(['otp' => 'Account creation failed. Please contact the administrator.']);
        }

        $user = User::create([
            'name'                => $pending['name'],
            'email'               => $pending['email'],
            'phone'               => $pending['phone'],
            'password'            => Hash::make($pending['password']),
            'role_id'             => $studentRole->id,
            'district_id'         => $pending['district_id'],
            'church_id'           => $pending['church_id'],
            'passport_photo'      => $pending['passport_photo'],
            'is_active'           => true,
            'password_updated_at' => now(),
            // Profile is NOT yet marked complete here — candidate still needs
            // to confirm their details on first login. If all fields are set,
            // markProfileComplete() will set the timestamp.
        ]);

        // Mark profile complete immediately if all data came through registration
        $user->markProfileComplete();

        Log::info('Candidate registration: account created', [
            'event'   => 'candidate_account_created',
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        Session::forget('candidate_registration');

        try {
            Mail::to($user->email)->send(new CandidateWelcomeMail($user));
        } catch (\Exception $e) {
            Log::error('Candidate registration: welcome email failed', [
                'event'   => 'candidate_welcome_email_failed',
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return redirect('/student/login')
            ->with('success', "Welcome, {$user->name}! Your account is ready. Please log in.");
    }

    // ── Resend OTP (SMS or voice) ─────────────────────────────────────────────

    public function resendOtp(Request $request)
    {
        $pending = Session::get('candidate_registration');

        if (! $pending) {
            return redirect()->route('candidate.register')
                ->with('error', 'Session expired. Please start over.');
        }

        $channel = in_array($request->input('channel'), ['sms', 'voice'], true)
            ? $request->input('channel')
            : 'sms';

        $result = $this->termii->resendOtp($pending['phone'], 'registration', $channel);

        if ($result['success']) {
            $label = $channel === 'voice' ? 'voice call' : 'SMS';
            return back()->with('success', "A new code has been sent via {$label}.");
        }

        return back()->withErrors(['otp' => $result['message']]);
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
