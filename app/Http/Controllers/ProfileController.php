<?php

namespace App\Http\Controllers;

use App\Enums\DayEnum;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Http\Requests\UpdateTutorLessonMethodRequest;
use App\Http\Requests\UpdateTutorProfileRequest;
use App\Models\ScheduleTutor;
use App\Services\StudentService;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{ 
    /**
     * @OA\Get(
     *     path="/api/classes",
     *     tags={"Profile"},
     *     summary="Get list of classes",
     *     description="Mendapatkan daftar kelas yang tersedia",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getClasses()
    {
        $classes = \App\Models\ClassModel::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $classes
        ], 200);
    }
    
    /**
     * @OA\Get(
     *     path="/api/student/profile",
     *     tags={"Profile"},
     *     summary="Get student profile",
     *     description="Menampilkan data lengkap profile student termasuk user data dan student-specific data.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="school", type="string"),
     *             @OA\Property(property="class", type="string")
     *         )
     *     )
     * )
     */
    public function showStudentProfile(Request $request)
    {
        $user = $request->user()->load(['student.class']);
        $student = $user->student;

        $userService = new UserService;
        $studentService = new StudentService;

        $userData = $userService->showUserProfile($user);
        $studentData = $studentService->showStudentProfile($student);
        $message = ['status' => 'success'];
        
        $data = array_merge($message, $userData, $studentData);

        return response()->json($data, 200);
    }

    public function showTutorProfile(Request $request)
    {
        $user = $request->user()->load(['tutor']);
        $tutor = $user->tutor;

        if (!$tutor) {
            return response()->json([
                'message' => 'Tutor profile not found',
            ], 404);
        }

        // Parse home_address JSON if exists
        $homeAddress = null;
        if ($user->home_address) {
            $homeAddress = is_string($user->home_address) 
                ? json_decode($user->home_address, true) 
                : $user->home_address;
        }

        // Format gender display
        $genderDisplay = 'N/A';
        if ($user->gender) {
            if (is_object($user->gender) && method_exists($user->gender, 'displayName')) {
                $genderDisplay = $user->gender->displayName();
            } elseif (is_string($user->gender)) {
                $genderDisplay = ucfirst($user->gender);
            }
        }

        // Format religion display
        $religionDisplay = 'N/A';
        if ($user->religion) {
            if (is_object($user->religion)) {
                // Jika enum object, ambil value-nya lalu gunakan static displayName
                $religionValue = $user->religion->value ?? (string)$user->religion;
                $religionDisplay = \App\Enums\ReligionEnum::displayName($religionValue);
            } elseif (is_string($user->religion)) {
                try {
                    $religionDisplay = \App\Enums\ReligionEnum::displayName($user->religion);
                } catch (\Exception $e) {
                    $religionDisplay = ucfirst($user->religion);
                }
            }
        }

        $data = [
            'message' => 'success',
            'id' => $user->id,
            'namaLengkap' => $user->name,
            'email' => $user->email,
            'photo' => $user->profile_photo_url ? url('storage/' . $user->profile_photo_url) : null,
            'jenisKelamin' => $genderDisplay,
            'tanggalLahir' => $user->date_of_birth ?? 'N/A',
            'noTelepon' => $user->telephone_number ?? 'N/A',
            'agama' => $religionDisplay,
            'lastNameEdit' => $user->last_name_edit,
            'provinsi' => $homeAddress['province'] ?? 'N/A',
            'kota' => $homeAddress['city'] ?? $homeAddress['regency'] ?? 'N/A',
            'kecamatan' => $homeAddress['district'] ?? 'N/A',
            'desa' => $homeAddress['subdistrict'] ?? $homeAddress['village'] ?? 'N/A',
            'alamatLengkap' => $homeAddress['street'] ?? $homeAddress['address'] ?? 'N/A',
            'latitude' => $user->latitude ?? null,
            'longitude' => $user->longitude ?? null,
            'keahlian' => $tutor->keahlian ?? 'N/A',
            'marketSiswa' => $tutor->market_siswa ? strtoupper($tutor->market_siswa) : 'N/A',
            'pengalaman' => $tutor->pengalaman ?? $tutor->experience ?? 'N/A',
            'organisasi' => $tutor->organisasi ?? $tutor->organization ?? 'N/A',
            'skillBahasa' => $tutor->skil_bahasa ?? 'N/A',
            'pendidikan' => $tutor->education ?? [],
            'tentangSaya' => $tutor->description ?? 'N/A',
            'jadwalMengajar' => $tutor->learning_method ? (is_string($tutor->learning_method) ? json_decode($tutor->learning_method, true) : $tutor->learning_method) : [],
            'hargaPerPertemuan' => $tutor->price ?? 0,
            'bankName' => $tutor->bank_name ?? 'N/A',
            'bankAccountNumber' => $tutor->bank_account_number ?? 'N/A',
            'bankAccountName' => $tutor->bank_account_name ?? 'N/A',
            'documents' => [
                'cv' => $tutor->cv_path ? url('storage/' . $tutor->cv_path) : null,
                'ktp' => $tutor->ktp_path ? url('storage/' . $tutor->ktp_path) : null,
                'ijazah' => $tutor->ijazah_path ? url('storage/' . $tutor->ijazah_path) : null,
            ],
            'status' => $tutor->status ? $tutor->status->displayName() : 'N/A',
            'statusEnum' => $tutor->status ? $tutor->status->value : null,
        ];

        return response()->json($data, 200);
    }

    public function updateStudentProfile(UpdateStudentProfileRequest $request)
    {
        $request->validated();

        $user = $request->user()->load(['student']);
        $student = $user->student;

        $photoPath = null;
        $photoUrl = null;

        // Handle photo upload jika ada
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $photoPath = $file->store('student/photos', 'public');
            $photoUrl = url('storage/' . $photoPath);
        }

        // Gunakan $request->all() untuk membaca FormData dengan benar
        $allData = $request->all();
        
        // Build address array dari field datar
        $addressData = [
            'province' => $allData['province'] ?? null,
            'regency' => $allData['regency'] ?? null,
            'district' => $allData['district'] ?? null,
            'subdistrict' => $allData['subdistrict'] ?? null,
            'street' => $allData['street'] ?? null,
        ];
        
        // Build userData untuk update kolom users table
        $userData = [
            'name' => $allData['name'] ?? null,
            'telephone_number' => $allData['telephone_number'] ?? null,
            'gender' => $allData['gender'] ?? null,
            'date_of_birth' => $allData['date_of_birth'] ?? null,
            'religion' => $allData['religion'] ?? null,
            'home_address' => $addressData, // Simpan address sebagai JSON
        ];
        
        // Tambahkan latitude/longitude jika ada
        if (!empty($allData['latitude'])) {
            $userData['latitude'] = $allData['latitude'];
        }
        if (!empty($allData['longitude'])) {
            $userData['longitude'] = $allData['longitude'];
        }
        
        // Tambahkan photo path jika ada upload
        if ($photoPath) {
            $userData['profile_photo_url'] = $photoPath;
        }
        
        // Filter hanya data yang tidak null (agar tidak overwrite dengan null)
        $userData = array_filter($userData, function($value) {
            return $value !== null && $value !== '';
        });
        
        DB::beginTransaction();
        try 
        {
            // Update user data
            $user->update($userData);
            
            // Build student data
            $studentData = [];
            
            if (!empty($allData['school'])) {
                $studentData['school'] = $allData['school'];
            }
            
            // Handle class - bisa terima class_id (integer) atau class name (string)
            if (!empty($allData['class_id'])) {
                $studentData['class_id'] = $allData['class_id'];
            } elseif (!empty($allData['class'])) {
                // Jika dikirim sebagai class name string, cari ID-nya
                $className = $allData['class'];
                $class = \App\Models\ClassModel::where('name', $className)->first();
                if ($class) {
                    $studentData['class_id'] = $class->id;
                }
            }
            
            if (!empty($allData['curriculum_id'])) {
                $studentData['curriculum_id'] = $allData['curriculum_id'];
            }
            if (!empty($allData['parent'])) {
                $studentData['parent'] = $allData['parent'];
            }
            if (!empty($allData['parent_telephone_number'])) {
                $studentData['parent_telephone_number'] = $allData['parent_telephone_number'];
            }
            
            // Jika student belum ada, buat baru; jika sudah ada, update
            if (!$student) {
                $studentData['user_id'] = $user->id;
                $student = $user->student()->create($studentData);
            } else {
                if (!empty($studentData)) {
                    $student->update($studentData);
                }
            }
            
            DB::commit();
            
            // Reload user data setelah commit
            $freshUser = $user->fresh()->load('student');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Profile berhasil di update',
                'photo_url' => $photoUrl ?? null,
                'user' => $freshUser,
            ],200);
        } 
        catch(Exception $e) 
        {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate profil: ' . $e->getMessage(),
            ], 500); 
        }
    }
    
    public function updateTutorProfile(UpdateTutorProfileRequest $request)
    {
        $request->validated();

        $user = $request->user()->load(['tutor']);
        $tutor = $user->tutor;
        
        $photoPath = null;
        $photoUrl = null;

        // Handle photo upload jika ada
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $photoPath = $file->store('tutor/photos', 'public');
            $photoUrl = url('storage/' . $photoPath);
        }
        
        // Data user (fields yang ada di tabel users)
        $userData = $request->only([
            'name', 
            'gender', 
            'date_of_birth', 
            'religion',
            'telephone_number',
            'latitude',
            'longitude',
        ]);
        
        // Tambahkan photo path jika ada
        if ($photoPath) {
            $userData['profile_photo_url'] = $photoPath;
        }
        
        // Alamat
        $rawAddress = $request->only([
            'province',
            'regency',
            'district',
            'subdistrict',
            'street',
        ]);
        
        // Data tutor (fields yang ada di tabel tutors)
        $tutorData = $request->only([
            'bank',
            'rekening',
            'description',
            'keahlian',
            'price',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
        ]);

        // Handle field tutor tambahan
        if ($request->has('marketSiswa')) {
            $tutorData['market_siswa'] = $request->input('marketSiswa');
        }
        if ($request->has('pengalaman')) {
            $tutorData['pengalaman'] = $request->input('pengalaman');
        }
        if ($request->has('skillBahasa')) {
            $tutorData['skil_bahasa'] = $request->input('skillBahasa');
        }
        if ($request->has('organisasi')) {
            $tutorData['organisasi'] = $request->input('organisasi');
        }
        if ($request->has('pendidikan')) {
            // Jika dikirim sebagai JSON string, decode dulu
            $pendidikan = $request->input('pendidikan');
            $tutorData['education'] = is_string($pendidikan) ? json_decode($pendidikan, true) : $pendidikan;
        }

        // Handle address jika dikirim sebagai string lengkap
        if ($request->has('address') && is_string($request->input('address'))) {
            // Preserve existing home_address data and merge with new address
            $existingAddress = is_string($user->home_address) 
                ? json_decode($user->home_address, true) 
                : ($user->home_address ?: []);
            
            $existingAddress['address'] = $request->input('address');
            $userData['home_address'] = $existingAddress;
        }
        
        // Handle latitude/longitude separately
        if ($request->has('latitude')) {
            $userData['latitude'] = $request->input('latitude');
        }
        if ($request->has('longitude')) {
            $userData['longitude'] = $request->input('longitude');
        }

        // Handle name edit dengan validasi 7 hari (untuk social auth)
        // Hanya validasi jika nama benar-benar berubah
        if ($request->has('name') && !empty($userData['name'])) {
            // Cek apakah nama berubah
            $nameChanged = $user->name !== $userData['name'];
            
            if ($nameChanged) {
                $lastNameEdit = $user->last_name_edit;
                
                if ($lastNameEdit) {
                    $daysSinceLastEdit = now()->diffInDays($lastNameEdit);
                    
                    if ($daysSinceLastEdit < 7) {
                        $nextEditDate = \Carbon\Carbon::parse($lastNameEdit)->addDays(7)->format('d F Y');
                        return response()->json([
                            'status' => 'error',
                            'message' => "Nama hanya dapat diubah sekali dalam 7 hari. Anda dapat mengubah nama kembali pada {$nextEditDate}",
                            'can_edit_at' => $nextEditDate,
                            'days_remaining' => 7 - $daysSinceLastEdit
                        ], 422);
                    }
                }
                
                // Update last_name_edit hanya jika nama berubah
                $userData['last_name_edit'] = now();
            }
        }

        // Handle skills array (untuk social auth)
        if ($request->has('skills') && is_array($request->input('skills'))) {
            $skills = $request->input('skills');
            if (!empty($skills)) {
                $tutorData['keahlian'] = $skills[0];
            }
        }

        // Handle schedule array - NEW FORMAT with day and time_slots
        if ($request->has('schedules')) {
            $schedulesData = $request->input('schedules');
            
            // Delete existing schedules for this tutor
            $user->schedules()->delete();
            
            // Create new schedules
            foreach ($schedulesData as $daySchedule) {
                $day = $daySchedule['day'];
                $timeSlots = $daySchedule['time_slots'] ?? [];
                
                foreach ($timeSlots as $slot) {
                    ScheduleTutor::create([
                        'user_id' => $user->id,
                        'day' => $day,
                        'time' => $slot['time'],
                    ]);
                }
            }
        }
        // Handle old schedule format (for backward compatibility)
        elseif ($request->has('schedule')) {
            $tutorData['learning_method'] = json_encode($request->input('schedule'));
        }

        // Handle education array (untuk social auth)
        if ($request->has('education')) {
            $tutorData['education'] = $request->input('education');
        }

        $userService = new UserService;
        
        // Handle address - store in home_address JSON field
        if (!empty($rawAddress)) {
            $address = $userService->convertAddressToArray($rawAddress);
            $userData['home_address'] = $address;
        }
        
        DB::beginTransaction();
        try{
            // Update user data jika ada
            if (!empty($userData)) {
                $user->update($userData);
            }
            
            // Update tutor data jika ada
            if (!empty($tutorData)) {
                $tutor->update($tutorData);
            }
            
            DB::commit();

            // Reload user dengan relasi
            $user->load('tutor');

            return response()->json([
                'status' => 'success',
                'message' => 'Profil berhasil diperbarui',
                'user' => $user,
                'tutor' => $tutor
            ], 200);
        } catch(Exception $e) {
            
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate profil: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ], 500);
        }
    }

    // Formulir profile tutor yang bagian pilih jadwal
    public function showTutorLessonMethod(Request $request) {
        $user = $request->user()->load(['tutor', 'schedules']);
        $tutor = $user->tutor;

        $data = [
            'course_mode' => $tutor->course_mode,
            'description' => $tutor->description,
            'qualification' => $tutor->qualification,
            'learning_method' => $tutor->learning_method,
            'schedules' =>  $user->schedules,
        ];

        return response()->json($data, 200);
    }

    // Formulir profile tutor yang bagian pilih jadwal
    public function updateTutorLessonMethod(UpdateTutorLessonMethodRequest $request)
    {
        $user = $request->user()->load(['tutor', 'schedules']);
        $tutor = $user->tutor;
        $schedules = $user->schedules();

        $tutorData = $request->only([
            'course_mode',
            'description',
            'qualification',
            'learning_method',
        ]);

        $schedulesData = $request->input('schedules');

        DB::beginTransaction();
        try{
            $tutor->update($tutorData);
            $user->schedules()->delete();

            $schedulesToCreate = collect($schedulesData)->map(function ($schedule) use ($user) {
                return array_merge($schedule, ['user_id' => $user->id]);
            })->toArray();

            $user->schedules()->insert($schedulesToCreate);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Profile dan jadwal tutor berhasil diperbarui.'
            ], 200);
        } catch(Exception $e){
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Profile dan jadwal tutor gagal diperbarui: ' . $e->getMessage(),
            ], 500);
        }
    }
}
