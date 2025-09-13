<?php

namespace App\Filament\Resources\UserResource\Actions;

use App\Models\User;
use App\Services\CsvUserValidationService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ImportUsersAction
{
    public static function make(): Action
    {
        return Action::make('import_users')
            ->label('Import Users from CSV')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->form([
                Section::make('CSV File Upload')
                    ->description('Upload a CSV file with user data. The CSV should have the following columns: name, email, phone, role_name, district_name, church_name, password (optional)')
                    ->schema([
                        FileUpload::make('csv_file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv', 'application/csv', '.csv'])
                            ->required()
                            ->maxSize(5120) // 5MB
                            ->helperText('Maximum file size: 5MB. Accepted format: CSV')
                            ->columnSpanFull(),
                    ])
            ])
            ->action(function (array $data) {
                $filePath = storage_path('app/public/' . $data['csv_file']);

                if (!file_exists($filePath)) {
                    Notification::make()
                        ->title('Error')
                        ->body('File not found.')
                        ->danger()
                        ->send();
                    return;
                }

                $csvData = array_map('str_getcsv', file($filePath));
                $headers = array_shift($csvData);

                // Initialize validation service
                $validator = new CsvUserValidationService();

                // Validate headers
                $headerValidation = $validator->validateCsvHeaders($headers);
                if (!$headerValidation['valid']) {
                    Notification::make()
                        ->title('Invalid CSV Format')
                        ->body('Missing required columns: ' . implode(', ', $headerValidation['missing_required']))
                        ->danger()
                        ->send();
                    return;
                }

                $normalizedHeaders = $headerValidation['normalized'];
                $successCount = 0;
                $errorCount = 0;
                $errors = [];
                $validUsers = [];

                // First pass: Validate all data without creating users
                foreach ($csvData as $index => $row) {
                    $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed

                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    if (count($row) !== count($normalizedHeaders)) {
                        $errors[] = "Row {$rowNumber}: Column count mismatch (expected " . count($normalizedHeaders) . ", got " . count($row) . ")";
                        $errorCount++;
                        continue;
                    }

                    $userData = array_combine($normalizedHeaders, $row);

                    // Clean up data
                    $userData = array_map('trim', $userData);

                    // Skip instruction/comment rows
                    if (strpos($userData['name'] ?? '', '#') === 0) {
                        continue;
                    }

                    // Validate user data
                    $validation = $validator->validateUserData($userData, $rowNumber);

                    if (!$validation['valid']) {
                        $errors[] = "Row {$rowNumber}: " . implode(', ', $validation['errors']);
                        $errorCount++;
                        continue;
                    }

                    // Generate password if not provided
                    $password = isset($userData['password']) && !empty($userData['password'])
                        ? $userData['password']
                        : 'password123'; // Default password

                    // Store valid user data for creation
                    $validUsers[] = [
                        'row_number' => $rowNumber,
                        'data' => [
                            'name' => $userData['name'],
                            'email' => $userData['email'],
                            'phone' => $userData['phone'] ?: null,
                            'password' => Hash::make($password),
                            'role_id' => $validation['role_id'],
                            'district_id' => $validation['district_id'],
                            'church_id' => $validation['church_id'],
                            'is_active' => true,
                        ]
                    ];
                }

                // If there are validation errors, don't proceed with import
                if ($errorCount > 0) {
                    // Clean up uploaded file
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    // Show first few errors
                    $errorSample = array_slice($errors, 0, 5);
                    $errorMessage = implode("\n", $errorSample);
                    if (count($errors) > 5) {
                        $errorMessage .= "\n... and " . (count($errors) - 5) . " more errors";
                    }

                    Notification::make()
                        ->title('Import Failed')
                        ->body("Found {$errorCount} validation errors. No users were imported.\n\n" . $errorMessage)
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }

                // If no valid users to import
                if (empty($validUsers)) {
                    // Clean up uploaded file
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    Notification::make()
                        ->title('No Users to Import')
                        ->body('No valid user data found in the CSV file.')
                        ->warning()
                        ->send();
                    return;
                }

                // Second pass: Create all users in a transaction
                try {
                    DB::transaction(function () use ($validUsers, &$successCount) {
                        foreach ($validUsers as $userInfo) {
                            User::create($userInfo['data']);
                            $successCount++;
                        }
                    });

                    // Clean up uploaded file after successful import
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    Notification::make()
                        ->title('Import Successful')
                        ->body("Successfully imported {$successCount} users.")
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    // Clean up uploaded file
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    Notification::make()
                        ->title('Import Failed')
                        ->body('An error occurred during import: ' . $e->getMessage() . '. No users were imported.')
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}
