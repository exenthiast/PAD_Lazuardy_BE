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

            // Create booking
            $booking = Booking::create([
                'user_id' => $userId,
                'tutor_id' => $request->tutor_id,
                'schedule_time' => $request->schedule_time,
                'price' => $request->price,
                'status' => 'unpaid',
            ]);

            // Generate payment URL (simulasi)
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $paymentUrl = $frontendUrl . '/payment/simulation/' . $booking->id;
            
            // Update booking dengan payment URL
            $booking->update([
                'payment_url' => $paymentUrl,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Booking created successfully',
                'booking_id' => $booking->id,
                'payment_url' => $paymentUrl,
            ], 201);

        } catch (\Exception $e) {
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
