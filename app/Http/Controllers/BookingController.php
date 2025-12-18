<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\ScheduleTutor;
use App\Models\TakenSchedule;
use App\Enums\TakenScheduleStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Store a new booking (Instant Booking - Simulasi).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'tutor_id' => 'required|exists:users,id',
            'schedule_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'price' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get authenticated user (student)
        $user = auth()->user();

        // Jika tidak ada auth (untuk testing), gunakan ID 1
        if (!$user) {
            $user = User::find(1); // Default user untuk testing
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }
        }

        // Validasi bahwa tutor_id adalah tutor
        $tutor = User::find($request->tutor_id);
        if (!$tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tutor not found',
            ], 404);
        }

        DB::beginTransaction();
        
        try {
            // Generate payment token
            $paymentToken = 'PKG-' . time() . '-' . strtoupper(Str::random(6));

            // Buat booking dengan status 'paid' karena siswa sudah punya paket aktif
            $booking = Booking::create([
                'user_id' => $user->id,
                'tutor_id' => $request->tutor_id,
                'schedule_time' => $request->schedule_time,
                'price' => $request->price,
                'status' => 'paid', // Langsung paid karena dari paket
                'payment_token' => $paymentToken,
                'payment_url' => null,
            ]);

            // Parse schedule_time untuk mendapatkan date dan time
            $scheduleDateTime = Carbon::parse($request->schedule_time);
            $date = $scheduleDateTime->format('Y-m-d');
            $time = $scheduleDateTime->format('H:i:s');
            
            // Cari atau buat schedule_tutor untuk tutor ini
            $scheduleTutor = ScheduleTutor::firstOrCreate([
                'user_id' => $request->tutor_id,
                'day' => $scheduleDateTime->dayOfWeekIso, // 1-7 (Monday-Sunday)
                'time' => $time,
            ]);

            // Ambil subject_id: prioritas dari StudentPackage siswa yang aktif
            $subjectId = null;
            
            // Cek 1: StudentPackage siswa dengan sisa sesi > 0
            $studentPackage = DB::table('student_packages')
                ->where('student_user_id', $user->id)
                ->where('remaining_session', '>', 0)
                ->whereNotNull('subject_id')
                ->first();

            if ($studentPackage && $studentPackage->subject_id) {
                $subjectId = $studentPackage->subject_id;
            }
            
            // Fallback 1: Ambil subject_id dari tutor_subjects
            if (!$subjectId) {
                $tutorSubject = DB::table('tutor_subjects')
                    ->where('user_id', $request->tutor_id)
                    ->first();
                $subjectId = $tutorSubject ? $tutorSubject->subject_id : null;
            }
            
            // Fallback 2: Ambil subject_id pertama dari tabel subjects
            if (!$subjectId) {
                $defaultSubject = DB::table('subjects')->first();
                $subjectId = $defaultSubject ? $defaultSubject->id : null;
            }

            if (!$subjectId) {
                throw new \Exception('Tidak dapat menentukan mata pelajaran untuk booking ini. Pastikan tutor memiliki mata pelajaran atau siswa memiliki paket aktif.');
            }

            // Langsung buat TakenSchedule karena siswa sudah punya paket aktif
            $takenSchedule = TakenSchedule::create([
                'user_id' => $user->id, // Student ID
                'schedule_tutor_id' => $scheduleTutor->id,
                'subject_id' => $subjectId,
                'date' => $date,
                'status' => TakenScheduleStatusEnum::ACTIVE->value, // Status aktif
            ]);

            DB::commit();

            // Return response tanpa payment URL
            return response()->json([
                'status' => 'success',
                'message' => 'Booking created',
                'booking_id' => $booking->id,
                'taken_schedule_id' => $takenSchedule->id,
                'data' => [
                    'booking_id' => $booking->id,
                    'taken_schedule_id' => $takenSchedule->id,
                    'date' => $takenSchedule->date,
                    'time' => $time,
                    'status' => $takenSchedule->status,
                    'tutor_id' => $request->tutor_id,
                    'subject_id' => $subjectId,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dummy confirm payment (untuk simulasi).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function dummyConfirm($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }

        // Cek apakah sudah paid
        if ($booking->status === 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking sudah lunas',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Update status menjadi paid
            $booking->update(['status' => 'paid']);

            // Parse schedule time
            $scheduleDateTime = Carbon::parse($booking->schedule_time);
            $date = $scheduleDateTime->format('Y-m-d');
            $time = $scheduleDateTime->format('H:i:s');
            
            // Get day name in Indonesian
            $dayMapping = [
                'Sunday' => 'minggu',
                'Monday' => 'senin',
                'Tuesday' => 'selasa',
                'Wednesday' => 'rabu',
                'Thursday' => 'kamis',
                'Friday' => 'jumat',
                'Saturday' => 'sabtu',
            ];
            $dayName = $dayMapping[$scheduleDateTime->format('l')];

            // Cari atau buat schedule_tutor untuk tutor ini
            $scheduleTutor = ScheduleTutor::firstOrCreate([
                'user_id' => $booking->tutor_id,
                'day' => $dayName,
                'time' => $time,
            ]);

            // Ambil subject berdasarkan kelas student
            $student = DB::table('students')->where('user_id', $booking->user_id)->first();
            $subjectId = null;

            if ($student && $student->class_id) {
                // Cari subject yang sesuai dengan class_id student dan yang diajarkan oleh tutor
                $matchingSubject = DB::table('subjects')
                    ->join('tutor_subjects', 'subjects.id', '=', 'tutor_subjects.subject_id')
                    ->where('subjects.class_id', $student->class_id)
                    ->where('tutor_subjects.user_id', $booking->tutor_id)
                    ->select('subjects.id')
                    ->first();

                if ($matchingSubject) {
                    $subjectId = $matchingSubject->id;
                } else {
                    // Jika tidak ada subject matching, ambil subject apapun dari tutor dengan class_id yang sama
                    $subjectByClass = DB::table('subjects')
                        ->where('class_id', $student->class_id)
                        ->first();
                    
                    if ($subjectByClass) {
                        $subjectId = $subjectByClass->id;
                    }
                }
            }

            // Fallback: Jika masih belum dapat subject, ambil dari tutor_subjects
            if (!$subjectId) {
                $tutorSubject = DB::table('tutor_subjects')
                    ->where('user_id', $booking->tutor_id)
                    ->first();

                if ($tutorSubject) {
                    $subjectId = $tutorSubject->subject_id;
                } else {
                    // Last fallback: ambil subject pertama
                    $anySubject = DB::table('subjects')->first();
                    
                    if (!$anySubject) {
                        throw new \Exception('Tidak ada mata pelajaran yang tersedia di sistem. Silakan hubungi administrator.');
                    }
                    
                    $subjectId = $anySubject->id;
                }
            }

            // Buat 4 taken_schedule (1 booking = 4 pertemuan di 4 minggu berturut-turut)
            $takenSchedules = [];
            
            for ($week = 0; $week < 4; $week++) {
                // Hitung tanggal untuk setiap minggu
                $scheduleDate = (new \DateTime($date))->modify("+{$week} weeks");
                
                $takenSchedule = TakenSchedule::create([
                    'user_id' => $booking->user_id, // Student ID
                    'schedule_tutor_id' => $scheduleTutor->id,
                    'subject_id' => $subjectId,
                    'date' => $scheduleDate->format('Y-m-d'),
                    'status' => TakenScheduleStatusEnum::ACTIVE, // Status aktif karena sudah dibayar
                ]);
                
                $takenSchedules[] = [
                    'id' => $takenSchedule->id,
                    'date' => $takenSchedule->date,
                    'week' => $week + 1,
                ];
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran berhasil dikonfirmasi dan 4 jadwal pertemuan telah dibuat',
                'data' => [
                    'booking_id' => $booking->id,
                    'status' => $booking->status,
                    'schedule_time' => $booking->schedule_time,
                    'price' => $booking->price,
                    'taken_schedules' => $takenSchedules,
                    'total_meetings' => count($takenSchedules),
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal konfirmasi pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get booking by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $booking = Booking::with(['user', 'tutor'])->find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found',
            ], 404);
        }

        // For payment simulation, allow access without strict auth check
        // In production, you should verify the user owns this booking

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $booking->id,
                'student_name' => $booking->user->name,
                'tutor_name' => $booking->tutor->name,
                'schedule_time' => $booking->schedule_time,
                'price' => $booking->price,
                'status' => $booking->status,
                'payment_url' => $booking->payment_url,
                'payment_token' => $booking->payment_token,
                'created_at' => $booking->created_at,
            ],
        ], 200);
    }

    /**
     * Get all bookings for authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get bookings as student or tutor
        $bookings = Booking::with(['user', 'tutor'])
            ->where('user_id', $user->id)
            ->orWhere('tutor_id', $user->id)
            ->orderBy('schedule_time', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'student_name' => $booking->user->name,
                    'tutor_name' => $booking->tutor->name,
                    'schedule_time' => $booking->schedule_time,
                    'price' => $booking->price,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                ];
            }),
        ], 200);
    }

    /**
     * Update booking status (untuk simulasi pembayaran).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:unpaid,paid,completed,cancelled,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found',
            ], 404);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Booking status updated',
            'data' => [
                'id' => $booking->id,
                'status' => $booking->status,
            ],
        ], 200);
    }
}
