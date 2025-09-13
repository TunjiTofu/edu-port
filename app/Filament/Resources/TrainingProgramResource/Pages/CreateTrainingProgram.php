<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Filament\Resources\TrainingProgramResource;
use App\Helpers\TrainingProgramHelper;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateTrainingProgram extends CreateRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Handle image upload if present
        if (isset($data['image']) && $data['image']) {
            $imageResult = TrainingProgramHelper::processImageUpload(
                $data['image'],
                $data['image'] // In Filament, this might be the path or filename
            );

            if ($imageResult['success']) {
                $data['image'] = $imageResult['path'];
                Log::info('Image uploaded successfully for new training program', [
                    'path' => $imageResult['path'],
                    'filename' => $imageResult['filename']
                ]);
            } else {
                Log::error('Failed to upload image for new training program', [
                    'error' => $imageResult['error']
                ]);
                // Remove image from data if upload failed
                unset($data['image']);
            }
        }

        return static::getModel()::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
