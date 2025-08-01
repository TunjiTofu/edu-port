<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use App\Models\Submission;
use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('landing');
//});

Route::get('/', [LandingController::class, 'index']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
//    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/check-auth', function() {
    return [
        'filament_auth' => \Filament\Facades\Filament::auth()->user(),
        'web_auth' => auth()->user(),
        'session' => session()->all()
    ];
});

// Submission routes
Route::middleware('auth')->group(function () {
    // Download submission file
    Route::get('/submission/{submission}/download', [SubmissionController::class, 'download'])
        ->name('submission.download');

    // View submission details (if you need a separate page)
    Route::get('/submission/{submission}', [SubmissionController::class, 'show'])
        ->name('submission.show');

    // Edit submission (if deadline hasn't passed)
    Route::get('/submission/{submission}/edit', [SubmissionController::class, 'edit'])
        ->name('submission.edit');

    // Update submission
    Route::put('/submission/{submission}', [SubmissionController::class, 'update'])
        ->name('submission.update');

    // Stream download for large files (alternative)
    Route::get('/submission/{submission}/stream', [SubmissionController::class, 'streamDownload'])
        ->name('submission.stream');
});

Route::get('/download/submission/{submission}', function (Submission $submission) {
    if (!Storage::disk(config('filesystems.default'))->exists($submission->file_path.'/'.$submission->file_name)) {
        abort(404, 'File not found');
    }

    return Storage::disk(config('filesystems.default'))->download($submission->file_path.'/'.$submission->file_name, $submission->file_name);
})->name('submission.download')->middleware('auth');

Route::get('/test-file', function() {
    return Storage::disk('public')->url('submissions/5/10/Student_1-2025-06-05_23-07-28-Project_Scoresheet_Dr._Adetunji.pdf');
});


//Route::get('/reviewer/submissions/{submission}/file', function (Submission $submission) {
//    // Verify reviewer access
//    if (!$submission->reviews()->where('reviewer_id', auth()->id())->exists()) {
//        abort(403, 'Unauthorized access');
//    }
//
//    $path = $submission->file_path.'/'.$submission->file_name;
//
//    if (!Storage::exists($path)) {
//        abort(404, 'File not found');
//    }
//
//    // Serve file directly without security headers that might conflict
//    return response()->file(Storage::path($path), [
//        'Content-Type' => 'application/pdf',
//        'X-Content-Type-Options' => 'nosniff'
//    ]);
//})->name('reviewer.submissions.file');;
//require __DIR__.'/auth.php';


Route::get('/reviewer/submissions/{submission}/file', function (Submission $submission) {
    // Add authorization check
    if (!auth()->user()?->isReviewer()) {
        abort(403);
    }

    // Check if reviewer has access to this submission
    $hasAccess = $submission->reviews()->where('reviewer_id', auth()->id())->exists();
    if (!$hasAccess) {
        abort(403, 'You do not have access to this submission.');
    }

    $fullPath = $submission->file_path . '/' . $submission->file_name;

    if (!Storage::exists($fullPath)) {
        abort(404, 'File not found.');
    }

    $fileExtension = strtolower(pathinfo($submission->file_name, PATHINFO_EXTENSION));

    // Set appropriate content type
    $contentType = match($fileExtension) {
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        default => 'application/octet-stream'
    };

    return response(Storage::get($fullPath), 200, [
        'Content-Type' => $contentType,
        'Content-Disposition' => 'inline; filename="' . $submission->file_name . '"',
        'X-Frame-Options' => 'SAMEORIGIN', // Allow iframe embedding from same origin
        'Cache-Control' => 'private, max-age=3600', // Cache for 1 hour
    ]);
})->name('reviewer.submissions.file')->middleware(['auth']);

