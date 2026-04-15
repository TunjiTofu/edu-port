<?php
//
//use App\Http\Controllers\Candidate\CandidateRegistrationController;
//use App\Http\Controllers\LandingController;
//use App\Http\Controllers\ProfileController;
//use App\Http\Controllers\SubmissionController;
//use App\Models\Submission;
//use Illuminate\Support\Facades\Route;
//
////Route::get('/', function () {
////    return view('landing');
////});
//
//Route::get('/', [LandingController::class, 'index']);
//
//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');
//
//Route::middleware('auth')->group(function () {
////    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
////    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
////    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
//});
//
//
//Route::get('/login', function () {
//    // Redirect to the appropriate login page based on intended URL or default to student
//    $intended = session()->get('url.intended');
//
//    if (str_contains($intended ?? '', '/reviewer')) {
//        return redirect('/reviewer/login');
//    } elseif (str_contains($intended ?? '', '/observer')) {
//        return redirect('/observer/login');
//    } else {
//        return redirect('/student/login');
//    }
//})->name('login');
//
//Route::get('/check-auth', function() {
//    return [
//        'filament_auth' => \Filament\Facades\Filament::auth()->user(),
//        'web_auth' => auth()->user(),
//        'session' => session()->all()
//    ];
//});
//
//// Submission routes
//Route::middleware('auth')->group(function () {
//    // Download submission file
//    Route::get('/submission/{submission}/download', [SubmissionController::class, 'download'])
//        ->name('submission.download');
//
//    // View submission details (if you need a separate page)
//    Route::get('/submission/{submission}', [SubmissionController::class, 'show'])
//        ->name('submission.show');
//
//    // Edit submission (if deadline hasn't passed)
//    Route::get('/submission/{submission}/edit', [SubmissionController::class, 'edit'])
//        ->name('submission.edit');
//
//    // Update submission
//    Route::put('/submission/{submission}', [SubmissionController::class, 'update'])
//        ->name('submission.update');
//
//    // Stream download for large files (alternative)
//    Route::get('/submission/{submission}/stream', [SubmissionController::class, 'streamDownload'])
//        ->name('submission.stream');
//});
//
//Route::get('/download/submission/{submission}', function (Submission $submission) {
//    if (!Storage::disk(config('filesystems.default'))->exists($submission->file_path.'/'.$submission->file_name)) {
//        abort(404, 'File not found');
//    }
//
//    return Storage::disk(config('filesystems.default'))->download($submission->file_path.'/'.$submission->file_name, $submission->file_name);
//})->name('submission.download')->middleware('auth');
//
//Route::get('/test-file', function() {
//    return Storage::disk('public')->url('submissions/5/10/Student_1-2025-06-05_23-07-28-Project_Scoresheet_Dr._Adetunji.pdf');
//});
//
//
//Route::get('/reviewer/submissions/{submission}/file', function (Submission $submission) {
//    // Authorization checks
//    if (!auth()->check() || !auth()->user()->isReviewer()) {
//        return response('Unauthorized', 403)->header('Content-Type', 'text/plain');
//    }
//
//    // Check reviewer assignment
//    $hasReview = $submission->reviews()
//        ->where('reviewer_id', auth()->id())
//        ->exists();
//
//    if (!$hasReview) {
//        return response('Access denied', 403)->header('Content-Type', 'text/plain');
//    }
//
//    // File path resolution
//    $fullPath = $submission->file_path . '/' . $submission->file_name;
//
//    if (!Storage::exists($fullPath)) {
//        $alternativePaths = [
//            $submission->file_name,
//            'submissions/' . $submission->file_name,
//            'uploads/' . $submission->file_name,
//        ];
//
//        $found = false;
//        foreach ($alternativePaths as $altPath) {
//            if (Storage::exists($altPath)) {
//                $fullPath = $altPath;
//                $found = true;
//                break;
//            }
//        }
//
//        if (!$found) {
//            return response('File not found', 404)->header('Content-Type', 'text/plain');
//        }
//    }
//
//    try {
//        $fileContent = Storage::get($fullPath);
//        $fileSize = Storage::size($fullPath);
//        $extension = strtolower(pathinfo($submission->file_name, PATHINFO_EXTENSION));
//
//        // Determine content type
//        $contentType = match($extension) {
//            'pdf' => 'application/pdf',
//            'doc' => 'application/msword',
//            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//            'txt' => 'text/plain; charset=utf-8',
//            'rtf' => 'application/rtf',
//            default => 'application/octet-stream'
//        };
//
//        // Determine if this should be a download
//        $forceDownload = request()->has('download') || request()->get('download') === '1';
//        $disposition = $forceDownload ? 'attachment' : 'inline';
//
//        // Create response
//        $response = response($fileContent);
//
//        // Set basic headers
//        $response->headers->set('Content-Type', $contentType);
//        $response->headers->set('Content-Length', (string)$fileSize);
//        $response->headers->set('Content-Disposition', $disposition . '; filename="' . $submission->file_name . '"');
//
//        // CORS headers for PDF.js compatibility
//        $origin = request()->header('Origin');
//        $allowedOrigins = [
//            request()->getSchemeAndHttpHost(),
//            config('app.url')
//        ];
//
//        if (in_array($origin, $allowedOrigins) || request()->ajax() || request()->wantsJson()) {
//            $response->headers->set('Access-Control-Allow-Origin', request()->getSchemeAndHttpHost());
//            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
//            $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Authorization');
//            $response->headers->set('Access-Control-Allow-Credentials', 'true');
//        }
//
//        // For PDF files that are not downloads, use minimal caching
//        if ($extension === 'pdf' && !$forceDownload) {
//            $response->headers->set('Cache-Control', 'private, max-age=300'); // 5 minutes cache
//            $response->headers->set('Pragma', 'cache');
//        } else {
//            // For downloads or other files, prevent caching
//            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
//            $response->headers->set('Pragma', 'no-cache');
//            $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
//        }
//
//        // Security headers (minimal to avoid conflicts with PDF.js)
//        $response->headers->set('X-Content-Type-Options', 'nosniff');
//        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
//
//        // Accept ranges for better PDF loading
//        if ($extension === 'pdf') {
//            $response->headers->set('Accept-Ranges', 'bytes');
//        }
//
//        // Log successful access
//        \Log::info('File accessed', [
//            'submission_id' => $submission->id,
//            'file_name' => $submission->file_name,
//            'user_id' => auth()->id(),
//            'is_download' => $forceDownload,
//            'user_agent' => request()->userAgent(),
//            'origin' => $origin,
//            'is_ajax' => request()->ajax()
//        ]);
//
//        return $response;
//
//    } catch (\Exception $e) {
//        \Log::error('File access error', [
//            'submission_id' => $submission->id,
//            'error' => $e->getMessage(),
//            'file_path' => $fullPath,
//            'user_id' => auth()->id()
//        ]);
//
//        return response('Error: ' . $e->getMessage(), 500)
//            ->header('Content-Type', 'text/plain');
//    }
//
//})->name('reviewer.submissions.file')->middleware(['web', 'auth']);
//
//Route::get('/debug/storage', function() {
//    if (!auth()->user()?->isReviewer()) {
//        abort(403);
//    }
//
//    return response()->json([
//        'default_disk' => config('filesystems.default'),
//        'disk_config' => config('filesystems.disks.' . config('filesystems.default')),
//        'storage_path' => storage_path(),
//        'app_path' => storage_path('app'),
//        'public_path' => storage_path('app/public'),
//        'laravel_version' => app()->version(),
//        'php_version' => PHP_VERSION,
//    ]);
//})->middleware(['auth']);
//
//
//// ── Candidate Self-Registration ──────────────────────────────────────────────
//
//Route::prefix('candidate')->name('candidate.')->group(function () {
//
//    // Step 1: Show & submit registration form
//    Route::get('/register',  [CandidateRegistrationController::class, 'showRegister'])->name('register');
//    Route::post('/register', [CandidateRegistrationController::class, 'submitRegister'])->name('register.submit');
//
//    // AJAX: fetch churches for a given district (used by the JS dropdown)
//    Route::get('/churches',  [CandidateRegistrationController::class, 'getChurches'])->name('churches');
//
//    // Step 2: Show & submit OTP verification
//    Route::get('/verify-otp',  [CandidateRegistrationController::class, 'showVerifyOtp'])->name('verify-otp');
//    Route::post('/verify-otp', [CandidateRegistrationController::class, 'submitVerifyOtp'])->name('verify-otp.submit');
//
//    // Resend OTP
//    Route::post('/resend-otp', [CandidateRegistrationController::class, 'resendOtp'])->name('resend-otp');
//});


