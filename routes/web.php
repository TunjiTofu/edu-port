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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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

//require __DIR__.'/auth.php';
