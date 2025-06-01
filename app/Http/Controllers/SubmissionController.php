<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    /**
     * Download a submission file
     */
    public function download(Submission $submission)
    {
        // Check if user owns this submission or has permission to view it
        if (!$this->canAccessSubmission($submission)) {
            abort(403, 'You do not have permission to download this file.');
        }

        // Construct the full file path
        $fullFilePath = $submission->file_path . '/' . $submission->file_name;
        $fileName = $submission->file_name;

        // Check if file exists
        if (!Storage::exists($fullFilePath)) {
            abort(404, 'File not found.');
        }

        // Get MIME type
        $mimeType = Storage::mimeType($fullFilePath);

        // Return file download response
        return Storage::download($fullFilePath, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Display submission details (if you need a separate view page)
     */
    public function show(Submission $submission)
    {
        if (!$this->canAccessSubmission($submission)) {
            abort(403, 'You do not have permission to view this submission.');
        }

        return view('submissions.show', compact('submission'));
    }

    /**
     * Show edit form for submission
     */
    public function edit(Submission $submission)
    {
        // Check if user owns this submission
        if ($submission->student_id !== Auth::id()) {
            abort(403, 'You can only edit your own submissions.');
        }

        // Check if deadline hasn't passed
        if ($submission->assignment->deadline && now()->isAfter($submission->assignment->deadline)) {
            return redirect()->back()->with('error', 'The deadline for this assignment has passed.');
        }

        return view('submissions.edit', compact('submission'));
    }

    /**
     * Update submission
     */
    public function update(Request $request, Submission $submission)
    {
        // Check ownership
        if ($submission->student_id !== Auth::id()) {
            abort(403, 'You can only update your own submissions.');
        }

        // Check deadline
        if ($submission->assignment->deadline && now()->isAfter($submission->assignment->deadline)) {
            return redirect()->back()->with('error', 'The deadline for this assignment has passed.');
        }

        $request->validate([
            'file' => 'sometimes|file|max:10240', // 10MB max
            'comments' => 'nullable|string|max:1000',
        ]);

        // Handle file upload if new file provided
        if ($request->hasFile('file')) {
            // Delete old file
            if ($submission->file_path && Storage::exists($submission->file_path)) {
                Storage::delete($submission->file_path);
            }

            // Store new file
            $file = $request->file('file');
            $filePath = $file->store('submissions/' . Auth::id());

            $submission->update([
                'file_path' => $filePath,
                'original_filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'updated_at' => now(),
            ]);
        }

        // Update comments if provided
        if ($request->has('comments')) {
            $submission->update(['comments' => $request->comments]);
        }

        return redirect()->back()->with('success', 'Submission updated successfully.');
    }

    /**
     * Check if current user can access the submission
     */
    private function canAccessSubmission(Submission $submission): bool
    {
        $user = Auth::user();

        // Student can access their own submission
        if ($submission->student_id === $user->id) {
            return true;
        }

        // Teacher/Admin can access submissions for their assignments
        // Adjust this logic based on your role system
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isReviewer()) {
            return true;
        }

        // If you have a different permission system, modify accordingly
        // For example, if teachers are linked to specific assignments:
        // return $submission->assignment->teacher_id === $user->id;

        return false;
    }

    /**
     * Stream large files for download (alternative method for large files)
     */
    public function streamDownload(Submission $submission): StreamedResponse
    {
        if (!$this->canAccessSubmission($submission)) {
            abort(403, 'You do not have permission to download this file.');
        }

        if (!$submission->file_path || !Storage::exists($submission->file_path)) {
            abort(404, 'File not found.');
        }

        $filePath = $submission->file_path;
        $fileName = $submission->original_filename ?? basename($filePath);

        return Storage::response($filePath, $fileName);
    }
}