use App\Http\Controllers\Candidate\CandidateRegistrationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SubmissionController;
use App\Models\Submission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ── Landing ───────────────────────────────────────────────────────────────────

Route::get('/', [LandingController::class, 'index']);

// ── Login redirect ────────────────────────────────────────────────────────────
// Filament panels each have their own /login page. This catches Laravel's
// default /login redirect and forwards to the correct panel login.

Route::get('/login', function () {
    $intended = session('url.intended', '');

    if (str_contains($intended, '/reviewer')) {
        return redirect('/reviewer/login');
    }

    if (str_contains($intended, '/observer')) {
        return redirect('/observer/login');
    }

    return redirect('/student/login');
})->name('login');

// ── Submission file serving (authenticated) ───────────────────────────────────

Route::middleware('auth')->group(function () {

    // Primary download route — used by Submission::getFileUrl() on S3 deployments
    Route::get('/submission/{submission}/download', [SubmissionController::class, 'download'])
        ->name('submission.download');

    // Reviewer file viewer — serves file inline for PDF preview or as download
    Route::get('/reviewer/submissions/{submission}/file', function (Submission $submission) {

        // Authorisation: reviewer only + must be assigned to this submission
        if (!auth()->user()?->isReviewer()) {
            abort(403, 'Reviewer access required.');
        }

        if (!$submission->reviews()->where('reviewer_id', auth()->id())->exists()) {
            abort(403, 'You are not assigned to this submission.');
        }

        // Resolve file path
        $fullPath = $submission->file_path . '/' . $submission->file_name;

        if (!Storage::exists($fullPath)) {
            Log::warning('Reviewer file access: file not found', [
                'event' => 'reviewer_file_not_found',
                'submission_id' => $submission->id,
                'path' => $fullPath,
                'reviewer_id' => auth()->id(),
            ]);
            abort(404, 'File not found.');
        }

        try {
            $extension = strtolower(pathinfo($submission->file_name, PATHINFO_EXTENSION));
            $contentType = match ($extension) {
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain; charset=utf-8',
                'rtf' => 'application/rtf',
                default => 'application/octet-stream',
            };

            $forceDownload = (bool)request('download', false);
            $disposition = $forceDownload ? 'attachment' : 'inline';
            $fileContent = Storage::get($fullPath);
            $fileSize = Storage::size($fullPath);

            Log::info('Reviewer file access: served', [
                'event' => 'reviewer_file_served',
                'submission_id' => $submission->id,
                'file_name' => $submission->file_name,
                'reviewer_id' => auth()->id(),
                'download' => $forceDownload,
            ]);

            return response($fileContent)
                ->header('Content-Type', $contentType)
                ->header('Content-Length', (string)$fileSize)
                ->header('Content-Disposition', "{$disposition}; filename=\"{$submission->file_name}\"")
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Accept-Ranges', 'bytes')
                ->header('Cache-Control', $forceDownload
                    ? 'no-store, no-cache, must-revalidate'
                    : 'private, max-age=300'
                );

        } catch (\Exception $e) {
            Log::error('Reviewer file access: exception', [
                'event' => 'reviewer_file_error',
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'reviewer_id' => auth()->id(),
            ]);

            abort(500, 'Error serving file.');
        }

    })->name('reviewer.submissions.file');

});

