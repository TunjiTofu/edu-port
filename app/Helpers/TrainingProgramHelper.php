<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class TrainingProgramHelper
{
    public static function processImageUpload($tempPath, $originalName, $programId = null): array
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $finalDir = "training-programs";
            $sanitizedName = str_replace(' ', '_', strtolower($originalName));
            $extension = pathinfo($sanitizedName, PATHINFO_EXTENSION);
            $nameWithoutExt = pathinfo($sanitizedName, PATHINFO_FILENAME);

            // Create unique filename
            $uniqueName = $programId
                ? "program_{$programId}_{$timestamp}_{$nameWithoutExt}.{$extension}"
                : "{$timestamp}_{$nameWithoutExt}.{$extension}";

            $newPath = "{$finalDir}/{$uniqueName}";

            // Log pre-upload details
            Log::info('Pre-upload image details', [
                'temp_path' => $tempPath,
                'new_path' => $newPath,
                'directory' => $finalDir,
                'sanitized_name' => $uniqueName,
                'disk' => config('filesystems.default'),
                'temp_exists' => Storage::disk('public')->exists($tempPath),
                'target_exists' => Storage::disk(config('filesystems.default'))->exists($newPath)
            ]);

            // Ensure directory exists (for local storage)
            if (config('filesystems.default') !== 's3') {
                Storage::disk('public')->makeDirectory($finalDir);
            }

            // Check if temp file exists
            if (!Storage::disk('public')->exists($tempPath)) {
                throw new Exception("Uploaded image not found at: {$tempPath}");
            }

            // Handle file upload based on disk type
            if (config('filesystems.default') === 's3') {
                $fileStream = Storage::disk('public')->readStream($tempPath);
                Storage::disk('s3')->put($newPath, $fileStream, [
                    'Visibility' => 'private',
                    'ContentType' => Storage::disk('public')->mimeType($tempPath)
                ]);
                Storage::disk('public')->delete($tempPath);
            } else {
                Storage::disk('public')->move($tempPath, $newPath);
            }

            // Log post-upload details
            Log::info('Post-upload image details', [
                'new_path' => $newPath,
                'disk' => config('filesystems.default'),
                'directory' => $finalDir,
                'sanitized_name' => $uniqueName,
                'final_exists' => Storage::disk(config('filesystems.default'))->exists($newPath),
                'file_size' => Storage::disk(config('filesystems.default'))->size($newPath),
                'file_type' => Storage::disk(config('filesystems.default'))->mimeType($newPath)
            ]);

            return [
                'success' => true,
                'path' => $newPath,
                'filename' => $uniqueName,
                'size' => Storage::disk(config('filesystems.default'))->size($newPath),
                'mime_type' => Storage::disk(config('filesystems.default'))->mimeType($newPath),
            ];

        } catch (Exception $e) {
            // Clean up any partial uploads
            if (isset($newPath)) {
                Storage::disk(config('filesystems.default'))->delete($newPath);
            }

            Log::error('Image processing failed', [
                'error' => $e->getMessage(),
                'temp_path' => $tempPath ?? null,
                'new_path' => $newPath ?? null,
                'stack_trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function deleteImage($imagePath): bool
    {
        try {
            if ($imagePath && Storage::disk(config('filesystems.default'))->exists($imagePath)) {
                Storage::disk(config('filesystems.default'))->delete($imagePath);
                Log::info('Image deleted successfully', ['path' => $imagePath]);
                return true;
            }
            return false;
        } catch (Exception $e) {
            Log::error('Failed to delete image', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public static function getImageUrl($imagePath): ?string
    {
        try {
            if (!$imagePath || !Storage::disk(config('filesystems.default'))->exists($imagePath)) {
                return null;
            }

            if (config('filesystems.default') === 's3') {
                return Storage::disk('s3')->temporaryUrl(
                    $imagePath,
                    now()->addHours(24) // 24 hour expiration for images
                );
            } else {
                return Storage::disk('public')->url($imagePath);
            }
        } catch (Exception $e) {
            Log::error("Failed to generate image URL: " . $e->getMessage());
            return null;
        }
    }
}
