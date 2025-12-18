<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * Store a newly created booking
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
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get authenticated user ID (student)
            $userId = Auth::id();
            
            // If not authenticated, return error
            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            \DB::beginTransaction();

            // Create booking
            $booking = Booking::create([
                'user_id' => $userId,
                'tutor_id' => $request->tutor_id,
                'schedule_time' => $request->schedule_time,
                'price' => $request->price,
                'status' => 'paid', // Status langsung paid karena siswa sudah punya paket aktif
                'payment_token' => 'PKG-' . strtoupper(uniqid()), // Token dari paket
            ]);

            // Parse schedule_time untuk mendapatkan date dan time
            $scheduleDateTime = \Carbon\Carbon::parse($request->schedule_time);
            $date = $scheduleDateTime->format('Y-m-d');
            $time = $scheduleDateTime->format('H:i:s');
            
            // Cari atau buat schedule_tutor untuk tutor ini
            $scheduleTutor = \App\Models\ScheduleTutor::firstOrCreate([
                'user_id' => $request->tutor_id,
                'day' => $scheduleDateTime->dayOfWeekIso, // 1-7 (Monday-Sunday)
                'time' => $time,
            ]);

            // Ambil subject_id dari StudentPackage atau user subjects
            $studentPackage = \App\Models\StudentPackage::where('student_user_id', $userId)
                ->where('tutor_user_id', $request->tutor_id)
                ->where('remaining_session', '>', 0)
                ->first();

            $subjectId = null;
            if ($studentPackage) {
                $subjectId = $studentPackage->subject_id;
            } else {
                // Fallback: ambil subject pertama dari tutor
                $tutorSubject = \DB::table('user_subjects')
                    ->where('user_id', $request->tutor_id)
                    ->first();
                $subjectId = $tutorSubject ? $tutorSubject->subject_id : null;
            }

            if (!$subjectId) {
                throw new \Exception('Tidak dapat menentukan mata pelajaran untuk booking ini');
            }

            // Langsung buat TakenSchedule karena siswa sudah punya paket aktif
            $takenSchedule = \App\Models\TakenSchedule::create([
                'user_id' => $userId, // Student ID
                'schedule_tutor_id' => $scheduleTutor->id,
                'subject_id' => $subjectId,
                'date' => $date,
                'status' => 'active', // Status aktif karena sudah dibayar via paket
            ]);

            \DB::commit();

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
            \DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show booking details
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $booking = Booking::with(['user', 'tutor.tutor'])->findOrFail($id);

            // Get tutor name
            $tutorName = $booking->tutor->name ?? 'N/A';

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'tutor_id' => $booking->tutor_id,
                    'tutor_name' => $tutorName,
                    'schedule_time' => $booking->schedule_time->format('Y-m-d H:i:s'),
                    'price' => $booking->price,
                    'status' => $booking->status,
                    'payment_url' => $booking->payment_url,
                    'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Confirm payment (Dummy/Simulation)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function dummyConfirm($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Check if already paid
            if ($booking->status === 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Booking already paid'
                ], 400);
            }

            // Update status to paid
            $booking->update([
                'status' => 'paid',
                'payment_token' => 'DUMMY-' . strtoupper(uniqid()),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran berhasil dikonfirmasi',
                'data' => [
                    'booking_id' => $booking->id,
                    'status' => $booking->status,
                    'payment_token' => $booking->payment_token,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