// ── Candidate Self-Registration (public — no auth required) ───────────────────

Route::prefix('candidate')->name('candidate.')->group(function () {

    Route::get('/register', [CandidateRegistrationController::class, 'showRegister'])->name('register');
//    Route::post('/register', [CandidateRegistrationController::class, 'submitRegister'])
//        ->middleware('throttle:3,10')
//        ->name('register.submit');

    // POST /candidate/register — SUBMIT the form
    // Two layers of protection:
    //   1. ThrottleRegistration middleware  (per-IP + per-email rate limiting)
    //   2. ValidRecaptcha rule inside the controller's validation
    Route::post('/register', [CandidateRegistrationController::class, 'submitRegister'])
        ->name('register.submit')
        ->middleware(\App\Http\Middleware\ThrottleRegistration::class);

    // AJAX: return churches for a given district_id (used by registration form JS)
    Route::get('/churches', [CandidateRegistrationController::class, 'getChurches'])->name('churches');

    Route::get('/verify-otp', [CandidateRegistrationController::class, 'showVerifyOtp'])->name('verify-otp');
    // 3 attempts per IP per 10 minutes — allows for mistyping without locking out
    Route::post('/verify-otp', [CandidateRegistrationController::class, 'submitVerifyOtp'])
        ->middleware('throttle:5,10')
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
