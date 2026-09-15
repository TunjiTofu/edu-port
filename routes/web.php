<?php

use App\Http\Controllers\Candidate\CandidateRegistrationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SubmissionController;
use App\Models\Submission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ── Landing ─────────────────────────────────────────────────────────────────

Route::get('/', [LandingController::class, 'index']);

// ── Login redirect ────────────────────────────────────────────────────────────

Route::get('/login', function () {
    $intended = session('url.intended', '');
    if (str_contains($intended, '/reviewer')) return redirect('/reviewer/login');
    if (str_contains($intended, '/observer')) return redirect('/observer/login');
    return redirect('/student/login');
})->name('login');

// ── Submission file serving ───────────────────────────────────────────────────

Route::middleware('auth')->group(function () {

    Route::get('/submission/{submission}/download', [SubmissionController::class, 'download'])
        ->name('submission.download');

    Route::get('/reviewer/submissions/{submission}/file', function (Submission $submission) {

        if (! auth()->user()?->isReviewer()) {
            abort(403, 'Reviewer access required.');
        }

        if (! $submission->reviews()->where('reviewer_id', auth()->id())->exists()) {
            abort(403, 'You are not assigned to this submission.');
        }

        $fullPath = $submission->file_path . '/' . $submission->file_name;

        if (! Storage::exists($fullPath)) {
            Log::warning('Reviewer file access: file not found', [
                'event'         => 'reviewer_file_not_found',
                'submission_id' => $submission->id,
                'path'          => $fullPath,
                'reviewer_id'   => auth()->id(),
            ]);
            abort(404, 'File not found.');
        }

        try {
            $extension   = strtolower(pathinfo($submission->file_name, PATHINFO_EXTENSION));
            $contentType = match ($extension) {
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt'  => 'text/plain; charset=utf-8',
                'rtf'  => 'application/rtf',
                default => 'application/octet-stream',
            };

            $forceDownload = (bool) request('download', false);
            $disposition   = $forceDownload ? 'attachment' : 'inline';

            Log::info('Reviewer file access: served', [
                'event'         => 'reviewer_file_served',
                'submission_id' => $submission->id,
                'reviewer_id'   => auth()->id(),
                'download'      => $forceDownload,
            ]);

            return response(Storage::get($fullPath))
                ->header('Content-Type', $contentType)
                ->header('Content-Length', (string) Storage::size($fullPath))
                ->header('Content-Disposition', "{$disposition}; filename=\"{$submission->file_name}\"")
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', $forceDownload ? 'no-store, no-cache' : 'no-cache, no-store, must-revalidate');

        } catch (\Exception $e) {
            Log::error('Reviewer file access: exception', [
                'event'         => 'reviewer_file_error',
                'submission_id' => $submission->id,
                'error'         => $e->getMessage(),
                'reviewer_id'   => auth()->id(),
            ]);
            abort(500, 'Error serving file.');
        }

    })->name('reviewer.submissions.file');

    // ── Admin file download ───────────────────────────────────────────────────
    // Using a server-side route instead of pre-generating storage URLs per row.
    // Pre-generating URLs (temporaryUrl / exists) for every table row caused
    // 30-second timeouts on production with 2000+ submissions.
    Route::get('/admin/submissions/{submission}/file', function (Submission $submission) {

        if (! auth()->user()?->isAdmin()) {
            abort(403, 'Admin access required.');
        }

        $fullPath = $submission->file_path . '/' . $submission->file_name;

        if (! Storage::exists($fullPath)) {
            Log::warning('Admin file access: file not found', [
                'event'         => 'admin_file_not_found',
                'submission_id' => $submission->id,
                'path'          => $fullPath,
                'admin_id'      => auth()->id(),
            ]);
            abort(404, 'File not found.');
        }

        $extension   = strtolower(pathinfo($submission->file_name, PATHINFO_EXTENSION));
        $contentType = match ($extension) {
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };

        Log::info('Admin file access: served', [
            'event'         => 'admin_file_served',
            'submission_id' => $submission->id,
            'admin_id'      => auth()->id(),
        ]);

        return response(Storage::get($fullPath))
            ->header('Content-Type', $contentType)
            ->header('Content-Length', (string) Storage::size($fullPath))
            ->header('Content-Disposition', 'attachment; filename="' . $submission->file_name . '"')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'no-store, no-cache');

    })->name('admin.submissions.file');
});

// ── Candidate Self-Registration & Terms (public) ──────────────────────────────

Route::prefix('candidate')->name('candidate.')->group(function () {

    Route::get('/register',  [CandidateRegistrationController::class, 'showRegister'])->name('register');

    // 5 attempts per IP per 10 minutes — prevents automated sign-up floods
    Route::post('/register', [CandidateRegistrationController::class, 'submitRegister'])
        ->middleware('throttle:5,10')
        ->name('register.submit');

    Route::get('/churches',  [CandidateRegistrationController::class, 'getChurches'])->name('churches');

    Route::get('/verify-otp', [CandidateRegistrationController::class, 'showVerifyOtp'])->name('verify-otp');
    // token={uuid} is required as a query param — passed from submitRegister and resendOtp

    // 10 attempts per IP per 10 minutes — allows for mistyping without locking out
    Route::post('/verify-otp', [CandidateRegistrationController::class, 'submitVerifyOtp'])
        ->middleware('throttle:10,10')
        ->name('verify-otp.submit');

    // 3 resends per IP per 10 minutes — tight because the UI already locks
    // the buttons until the active code expires (prevents Termii API abuse)
    Route::post('/resend-otp', [CandidateRegistrationController::class, 'resendOtp'])
        ->middleware('throttle:3,10')
        ->name('resend-otp');

    // Terms & Conditions — publicly accessible (opened in new tab from registration form)
    Route::get('/terms', fn () => view('candidate.terms'))->name('terms');
    Route::get('/guide', fn () => view('candidate.guide'))->name('guide');
});
