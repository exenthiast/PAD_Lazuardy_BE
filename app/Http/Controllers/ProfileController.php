<?php

namespace App\Http\Controllers;

use App\Enums\DayEnum;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Http\Requests\UpdateTutorLessonMethodRequest;
use App\Http\Requests\UpdateTutorProfileRequest;
use App\Services\StudentService;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{ 
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

        $userService = new UserService;

        $userData = $userService->showUserProfile($user);
        $message = ['message' => 'success'];
        
        $data = array_merge($userData, $message);

        return response()->json($data, 200);
    }

    public function updateStudentProfile(UpdateStudentProfileRequest $request)
    {
        $request->validated();

        $user = $request->user()->load(['student']);
        $student = $user->student;

        $userService = new UserService;

        $address = $userService->convertAddressToArray($request);
        $userData = $request->only(['name', 'telephone_number', 'profile_photo_url', 'gender', 'date_of_birth', 'religion', 'latitude', 'longitude']);
        $userData['home_address'] = $address;
        
        DB::beginTransaction();
        try 
        {
            $user->update($userData);
            $student->update($request->only(['school', 'class_id', 'curriculum_id', 'parent', 'parent_telephone_number']));
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Profile berhasil di update',
            ],200);
        } 
        catch(Exception $e) 
        {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate profil: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
            ], 500); 
        }
    }
    
    public function updateTutorProfile(UpdateTutorProfileRequest $request)
    {
        $request->validated();

        $user = $request->user()->load(['tutor']);
        $tutor = $user->tutor;
        
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
        ]);

        // Handle name edit dengan validasi 7 hari (untuk social auth)
        if ($request->has('name') && !empty($userData['name'])) {
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
            
            $userData['last_name_edit'] = now();
        }

        // Handle skills array (untuk social auth)
        if ($request->has('skills') && is_array($request->input('skills'))) {
            $skills = $request->input('skills');
            if (!empty($skills)) {
                $tutorData['keahlian'] = $skills[0];
            }
        }

        // Handle schedule array (untuk social auth)
        if ($request->has('schedule')) {
            $tutorData['learning_method'] = json_encode($request->input('schedule'));
        }

        // Handle education array (untuk social auth)
        if ($request->has('education')) {
            $tutorData['education'] = $request->input('education');
        }

        $userService = new UserService;
        
        if (!empty($rawAddress)) {
            $address = $userService->convertAddressToArray($rawAddress);
            $userData = array_merge($userData, $address);
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
