<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Filament\Resources\TrainingProgramResource;
use App\Helpers\TrainingProgramHelper;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditTrainingProgram extends EditRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldImagePath = $record->image;

        // Handle image upload if present
        if (isset($data['image']) && $data['image']) {
            // Check if it's a new upload (different from existing)
            if ($data['image'] !== $oldImagePath) {
                $imageResult = TrainingProgramHelper::processImageUpload(
                    $data['image'],
                    $data['image'], // In Filament, this might be the path or filename
                    $record->id
                );

                if ($imageResult['success']) {
                    $data['image'] = $imageResult['path'];

                    // Delete old image if it exists
                    if ($oldImagePath) {
                        TrainingProgramHelper::deleteImage($oldImagePath);
                    }

                    Log::info('Image updated successfully for training program', [
                        'program_id' => $record->id,
                        'old_path' => $oldImagePath,
                        'new_path' => $imageResult['path'],
                        'filename' => $imageResult['filename']
                    ]);
                } else {
                    Log::error('Failed to upload new image for training program', [
                        'program_id' => $record->id,
                        'error' => $imageResult['error']
                    ]);
                    // Keep the old image if upload failed
                    $data['image'] = $oldImagePath;
                }
            }
        } else {
            // If no image is provided, check if we should delete the old one
            if ($oldImagePath && !isset($data['image'])) {
                TrainingProgramHelper::deleteImage($oldImagePath);
                $data['image'] = null;
            }
        }

        $record->update($data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
