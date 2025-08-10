<?php

namespace App\Services;

use App\Models\Role;
use App\Models\District;
use App\Models\Church;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CsvUserValidationService
{
    private $roles;
    private $districts;
    private $churches;

    public function __construct()
    {
        $this->roles = Role::all()->keyBy('name');
        $this->districts = District::all()->keyBy('name');
        $this->churches = Church::with('district')->get()->keyBy('name');
    }

    public function validateCsvHeaders(array $headers): array
    {
        $normalizedHeaders = array_map(function($header) {
            // Remove BOM and trim whitespace
            $cleanHeader = trim(str_replace("\xEF\xBB\xBF", '', $header));
            return strtolower($cleanHeader);
        }, $headers);

        $requiredHeaders = ['name', 'email', 'phone', 'role_name', 'district_name', 'church_name'];
        $optionalHeaders = ['password'];

        $missingRequired = array_diff($requiredHeaders, $normalizedHeaders);
        $extraHeaders = array_diff($normalizedHeaders, array_merge($requiredHeaders, $optionalHeaders));

        return [
            'valid' => empty($missingRequired),
            'missing_required' => $missingRequired,
            'extra_headers' => $extraHeaders,
            'normalized' => $normalizedHeaders
        ];
    }

    public function validateUserData(array $userData, int $rowNumber): array
    {
        $errors = [];

        // Normalize phone number if provided
        if (!empty($userData['phone'])) {
            // Remove all non-digit characters and normalize
            $phone = preg_replace('/\D/', '', $userData['phone']);
            $phoneLength = strlen($phone);

            // Validate and normalize in one step
            if ($phoneLength === 10) {
                $userData['phone'] = '0' . $phone;
            } elseif (!($phoneLength === 11 && str_starts_with($phone, '0'))) {
                $errors[] = "Phone number must be 10 digits (without leading 0) or 11 digits (with leading 0)";
            }
            // Valid 11-digit numbers are already correctly formatted
        }

        // Basic field validation
        $validator = Validator::make($userData, [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'phone' => [
                'nullable',
                'string',
                'size:11',
                'regex:/^0\d{10}$/',
                Rule::unique('users', 'phone')->whereNull('deleted_at')
            ],
            'role_name' => 'required|string',
            'district_name' => 'required|string',
            'church_name' => 'required|string',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            $errors = array_merge($errors, $validator->errors()->all());
        }

        // Role validation
        if (!empty($userData['role_name'])) {
            $role = $this->roles->get($userData['role_name']);
            if (!$role) {
                $availableRoles = $this->roles->keys()->implode(', ');
                $errors[] = "Role '{$userData['role_name']}' not found. Available roles: {$availableRoles}";
            }
        }

        // District validation
        if (!empty($userData['district_name'])) {
            $district = $this->districts->get($userData['district_name']);
            if (!$district) {
                $availableDistricts = $this->districts->keys()->take(5)->implode(', ');
                $errors[] = "District '{$userData['district_name']}' not found. Sample districts: {$availableDistricts}...";
            }
        }

        // Church validation
        if (!empty($userData['church_name']) && !empty($userData['district_name'])) {
            $church = $this->churches->get($userData['church_name']);
            $district = $this->districts->get($userData['district_name']);

            if (!$church) {
                $errors[] = "Church '{$userData['church_name']}' not found";
            } elseif ($district && $church->district_id !== $district->id) {
                $errors[] = "Church '{$userData['church_name']}' does not belong to district '{$userData['district_name']}'";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'role_id' => $this->roles->get($userData['role_name'] ?? '')?->id,
            'district_id' => $this->districts->get($userData['district_name'] ?? '')?->id,
            'church_id' => $this->churches->get($userData['church_name'] ?? '')?->id,
        ];
    }
    public function getAvailableOptions(): array
    {
        return [
            'roles' => $this->roles->keys()->toArray(),
            'districts' => $this->districts->keys()->toArray(),
            'churches' => $this->churches->keys()->toArray(),
        ];
    }
}
