<?php

namespace App\Http\Controllers;

use App\Enums\TakenScheduleStatusEnum;
use App\Models\StudentPackage;
use App\Models\TakenSchedule;
use App\Models\ScheduleTutor;
use App\Models\Review;
use App\Models\SessionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TutorDashboardController extends Controller
{
    /**
     * Get dashboard data untuk tutor
     * 
     * @OA\Get(
     *     path="/api/dashboard/tutor",
     *     tags={"Dashboard"},
     *     summary="Get tutor dashboard data",
     *     description="Menampilkan data lengkap dashboard tutor: profile, students, jadwal, earnings, dan statistik.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="profile", type="object"),
     *             @OA\Property(property="subjects", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="students", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="earnings", type="object"),
     *             @OA\Property(property="statistics", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="User bukan tutor")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Cek apakah user adalah tutor
        if (!$user->tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan tutor'
            ], 403);
        }

        $tutor = $user->tutor;

        // Data Profil Tutor
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
            'education' => $tutor->education,
            'salary' => $tutor->salary,
            'price' => $tutor->price,
            'description' => $tutor->description,
            'experience' => $tutor->experience,
            'organization' => $tutor->organization,
            'learning_method' => $tutor->learning_method,
            'qualification' => $tutor->qualification,
            'course_mode' => $tutor->course_mode,
            'status' => $tutor->status,
            'badge' => $tutor->badge,
            'sanction_amount' => $tutor->sanction_amount,
        ];

        // Mata Pelajaran yang Diajarkan
        $subjects = $user->subjects->map(function ($subject) {
            return [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'subject_icon' => $subject->icon_image_url,
                'class_name' => $subject->class->name ?? null,
                'curriculum_name' => $subject->curriculum->name ?? null,
            ];
        });

        // Daftar Siswa yang Diajar
        $students = StudentPackage::where('tutor_user_id', $user->id)
            ->with(['student.student', 'subject', 'package'])
            ->get()
            ->map(function ($sp) {
                // Get student photo URL
                $photoUrl = $sp->student->profile_photo_url ?? null;
                
                // Convert relative path to full URL
                if ($photoUrl && !str_starts_with($photoUrl, 'http')) {
                    if (str_starts_with($photoUrl, 'storage/')) {
                        $photoUrl = url($photoUrl);
                    } else {
                        $photoUrl = url('storage/' . $photoUrl);
                    }
                }
                
                return [
                    'student_package_id' => $sp->id,
                    'student_user_id' => $sp->student_user_id,
                    'student_name' => $sp->student->name ?? null,
                    'student_photo' => $photoUrl,
                    'student_email' => $sp->student->email ?? null,
                    'student_phone' => $sp->student->telephone_number ?? null,
                    'student_class' => $sp->student->student->class->name ?? null,
                    'subject_name' => $sp->subject->name ?? null,
                    'package_name' => $sp->package->name ?? null,
                    'remaining_session' => $sp->remaining_session,
                    'total_session' => $sp->package->session ?? 0,
                    'progress_percentage' => $sp->package && $sp->package->session > 0 
                        ? round((($sp->package->session - $sp->remaining_session) / $sp->package->session) * 100, 2)
                        : 0,
                ];
            });

        // Statistik Siswa
        $studentStats = [
            'total_students' => $students->unique('student_user_id')->count(),
            'total_active_packages' => StudentPackage::where('tutor_user_id', $user->id)
                ->where('remaining_session', '>', 0)
                ->count(),
            'total_sessions_given' => StudentPackage::where('tutor_user_id', $user->id)
                ->get()
                ->sum(function ($sp) {
                    return ($sp->package->session ?? 0) - $sp->remaining_session;
                }),
        ];

        // Jadwal Mengajar
        $schedules = ScheduleTutor::where('user_id', $user->id)
            ->get()
            ->map(function ($schedule) {
                return [
                    'schedule_id' => $schedule->id,
                    'day' => $schedule->day,
                    'time' => $schedule->time,
                ];
            });

        // Jadwal yang Sudah Diambil Siswa
        $takenSchedules = TakenSchedule::whereHas('scheduleTutor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['student', 'subject'])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($ts) use ($user) {
                $photoUrl = $ts->student->profile_photo_url ?? null;
                
                // Convert relative path to full URL if needed
                if ($photoUrl && !str_starts_with($photoUrl, 'http')) {
                    if (str_starts_with($photoUrl, 'storage/')) {
                        $photoUrl = url($photoUrl);
                    } else {
                        $photoUrl = url('storage/' . $photoUrl);
                    }
                }
                
                // Check if this schedule has been accepted (has StudentPackage)
                $isAccepted = StudentPackage::where([
                    'student_user_id' => $ts->user_id,
                    'tutor_user_id' => $user->id,
                    'subject_id' => $ts->subject_id,
                ])->exists();
                
                return [
                    'id' => $ts->id,
                    'date' => $ts->date,
                    'status' => $ts->status,
                    'is_accepted' => $isAccepted,
                    'student_name' => $ts->student->name ?? null,
                    'student_photo' => $photoUrl,
                    'student_phone' => $ts->student->telephone_number ?? null,
                    'subject_name' => $ts->subject->name ?? null,
                    'schedule_time' => $ts->scheduleTutor->time ?? null,
                ];
            });

        // Statistik Jadwal
        $scheduleStats = [
            'total_schedules' => $takenSchedules->count(),
            'completed_schedules' => $takenSchedules->where('status', TakenScheduleStatusEnum::COMPLETED->value)->count(),
            'active_schedules' => $takenSchedules->where('status', TakenScheduleStatusEnum::ACTIVE->value)->count(),
            'cancelled_schedules' => $takenSchedules->where('status', TakenScheduleStatusEnum::CANCELLED->value)->count(),
        ];

        // Jadwal Mengajar Mendatang (7 hari)
        $upcomingSchedules = TakenSchedule::whereHas('scheduleTutor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('date', '>=', now()->toDateString())
            ->where('date', '<=', now()->addDays(7)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->with(['student', 'subject', 'scheduleTutor'])
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($ts) {
                return [
                    'id' => $ts->id,
                    'date' => $ts->date,
                    'status' => $ts->status,
                    'student_name' => $ts->student->name ?? null,
                    'subject_name' => $ts->subject->name ?? null,
                    'schedule_time' => $ts->scheduleTutor->time ?? null,
                ];
            });

        // Pendapatan
        $completedSessions = TakenSchedule::whereHas('scheduleTutor', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('status', TakenScheduleStatusEnum::COMPLETED->value)
            ->count();        $earnings = [
            'completed_sessions' => $completedSessions,
            'salary_per_session' => $tutor->salary,
            'estimated_total_earnings' => $completedSessions * ($tutor->salary ?? 0),
        ];

        // Review
        $reviews = Review::where('to_user_id', $user->id)
            ->with('fromUser')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rate' => $review->rate,
                    'quality' => $review->quality,
                    'delivery' => $review->delivery,
                    'attitude' => $review->attitude,
                    'benefit' => $review->benefit,
                    'review' => $review->review,
                    'from_user_name' => $review->fromUser->name ?? null,
                    'from_user_photo' => $review->fromUser->profile_photo_url ?? null,
                    'created_at' => $review->created_at,
                ];
            });

        $reviewStats = [
            'total_reviews' => $reviews->count(),
            'average_rating' => $reviews->avg('rate') ?? 0,
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile' => $profile,
                'subjects' => $subjects,
                'students' => $students,
                'student_stats' => $studentStats,
                'my_schedules' => $schedules,
                'taken_schedules' => $takenSchedules,
                'schedule_stats' => $scheduleStats,
                'upcoming_schedules' => $upcomingSchedules,
                'earnings' => $earnings,
                'reviews' => $reviews,
                'review_stats' => $reviewStats,
            ]
        ], 200);
    }

    /**
     * Get summary untuk widget dashboard
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        
        if (!$user->tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan tutor'
            ], 403);
        }

        $completedSessionsThisMonth = TakenSchedule::whereHas('scheduleTutor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', 'completed')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        $summary = [
            'total_students' => StudentPackage::where('tutor_user_id', $user->id)->distinct('student_user_id')->count(),
            'total_subjects' => $user->subjects->count(),
            'total_schedules_today' => TakenSchedule::whereHas('scheduleTutor', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('date', now()->toDateString())
                ->count(),
            'total_upcoming_schedules' => TakenSchedule::whereHas('scheduleTutor', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('date', '>=', now()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->count(),
            'completed_sessions_this_month' => $completedSessionsThisMonth,
            'estimated_earnings_this_month' => $completedSessionsThisMonth * ($user->tutor->salary ?? 0),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $summary
        ], 200);
    }

    /**
     * Accept learning request (taken_schedule)
     */
    public function acceptRequest(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan tutor'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Find taken_schedule and verify it belongs to this tutor
            $takenSchedule = TakenSchedule::with(['scheduleTutor', 'subject'])
                ->whereHas('scheduleTutor', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->findOrFail($id);

            // Check if already processed
            if ($takenSchedule->status !== TakenScheduleStatusEnum::ACTIVE) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ajuan sudah diproses sebelumnya'
                ], 400);
            }

            // Create or update StudentPackage
            // Check if student already has package with this tutor and subject
            $studentPackage = StudentPackage::where([
                'student_user_id' => $takenSchedule->user_id,
                'tutor_user_id' => $user->id,
                'subject_id' => $takenSchedule->subject_id,
            ])->first();

            if (!$studentPackage) {
                // Create new StudentPackage
                // For instant bookings, package_id can be null
                $studentPackage = StudentPackage::create([
                    'package_id' => null, // Null for instant booking (single session)
                    'student_user_id' => $takenSchedule->user_id,
                    'subject_id' => $takenSchedule->subject_id,
                    'tutor_user_id' => $user->id,
                    'remaining_session' => 1, // Start with 1 session for instant booking
                ]);
            }
            // If package already exists, no need to update remaining_session here
            // It will be decremented when the session is completed

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ajuan belajar diterima',
                'data' => [
                    'id' => $takenSchedule->id,
                    'status' => $takenSchedule->status,
                    'student_package_created' => $studentPackage->wasRecentlyCreated,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menerima ajuan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject learning request (taken_schedule)
     */
    public function rejectRequest(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user->tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan tutor'
            ], 403);
        }

        // Find taken_schedule and verify it belongs to this tutor
        $takenSchedule = TakenSchedule::with('scheduleTutor')
            ->whereHas('scheduleTutor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->findOrFail($id);

        // Check if already processed
        if ($takenSchedule->status !== TakenScheduleStatusEnum::ACTIVE) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ajuan sudah diproses sebelumnya'
            ], 400);
        }

        // Update status to cancelled
        $takenSchedule->update([
            'status' => TakenScheduleStatusEnum::CANCELLED
        ]);

        // TODO: Refund payment to student if needed
        // TODO: Send notification to student

        return response()->json([
            'status' => 'success',
            'message' => 'Ajuan belajar ditolak',
            'data' => [
                'id' => $takenSchedule->id,
                'status' => $takenSchedule->status,
            ]
        ], 200);
    }

    /**
     * Get student detail from taken_schedule
     */
    public function getStudentDetail(Request $request, $takenScheduleId)
    {
        $user = $request->user();
        
        if (!$user->tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan tutor'
            ], 403);
        }

        // Find taken_schedule and verify it belongs to this tutor
        $takenSchedule = TakenSchedule::with(['student.student.class', 'subject', 'scheduleTutor'])
            ->whereHas('scheduleTutor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->findOrFail($takenScheduleId);

        $studentUser = $takenSchedule->student;
        $studentProfile = $studentUser->student;

        $photoUrl = $studentUser->profile_photo_url ?? null;
        if ($photoUrl && !str_starts_with($photoUrl, 'http')) {
            if (str_starts_with($photoUrl, 'storage/')) {
                $photoUrl = url($photoUrl);
            } else {
                $photoUrl = url('storage/' . $photoUrl);
            }
        }

        $studentDetail = [
            'id' => $takenSchedule->id,
            'student_user_id' => $studentUser->id,
            'nama' => $studentUser->name,
            'email' => $studentUser->email,
            'jenisKelamin' => $studentUser->gender ?? 'N/A',
            'tanggalLahir' => $studentUser->date_of_birth ? $studentUser->date_of_birth->format('d-m-Y') : 'N/A',
            'telepon' => $studentUser->telephone_number ?? 'N/A',
            'agama' => $studentUser->religion ?? 'N/A',
            'photo' => $photoUrl,
            'alamat' => [
                'detail' => is_array($studentUser->home_address) 
                    ? ($studentUser->home_address['street'] ?? $studentUser->home_address['address'] ?? 'N/A')
                    : ($studentUser->home_address ?? 'N/A'),
                'latitude' => $studentUser->latitude,
                'longitude' => $studentUser->longitude,
            ],
            'sekolah' => [
                'nama' => $studentProfile->school ?? 'N/A',
                'kelas' => $studentProfile->class->name ?? 'N/A',
            ],
            'namaOrangTua' => $studentProfile->parent ?? 'N/A',
            'teleponOrangTua' => $studentProfile->parent_telephone_number ?? 'N/A',
            'subject' => $takenSchedule->subject->name ?? 'N/A',
            'date' => $takenSchedule->date,
            'time' => $takenSchedule->scheduleTutor->time ?? 'N/A',
            'status' => $takenSchedule->status,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $studentDetail
        ], 200);
    }

    /**
     * Get student attendance data (untuk halaman absensi)
     */
    public function getStudentAttendance(Request $request, $studentUserId)
    {
        $user = $request->user();
        
        // Cek apakah user adalah tutor
        if (!$user->tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan tutor'
            ], 403);
        }

        // Cari StudentPackage untuk student ini
        $studentPackage = StudentPackage::where('tutor_user_id', $user->id)
            ->where('student_user_id', $studentUserId)
            ->with(['student.student.class', 'subject', 'package', 'sessionReports'])
            ->first();

        if (!$studentPackage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Student tidak ditemukan'
            ], 404);
        }

        $studentUser = $studentPackage->student;
        
        // Convert photo URL
        $photoUrl = $studentUser->profile_photo_url ?? null;
        if ($photoUrl && !str_starts_with($photoUrl, 'http')) {
            if (str_starts_with($photoUrl, 'storage/')) {
                $photoUrl = url($photoUrl);
            } else {
                $photoUrl = url('storage/' . $photoUrl);
            }
        }

        // Get all taken schedules for this student-tutor-subject combination
        $takenSchedules = TakenSchedule::whereHas('scheduleTutor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('user_id', $studentUserId)
            ->where('subject_id', $studentPackage->subject_id)
            ->where('status', TakenScheduleStatusEnum::ACTIVE)
            ->orderBy('date', 'asc')
            ->get();

        // Calculate total sessions from package or count of schedules
        $totalSessions = $studentPackage->package 
            ? $studentPackage->package->session 
            : $takenSchedules->count();
        
        // Initialize remaining_session if null (set to total sessions)
        if ($studentPackage->remaining_session === null) {
            $studentPackage->remaining_session = $totalSessions;
            $studentPackage->save();
        }
        
        $completedSessions = $totalSessions - $studentPackage->remaining_session;
        $progress = $totalSessions > 0 
            ? round(($completedSessions / $totalSessions) * 100, 2) 
            : 0;

        // Get session reports
        $sessionReports = $studentPackage->sessionReports->map(function ($report) {
            $documentUrl = null;
            if ($report->document_path) {
                $documentUrl = str_starts_with($report->document_path, 'http') 
                    ? $report->document_path 
                    : url('storage/' . $report->document_path);
            }
            
            return [
                'id' => $report->id,
                'session_number' => $report->session_number,
                'material' => $report->material,
                'score' => $report->score,
                'review' => $report->review,
                'document_url' => $documentUrl,
                'session_date' => $report->session_date->format('Y-m-d'),
            ];
        });

        // Get schedule dates with sessions and meeting links
        $scheduleDates = $takenSchedules->map(function ($ts) {
            return [
                'schedule_id' => $ts->id,
                'date' => $ts->date,
                'time' => $ts->scheduleTutor->time ?? 'N/A',
                'course_mode' => $ts->course_mode,
                'meeting_link' => $ts->meeting_link,
                'meeting_link_sent' => $ts->meeting_link_sent,
            ];
        });

        $attendanceData = [
            'student_package_id' => $studentPackage->id,
            'student' => [
                'id' => $studentUser->id,
                'name' => $studentUser->name,
                'photo' => $photoUrl ?: "https://ui-avatars.com/api/?name=" . urlencode($studentUser->name) . "&size=80&background=41a6c2&color=fff",
                'subject' => $studentPackage->subject->name ?? 'N/A',
            ],
            'sessions' => [
                'total' => $totalSessions,
                'completed' => $completedSessions,
                'remaining' => $studentPackage->remaining_session,
                'progress' => $progress,
            ],
            'schedule_dates' => $scheduleDates,
            'session_reports' => $sessionReports,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $attendanceData
        ], 200);
    }

    /**
     * Save or update session report
     */
    public function saveSessionReport(Request $request)
    {
        $user = $request->user();
        
        // Cek apakah user adalah tutor
        if (!$user->tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan tutor'
            ], 403);
        }

        $validated = $request->validate([
            'student_package_id' => 'required|exists:student_packages,id',
            'session_number' => 'required|integer|min:1',
            'material' => 'nullable|string|max:255',
            'score' => 'nullable|string|max:100',
            'review' => 'nullable|string',
            'session_date' => 'required|date',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        // Verify student package belongs to this tutor
        $studentPackage = StudentPackage::where('id', $validated['student_package_id'])
            ->where('tutor_user_id', $user->id)
            ->first();

        if (!$studentPackage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Student package tidak ditemukan'
            ], 404);
        }

        // Get or create taken_schedule for this session (use first active schedule as reference)
        $takenSchedule = TakenSchedule::whereHas('scheduleTutor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('user_id', $studentPackage->student_user_id)
            ->where('subject_id', $studentPackage->subject_id)
            ->where('status', TakenScheduleStatusEnum::ACTIVE)
            ->first();

        if (!$takenSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada jadwal aktif ditemukan'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Handle document upload
            $documentPath = null;
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $documentPath = $file->store('session_reports', 'public');
            }

            // Check if report already exists for this session
            $sessionReport = SessionReport::where('student_package_id', $validated['student_package_id'])
                ->where('session_number', $validated['session_number'])
                ->first();

            if ($sessionReport) {
                // Update existing report
                if ($documentPath && $sessionReport->document_path) {
                    // Delete old document
                    Storage::disk('public')->delete($sessionReport->document_path);
                }
                
                $sessionReport->update([
                    'material' => $validated['material'],
                    'score' => $validated['score'],
                    'review' => $validated['review'],
                    'session_date' => $validated['session_date'],
                    'document_path' => $documentPath ?: $sessionReport->document_path,
                ]);
            } else {
                // Create new report
                $sessionReport = SessionReport::create([
                    'student_package_id' => $validated['student_package_id'],
                    'taken_schedule_id' => $takenSchedule->id,
                    'session_number' => $validated['session_number'],
                    'material' => $validated['material'],
                    'score' => $validated['score'],
                    'review' => $validated['review'],
                    'session_date' => $validated['session_date'],
                    'document_path' => $documentPath,
                ]);
                
                // Update remaining_session (decrement by 1 when new session is completed)
                if ($studentPackage->remaining_session > 0) {
                    $studentPackage->decrement('remaining_session');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Session report berhasil disimpan',
                'data' => $sessionReport
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete uploaded file if transaction failed
            if ($documentPath) {
                Storage::disk('public')->delete($documentPath);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan session report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send meeting link untuk kelas online
     */
    public function sendMeetingLink(Request $request, $studentUserId)
    {
        $user = $request->user();
        
        if (!$user->tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'User bukan tutor'
            ], 403);
        }

        $validated = $request->validate([
            'meeting_link' => 'required|url',
            'schedule_id' => 'required|exists:taken_schedules,id',
        ]);

        // Verify schedule belongs to this tutor's student
        $schedule = TakenSchedule::with('scheduleTutor')
            ->where('id', $validated['schedule_id'])
            ->where('user_id', $studentUserId)
            ->whereHas('scheduleTutor', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $schedule->update([
                'meeting_link' => $validated['meeting_link'],
                'meeting_link_sent' => true,
                'meeting_link_sent_at' => now(),
            ]);

            // TODO: Create notification for student
            // Notification::create([
            //     'user_id' => $studentUserId,
            //     'title' => 'Link Kelas Sudah Dikirim!',
            //     'message' => 'Tutor telah mengirim link kelas online. Klik untuk bergabung.',
            //     'type' => 'meeting_link',
            //     'data' => json_encode(['schedule_id' => $schedule->id]),
            // ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Meeting link berhasil dikirim',
                'data' => [
                    'meeting_link' => $schedule->meeting_link,
                    'sent_at' => $schedule->meeting_link_sent_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim meeting link: ' . $e->getMessage()
            ], 500);
        }
    }
}
