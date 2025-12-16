<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleStatusEnum;
use App\Enums\TakenScheduleStatusEnum;
use App\Models\StudentPackage;
use App\Models\TakenSchedule;
use App\Models\User;
use App\Models\Review;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentDashboardController extends Controller
{
    /**
     * Get dashboard data untuk student
     * 
     * @OA\Get(
     *     path="/api/dashboard/student",
     *     tags={"Dashboard"},
     *     summary="Get student dashboard data",
     *     description="Menampilkan data lengkap dashboard student: profile, paket belajar, jadwal, dan statistik.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="profile", type="object",
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="class", type="string", example="kelas 10"),
     *                 @OA\Property(property="school", type="string")
     *             ),
     *             @OA\Property(property="packages", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="package_stats", type="object",
     *                 @OA\Property(property="total_packages", type="integer"),
     *                 @OA\Property(property="total_remaining_sessions", type="integer")
     *             ),
     *             @OA\Property(property="schedules", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="schedule_stats", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="User bukan student")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Cek apakah user adalah student
        if (!$user->student) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan student'
            ], 403);
        }

        $student = $user->student;

        // Data Profil Siswa
        $profile = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telephone_number' => $user->telephone_number,
            'profile_photo_url' => $user->profile_photo_url,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'religion' => $user->religion,
            'home_address' => $user->home_address,
            'student_id' => $student->user_id,
            'class' => $student->class ? $student->class->name : null,
            'curriculum' => $student->curriculum ? $student->curriculum->name : null,
            'school' => $student->school,
            'parent_name' => $student->parent,
            'parent_telephone_number' => $student->parent_telephone_number,
        ];

        // Data Paket yang Dimiliki (HANYA YANG APPROVED)
        $packages = StudentPackage::where('student_user_id', $user->id)
            ->where('status', 'approved') // FILTER: Hanya paket yang sudah di-approve admin
            ->where(function($query) {
                $query->whereNull('expired_at')
                      ->orWhere('expired_at', '>=', now()->toDateString());
            }) // FILTER: Belum expired
            ->with(['package', 'subject', 'tutor'])
            ->get()
            ->map(function ($sp) {
                return [
                    'id' => $sp->id,
                    'package_name' => $sp->package?->name ?? null,
                    'package_session' => $sp->package?->session ?? 0,
                    'remaining_session' => $sp->remaining_session ?? $sp->package?->session ?? 0,
                    'used_session' => ($sp->package?->session ?? 0) - ($sp->remaining_session ?? $sp->package?->session ?? 0),
                    'subject_name' => $sp->subject?->name ?? null,
                    'tutor_name' => $sp->tutor?->name ?? null,
                    'tutor_photo' => $sp->tutor?->profile_photo_url ?? null,
                    'start_date' => $sp->start_date,
                    'end_date' => $sp->expired_at, // Rename untuk frontend
                    'status' => $sp->status,
                ];
            });

        // Statistik Paket
        $packageStats = [
            'total_packages' => $packages->count(),
            'total_remaining_sessions' => $packages->sum('remaining_session'),
            'total_used_sessions' => $packages->sum('used_session'),
        ];

        // Jadwal yang Diambil
        $schedules = TakenSchedule::where('user_id', $user->id)
            ->with(['scheduleTutor.user', 'subject'])
            ->orderBy('date', 'desc')
            ->get()
                ->map(function ($ts) {
                return [
                    'id' => $ts->id,
                    'date' => $ts->date,
                    'status' => $ts->status,
                    'subject_name' => $ts->subject?->name ?? null,
                    'tutor_name' => $ts->scheduleTutor?->user?->name ?? null,
                    'tutor_photo' => $ts->scheduleTutor?->user?->profile_photo_url ?? null,
                    'schedule_day' => $ts->scheduleTutor?->day ?? null,
                    'schedule_time' => $ts->scheduleTutor?->time ?? null,
                ];
            });

        // Statistik Jadwal
        $scheduleStats = [
            'total_schedules' => $schedules->count(),
            'completed_schedules' => $schedules->where('status', ScheduleStatusEnum::COMPLETED->value)->count(),
            'pending_schedules' => $schedules->where('status', ScheduleStatusEnum::PENDING->value)->count(),
            'cancelled_schedules' => $schedules->where('status', ScheduleStatusEnum::CANCELLED->value)->count(),
        ];

        // Jadwal Mendatang (7 hari ke depan)
        $upcomingSchedules = TakenSchedule::where('user_id', $user->id)
            ->where('date', '>=', now()->toDateString())
            ->where('date', '<=', now()->addDays(7)->toDateString())
            ->where('status', '!=', ScheduleStatusEnum::CANCELLED->value)
            ->with(['scheduleTutor.user', 'subject'])
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($ts) {
                return [
                    'id' => $ts->id,
                    'date' => $ts->date,
                    'status' => $ts->status,
                    'subject_name' => $ts->subject?->name ?? null,
                    'tutor_name' => $ts->scheduleTutor?->user?->name ?? null,
                    'schedule_time' => $ts->scheduleTutor?->time ?? null,
                ];
            });

        // Daftar Tutor yang Pernah Mengajar (hanya unique tutors)
        $myTutors = StudentPackage::where('student_user_id', $user->id)
            ->with(['tutor'])
            ->get()
            ->unique('tutor_user_id')
            ->map(function ($sp) {
                return [
                    'tutor_id' => $sp->tutor_user_id,
                    'tutor_name' => $sp->tutor?->name ?? null,
                    'tutor_photo' => $sp->tutor?->profile_photo_url ?? null,
                    'tutor_education' => $sp->tutor?->tutor?->education ?? null,
                    'tutor_experience' => $sp->tutor?->tutor?->experience ?? null,
                ];
            })
            ->values();

        // Mata Pelajaran yang Dipelajari
        $subjects = StudentPackage::where('student_user_id', $user->id)
            ->with('subject')
            ->get()
            ->unique('subject_id')
            ->map(function ($sp) {
                return [
                    'subject_id' => $sp->subject_id,
                    'subject_name' => $sp->subject?->name ?? null,
                    'subject_icon' => $sp->subject?->icon_image_url ?? null,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile' => $profile,
                'packages' => $packages,
                'package_stats' => $packageStats,
                'schedules' => $schedules,
                'schedule_stats' => $scheduleStats,
                'upcoming_schedules' => $upcomingSchedules,
                'my_tutors' => $myTutors,
                'subjects' => $subjects,
            ]
        ], 200);
    }

    /**
     * Get recommended tutors dengan pagination (5 tutor per page)
     * Untuk kotak "Tutor Rekomendasi" di dashboard
     * 
     * @OA\Get(
     *     path="/api/dashboard/student/recommended-tutors",
     *     tags={"Dashboard"},
     *     summary="Get recommended tutors for student",
     *     description="Menampilkan daftar tutor rekomendasi berdasarkan kedekatan lokasi (5 tutors per page).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="profile_photo_url", type="string"),
     *                     @OA\Property(property="education", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="subjects", type="array", @OA\Items(type="object"))
     *                 )
     *             ),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="User bukan student")
     * )
     */
    public function getRecommendedTutors(Request $request)
    {
        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan student'
            ], 403);
        }

        // Ambil alamat student
        $studentAddress = $user->home_address;
        
        // Query tutor dengan status active
        $tutorsQuery = User::where('role', 'tutor')
            ->whereHas('tutor', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['tutor', 'subjects']);

        // Sorting berdasarkan kedekatan alamat (prioritas: regency > district > province)
        if ($studentAddress) {
            $tutorsQuery->orderByRaw("
                CASE
                    WHEN JSON_EXTRACT(home_address, '$.regency') = ? THEN 1
                    WHEN JSON_EXTRACT(home_address, '$.district') = ? THEN 2
                    WHEN JSON_EXTRACT(home_address, '$.province') = ? THEN 3
                    ELSE 4
                END
            ", [
                $studentAddress['regency'] ?? '',
                $studentAddress['district'] ?? '',
                $studentAddress['province'] ?? ''
            ]);
        }

        // Pagination: 5 tutors per page
        $tutors = $tutorsQuery->paginate(5);

        return response()->json([
            'status' => 'success',
            'data' => $tutors->map(function ($tutor) {
                // Hitung total siswa yang memilih tutor ini
                $totalStudents = StudentPackage::where('tutor_user_id', $tutor->id)
                    ->distinct('student_user_id')
                    ->count('student_user_id');
                
                // Hitung total sesi yang sudah diberikan
                $totalSessions = TakenSchedule::whereHas('scheduleTutor', function ($query) use ($tutor) {
                        $query->where('user_id', $tutor->id);
                    })
                    ->where('status', ScheduleStatusEnum::COMPLETED->value)
                    ->count();
                
                // Hitung rating dan review
                $reviews = Review::where('to_user_id', $tutor->id)->get();
                $averageRating = $reviews->avg('rate') ?? 0;
                $totalReviews = $reviews->count();
                
                // Latest review text
                $latestReview = Review::where('to_user_id', $tutor->id)
                    ->with('fromUser')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                return [
                    'tutor_id' => $tutor->id,
                    'tutor_name' => $tutor->name,
                    'tutor_photo' => $tutor->profile_photo_url,
                    'gender' => $tutor->gender,
                    'address' => $tutor->home_address,
                    'telephone_number' => $tutor->telephone_number,
                    'education' => $tutor->tutor?->education ?? null,
                    'experience' => $tutor->tutor?->experience ?? 0,
                    'price' => $tutor->tutor?->price ?? 0,
                    'description' => $tutor->tutor?->description ?? null,
                    'course_mode' => $tutor->tutor?->course_mode ?? null,
                    'badge' => $tutor->tutor?->badge ?? null,
                    'keahlian' => $tutor->tutor?->keahlian ?? null,
                    'total_students' => $totalStudents,
                    'total_sessions' => $totalSessions,
                    'rating' => round($averageRating, 1),
                    'total_reviews' => $totalReviews,
                    'latest_review' => $latestReview ? [
                        'review_text' => $latestReview->review,
                        'rate' => $latestReview->rate,
                        'student_name' => $latestReview->fromUser?->name ?? 'Anonymous',
                        'created_at' => $latestReview->created_at,
                    ] : null,
                    'subjects' => $tutor->subjects->map(function ($subject) {
                        return [
                            'subject_id' => $subject->id,
                            'subject_name' => $subject->name,
                            'subject_icon' => $subject->icon_image_url ?? null,
                        ];
                    }),
                ];
            }),
            'pagination' => [
                'current_page' => $tutors->currentPage(),
                'last_page' => $tutors->lastPage(),
                'per_page' => $tutors->perPage(),
                'total' => $tutors->total(),
                'has_more' => $tutors->hasMorePages(),
            ]
        ], 200);
    }

    /**
     * Get summary untuk widget dashboard
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan student'
            ], 403);
        }

        $summary = [
            'total_packages' => StudentPackage::where('student_user_id', $user->id)->count(),
            'total_remaining_sessions' => StudentPackage::where('student_user_id', $user->id)->sum('remaining_session'),
            'total_schedules_today' => TakenSchedule::where('user_id', $user->id)
                ->where('date', now()->toDateString())
                ->count(),
            'total_upcoming_schedules' => TakenSchedule::where('user_id', $user->id)
                ->where('date', '>=', now()->toDateString())
                ->where('status', '!=', ScheduleStatusEnum::CANCELLED->value)
                ->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $summary
        ], 200);
    }

    /**
     * Get student's schedules (untuk halaman Jadwal Saya)
     */
    public function getMySchedules(Request $request)
    {
        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan student'
            ], 403);
        }

        $status = $request->query('status', 'all'); // all, upcoming, completed, cancelled

        $query = TakenSchedule::where('user_id', $user->id)
            ->with(['scheduleTutor.user.tutor', 'subject', 'studentAttendance'])
            ->orderBy('date', 'desc');

        // Filter by status
        if ($status === 'upcoming') {
            $query->where('date', '>=', now()->toDateString())
                ->where('status', TakenScheduleStatusEnum::ACTIVE);
        } elseif ($status === 'completed') {
            $query->where('status', TakenScheduleStatusEnum::COMPLETED);
        } elseif ($status === 'cancelled') {
            $query->where('status', TakenScheduleStatusEnum::CANCELLED);
        }

        $schedules = $query->get()->map(function ($schedule) {
            $tutor = $schedule->scheduleTutor->user ?? null;
            $tutorPhoto = $tutor?->profile_photo_url ?? null;
            
            // Convert photo URL
            if ($tutorPhoto && !str_starts_with($tutorPhoto, 'http')) {
                if (str_starts_with($tutorPhoto, 'storage/')) {
                    $tutorPhoto = url($tutorPhoto);
                } else {
                    $tutorPhoto = url('storage/' . $tutorPhoto);
                }
            }

            $hasAttendance = $schedule->studentAttendance !== null;

            return [
                'id' => $schedule->id,
                'subject' => $schedule->subject->name ?? 'N/A',
                'tutor_id' => $tutor?->id,
                'tutor_name' => $tutor?->name ?? 'N/A',
                'tutor_photo' => $tutorPhoto,
                'date' => $schedule->date,
                'time' => $schedule->scheduleTutor->time ?? 'N/A',
                'status' => $schedule->status,
                'course_mode' => $schedule->course_mode ?? 'online',
                'meeting_link' => $schedule->meeting_link,
                'meeting_link_sent' => $schedule->meeting_link_sent ?? false,
                'has_attendance' => $hasAttendance,
                'attendance_submitted_at' => $schedule->studentAttendance?->submitted_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $schedules
        ], 200);
    }

    /**
     * Submit student attendance (bukti kehadiran)
     */
    public function submitAttendance(Request $request, $scheduleId)
    {
        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan student'
            ], 403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'proof_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

        // Verify schedule belongs to this student
        $schedule = TakenSchedule::where('id', $scheduleId)
            ->where('user_id', $user->id)
            ->first();

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        // Check if attendance already submitted
        $existingAttendance = StudentAttendance::where('taken_schedule_id', $scheduleId)
            ->where('student_user_id', $user->id)
            ->first();

        DB::beginTransaction();
        try {
            $proofPath = null;
            
            // Handle file upload
            if ($request->hasFile('proof_document')) {
                $file = $request->file('proof_document');
                $proofPath = $file->store('student_attendances', 'public');
            }

            if ($existingAttendance) {
                // Update existing
                if ($proofPath && $existingAttendance->proof_document) {
                    Storage::disk('public')->delete($existingAttendance->proof_document);
                }
                
                $existingAttendance->update([
                    'notes' => $validated['notes'] ?? null,
                    'proof_document' => $proofPath ?: $existingAttendance->proof_document,
                    'submitted_at' => now(),
                ]);
                
                $attendance = $existingAttendance;
            } else {
                // Create new
                $attendance = StudentAttendance::create([
                    'taken_schedule_id' => $scheduleId,
                    'student_user_id' => $user->id,
                    'notes' => $validated['notes'] ?? null,
                    'proof_document' => $proofPath,
                    'submitted_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bukti kehadiran berhasil dikirim',
                'data' => $attendance
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($proofPath) {
                Storage::disk('public')->delete($proofPath);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim bukti kehadiran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit review for tutor
     */
    public function submitReview(Request $request, $tutorId)
    {
        $user = $request->user();
        
        if (!$user->student) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan student'
            ], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // 2MB
        ]);

        // Verify tutor exists
        $tutor = User::whereHas('tutor')->find($tutorId);
        if (!$tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tutor tidak ditemukan'
            ], 404);
        }

        // Check if student has completed session with this tutor
        $hasCompletedSession = TakenSchedule::whereHas('scheduleTutor', function ($query) use ($tutorId) {
                $query->where('user_id', $tutorId);
            })
            ->where('user_id', $user->id)
            ->where('status', TakenScheduleStatusEnum::COMPLETED)
            ->exists();

        if (!$hasCompletedSession) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum pernah belajar dengan tutor ini'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $photoPath = null;
            
            // Handle photo upload
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $photoPath = $file->store('reviews', 'public');
            }

            $review = Review::create([
                'reviewer_id' => $user->id,
                'tutor_id' => $tutorId,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'photo_path' => $photoPath,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Review berhasil dikirim',
                'data' => $review
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history untuk student
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get all payments with order and package info
            $payments = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->join('packages', 'orders.package_id', '=', 'packages.id')
                ->leftJoin('student_packages', function($join) use ($user) {
                    $join->on('orders.package_id', '=', 'student_packages.package_id')
                         ->where('student_packages.student_user_id', '=', $user->id);
                })
                ->where('orders.user_id', $user->id)
                ->select(
                    'payments.id',
                    'payments.order_id',
                    'payments.amount',
                    'payments.payment_method',
                    'payments.payment_proof',
                    'payments.status',
                    'payments.created_at',
                    'packages.name as package_name',
                    'student_packages.status as package_status',
                    'student_packages.start_date',
                    'student_packages.expired_at'
                )
                ->orderBy('payments.created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $payments
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil riwayat pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }
}