Route::get('/reviewer/submissions/secure-pdf/{token}', function ($token) {
    try {
        // Decode and validate the token
        $tokenData = json_decode(base64_decode($token), true);

        if (!$tokenData || !isset($tokenData['submission_id'], $tokenData['user_id'], $tokenData['expires_at'], $tokenData['hash'])) {
            abort(403, 'Invalid access token');
        }

        // Check if token has expired
        if ($tokenData['expires_at'] < now()->timestamp) {
            abort(403, 'Access token has expired');
        }

        // Verify token integrity
        $expectedHash = hash_hmac('sha256', $tokenData['submission_id'] . $tokenData['user_id'], config('app.key'));
        if (!hash_equals($expectedHash, $tokenData['hash'])) {
            abort(403, 'Invalid token signature');
        }

        // Check if user is still authenticated and authorized
        if (!auth()->check() || auth()->id() != $tokenData['user_id']) {
            abort(403, 'Authentication required');
        }

        if (!auth()->user()?->isReviewer()) {
            abort(403, 'Insufficient permissions');
        }

        // Get the submission
        $submission = Submission::findOrFail($tokenData['submission_id']);

        // Check if reviewer has access to this submission
        $hasAccess = $submission->reviews()->where('reviewer_id', auth()->id())->exists();
        if (!$hasAccess) {
            abort(403, 'You do not have access to this submission.');
        }

        $fullPath = $submission->file_path . '/' . $submission->file_name;

        if (!Storage::exists($fullPath)) {
            abort(404, 'File not found.');
        }

        $fileExtension = strtolower(pathinfo($submission->file_name, PATHINFO_EXTENSION));

        if ($fileExtension !== 'pdf') {
            abort(400, 'Only PDF files are supported by this secure viewer.');
        }

        // Get the PDF content
        $pdfContent = Storage::get($fullPath);

        // Return the PDF with security headers
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="protected_document.pdf"',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            // Additional security headers to prevent downloading/printing
            'Content-Security-Policy' => "default-src 'self'; script-src 'unsafe-inline' 'unsafe-eval'; style-src 'unsafe-inline'; object-src 'none';",
        ]);

    } catch (\Exception $e) {
        \Log::error('Secure PDF access error: ' . $e->getMessage(), [
            'token' => $token,
            'user_id' => auth()->id(),
        ]);

        abort(403, 'Access denied');
    }
})->name('reviewer.submissions.secure-pdf')->middleware(['auth']);

Route::get('/reviewer/submissions/{submission}/file-direct', function (Submission $submission) {
    // Basic auth check
    if (!auth()->user()?->isReviewer()) {
        abort(403, 'Access denied');
    }

    // Check reviewer access
    $hasAccess = $submission->reviews()->where('reviewer_id', auth()->id())->exists();
    if (!$hasAccess) {
        abort(403, 'No access to this submission');
    }

    $fullPath = $submission->file_path . '/' . $submission->file_name;

    // Debug: Log the path
    \Log::info('PDF Request Debug', [
        'submission_id' => $submission->id,
        'file_path' => $submission->file_path,
        'file_name' => $submission->file_name,
        'full_path' => $fullPath,
        'storage_exists' => Storage::exists($fullPath),
        'storage_disk' => config('filesystems.default')
    ]);

    if (!Storage::exists($fullPath)) {
        // Let's try some common path variations
        $alternativePaths = [
            $submission->file_name, // Just filename
            'submissions/' . $submission->file_name, // Common folder
            'uploads/' . $submission->file_name, // Another common folder
            'public/' . $fullPath, // Public disk
        ];

        foreach ($alternativePaths as $altPath) {
            if (Storage::exists($altPath)) {
                \Log::info('Found file at alternative path: ' . $altPath);
                $fullPath = $altPath;
                break;
            }
        }

        if (!Storage::exists($fullPath)) {
            \Log::error('File not found at any path', [
                'tried_paths' => array_merge([$fullPath], $alternativePaths)
            ]);
            abort(404, 'File not found. Check logs for details.');
        }
    }

    try {
        $fileContent = Storage::get($fullPath);
        $fileSize = strlen($fileContent);

        \Log::info('PDF File Retrieved', [
            'file_size' => $fileSize,
            'first_4_bytes' => bin2hex(substr($fileContent, 0, 4)) // Should be 25504446 for PDF
        ]);

        // Verify it's actually a PDF
        if (substr($fileContent, 0, 4) !== '%PDF') {
            \Log::error('File is not a valid PDF', [
                'first_10_bytes' => bin2hex(substr($fileContent, 0, 10))
            ]);
            abort(400, 'File is not a valid PDF');
        }

        return response($fileContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'inline; filename="' . $submission->file_name . '"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);

    } catch (\Exception $e) {
        \Log::error('Error serving PDF', [
            'error' => $e->getMessage(),
            'file_path' => $fullPath
        ]);
        abort(500, 'Error loading PDF: ' . $e->getMessage());
    }

})->name('reviewer.submissions.file-direct')->middleware(['auth']);

Route::get('/debug/storage', function() {
    if (!auth()->user()?->isReviewer()) {
        abort(403);
    }

    return response()->json([
        'default_disk' => config('filesystems.default'),
        'disk_config' => config('filesystems.disks.' . config('filesystems.default')),
        'storage_path' => storage_path(),
        'app_path' => storage_path('app'),
        'public_path' => storage_path('app/public'),
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
    ]);
})->middleware(['auth']);

