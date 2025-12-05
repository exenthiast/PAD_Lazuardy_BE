<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Enums\TutorStatusEnum;
use App\Models\Tutor;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function storeUserProfile(array $data)
    {

    }
    
    public function convertAddressToArray($data)
    {
        Log::info('Converting address - Input data:', [
            'province' => $data['province'] ?? 'NOT SET',
            'regency' => $data['regency'] ?? 'NOT SET',
            'district' => $data['district'] ?? 'NOT SET',
            'subdistrict' => $data['subdistrict'] ?? 'NOT SET',
            'street' => $data['street'] ?? 'NOT SET',
        ]);
        
        return [
            "province" => $data["province"] ?? null,
            "regency" => $data["regency"] ?? null,
            "district" => $data["district"] ?? null,
            "subdistrict" => $data["subdistrict"] ?? null,
            "street" => $data["street"] ?? null,
        ];
    }

    public function convertAddressToString($data)
    {
        return [
            "fullAddress" => "{$data['street']}, {$data['subdistrict']}, {$data['district']}, {$data['regency']}, {$data['province']}, Indonesia",
            "simplifiedAddress" => "{$data['subdistrict']}, {$data['district']}, {$data['regency']}, {$data['province']}, Indonesia",
        ];
    }

    public function showUserProfile(User $query)
    {
        $address = $query->home_address ?? [];
        $data = [
            'name' => $query->name,
            'email' => $query->email,
            'telephone_number' => $query->telephone_number,
            'profile_photo_url' => $query->profile_photo_url,
            'gender' => $query->gender,
            'date_of_birth' => $query->date_of_birth,
            'religion' => $query->religion,
            'latitude' => $query->latitude,
            'longitude' => $query->longitude,

            // Address fields - kirim dengan nama yang konsisten
            'province' => $address['province'] ?? null,
            'regency' => $address['regency'] ?? null,
            'city' => $address['regency'] ?? null, // Alias untuk backward compatibility
            'district' => $address['district'] ?? null,
            'subdistrict' => $address['subdistrict'] ?? null,
            'street' => $address['street'] ?? null,
        ];

        return $data;
    }

    public function storeTutorRole(User $user, Collection $tutorData) {
        DB::beginTransaction();
        try {
            $tutor = Tutor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'experience' => $tutorData['experience'],
                    'organization' => $tutorData['organization'],
                    'course_mode' => $tutorData['course_mode'],
                    'description' => $tutorData['description'],
                    'qualification' => $tutorData['qualification'],
                    'learning_method' => $tutorData['learning_method'],
                    'status' => TutorStatusEnum::VERIFY->value,
                ],
            );

            DB::commit();
            return $tutor;
        } catch(Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
