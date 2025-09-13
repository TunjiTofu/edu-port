<?php

namespace App\Filament\Resources\UserResource\Actions;

use App\Models\Church;
use App\Models\District;
use App\Models\Role;
use Filament\Actions\Action;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadTemplateAction
{
    public static function make(): Action
    {
        return Action::make('download_template')
            ->label('Download CSV Template')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function () {
                $fileName = 'user_import_template_' . date('Y-m-d') . '.csv';

                return new StreamedResponse(function () {
                    $handle = fopen('php://output', 'w');

                    // Add BOM for Excel compatibility
                    fwrite($handle, "\xEF\xBB\xBF");

                    // Headers
                    $headers = [
                        'name',
                        'email',
                        'phone',
                        'role_name',
                        'district_name',
                        'church_name',
                        'password'
                    ];

                    fputcsv($handle, $headers);

                    // Sample data row
                    $sampleRole = Role::first();
                    $sampleDistrict = District::first();
                    $sampleChurch = Church::first();

                    $sampleData = [
                        'John Doe',
                        'john.doe@example.com',
                        '+1234567890',
                        $sampleRole ? $sampleRole->name : 'student',
                        $sampleDistrict ? $sampleDistrict->name : 'Sample District',
                        $sampleChurch ? $sampleChurch->name : 'Sample Church',
                        'password123'
                    ];

                    fputcsv($handle, $sampleData);

                    // Instructions as comments
                    $availableRoles = Role::pluck('name')->take(5)->implode(', ');
                    $availableDistricts = District::pluck('name')->take(3)->implode(', ');

                    fputcsv($handle, [
                        '# INSTRUCTIONS: Replace the sample data above with actual user data',
                        '',
                        '',
                        'Available roles: ' . $availableRoles,
                        'Districts: ' . $availableDistricts . '...',
                        'Churches must belong to selected district',
                        'Leave password empty for auto-generation'
                    ]);

                    fclose($handle);
                }, 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Expires' => '0',
                    'Pragma' => 'public',
                ]);
            });
    }
}
