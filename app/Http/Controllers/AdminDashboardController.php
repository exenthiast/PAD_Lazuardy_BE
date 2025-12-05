<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use App\Models\Tutor;
use App\Models\Student;
use App\Models\Review;
use App\Enums\RoleEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\TutorStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\TutorApprovedMail;
use App\Mail\TutorRejectedMail;
use App\Mail\PaymentVerifiedMail;
use App\Mail\PaymentRejectedMail;

class AdminDashboardController extends Controller
{
    /**
     * Get admin dashboard statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        try {
            // Total Students
            $totalStudents = User::where('role', RoleEnum::STUDENT)->count();
            
            // Total Tutors
            $totalTutors = User::where('role', RoleEnum::TUTOR)->count();
            
            // Monthly Transactions (current month)
            $monthlyTransactions = Payment::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->where('status', PaymentStatusEnum::VALIDATED)
                ->count();
            
            // Average Rating
            $averageRating = Review::avg('rate');
            $averageRating = $averageRating ? round($averageRating, 1) : 0;
            
            return response()->json([
                'totalStudents' => $totalStudents,
                'totalTutors' => $totalTutors,
                'monthlyTransactions' => $monthlyTransactions,
                'averageRating' => $averageRating,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil statistik dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get pending tutor verifications
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingTutors(Request $request)
    {
        try {
            $tutors = Tutor::with(['user'])
                ->where('status', TutorStatusEnum::VERIFY)
                ->get()
                ->map(function ($tutor) {
                    return [
                        'id' => $tutor->user_id,
                        'name' => $tutor->user->name ?? 'N/A',
                        'subject' => $tutor->keahlian ?? 'N/A',
                        'status' => $tutor->status === TutorStatusEnum::VERIFY ? 'Menunggu' : ($tutor->status ? $tutor->status->displayName() : 'N/A'),
                        'created_at' => $tutor->created_at,
                    ];
                });
            
            return response()->json([
                'data' => $tutors,
                'total' => $tutors->count(),
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get pending payment verifications
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingPayments(Request $request)
    {
        try {
            $payments = Payment::with(['order.user', 'order.package'])
                ->whereIn('status', [PaymentStatusEnum::PENDING, PaymentStatusEnum::UPLOADED])
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'studentName' => $payment->order?->user?->name ?? 'N/A',
                        'package' => $payment->order?->package?->name ?? 'N/A',
                        'amount' => $payment->amount,
                        'status' => $payment->status ? $payment->status->displayName() : 'N/A',
                        'created_at' => $payment->created_at,
                    ];
                });
            
            return response()->json([
                'data' => $payments,
                'total' => $payments->count(),
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Approve tutor
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveTutor(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);
            
            $tutor = Tutor::where('user_id', $request->user_id)->firstOrFail();
            $tutor->status = TutorStatusEnum::ACTIVE;
            $tutor->save();
            
            // Send notification to tutor
            $user = $tutor->user;
            $user->notify(new \App\Notifications\TutorStatusNotification(
                'Selamat! Akun Anda Disetujui',
                'Akun tutor Anda telah disetujui oleh admin. Anda sekarang dapat mulai mengajar dan menerima siswa.',
                'approved'
            ));
            
            // Send email notification
            Mail::to($user->email)->send(new TutorApprovedMail($tutor));
            
            return response()->json([
                'message' => 'Tutor berhasil disetujui',
                'tutor' => $tutor,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyetujui tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reject tutor
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectTutor(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);
            
            $tutor = Tutor::where('user_id', $request->user_id)->firstOrFail();
            $tutor->status = TutorStatusEnum::REJECTED;
            $tutor->save();
            
            // Send notification to tutor
            $user = $tutor->user;
            $user->notify(new \App\Notifications\TutorStatusNotification(
                'Pendaftaran Tutor Ditolak',
                'Maaf, pendaftaran Anda sebagai tutor ditolak oleh admin. Silakan hubungi admin untuk informasi lebih lanjut.',
                'rejected'
            ));
            
            // Send email notification
            Mail::to($user->email)->send(new TutorRejectedMail($tutor));
            
            return response()->json([
                'message' => 'Tutor berhasil ditolak',
                'tutor' => $tutor,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menolak tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verify payment
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        try {
            $request->validate([
                'payment_id' => 'required|exists:payments,id',
            ]);
            
            $payment = Payment::with(['order.user', 'order.package'])->findOrFail($request->payment_id);
            $payment->status = PaymentStatusEnum::VALIDATED;
            $payment->save();
            
            // Send email notification
            if ($payment->order && $payment->order->user && $payment->order->user->email) {
                Mail::to($payment->order->user->email)->send(new PaymentVerifiedMail($payment));
            }
            
            return response()->json([
                'message' => 'Pembayaran berhasil diverifikasi',
                'payment' => $payment,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memverifikasi pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reject payment
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectPayment(Request $request)
    {
        try {
            $request->validate([
                'payment_id' => 'required|exists:payments,id',
            ]);
            
            $payment = Payment::with(['order.user', 'order.package'])->findOrFail($request->payment_id);
            $payment->status = PaymentStatusEnum::REJECTED;
            $payment->save();
            
            // Send email notification
            if ($payment->order && $payment->order->user && $payment->order->user->email) {
                Mail::to($payment->order->user->email)->send(new PaymentRejectedMail($payment));
            }
            
            return response()->json([
                'message' => 'Pembayaran berhasil ditolak',
                'payment' => $payment,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menolak pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get payment detail by ID
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentDetail(Request $request, $id)
    {
        try {
            $payment = Payment::with(['order.user.student', 'order.package'])
                ->findOrFail($id);
            
            $user = $payment->order->user;
            $student = $user->student;
            
            // Parse home_address JSON
            $homeAddress = null;
            if ($user->home_address) {
                $homeAddress = is_string($user->home_address) 
                    ? json_decode($user->home_address, true) 
                    : $user->home_address;
            }
            
            // Get class name if exists
            $className = 'N/A';
            if ($student && $student->class_id) {
                $class = \App\Models\ClassModel::find($student->class_id);
                $className = $class ? $class->name : 'N/A';
            }
            
            return response()->json([
                'payment' => [
                    'id' => $payment->id,
                    'package_name' => $payment->order->package->name ?? 'N/A',
                    'amount' => $payment->amount,
                    'status' => $payment->status ? $payment->status->displayName() : 'N/A',
                    'payment_method' => $payment->payment_method ? $payment->payment_method->value : 'N/A',
                    'proof_url' => $payment->proof_image_url ? asset('storage/' . $payment->proof_image_url) : null,
                    'created_at' => $payment->created_at,
                ],
                'student' => [
                    'nama_lengkap' => $user->name ?? 'N/A',
                    'email' => $user->email ?? 'N/A',
                    'jenis_kelamin' => $user->gender ? $user->gender->displayName() : 'N/A',
                    'tanggal_lahir' => $user->date_of_birth ?? 'N/A',
                    'no_telepon' => $user->telephone_number ?? 'N/A',
                    'agama' => $user->religion ? \App\Enums\ReligionEnum::displayName($user->religion->value) : 'N/A',
                    'provinsi' => $homeAddress['province'] ?? 'N/A',
                    'kota' => $homeAddress['regency'] ?? 'N/A',
                    'kecamatan' => $homeAddress['district'] ?? 'N/A',
                    'desa' => $homeAddress['subdistrict'] ?? 'N/A',
                    'alamat_lengkap' => $homeAddress['street'] ?? 'N/A',
                    'jenjang' => $student->school ?? 'N/A',
                    'kelas' => $className,
                    'nama_orang_tua' => $student->parent ?? 'N/A',
                    'nomor_orang_tua' => $student->parent_telephone_number ?? 'N/A',
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil detail pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get tutor detail by user_id
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTutorDetail(Request $request, $userId)
    {
        try {
            $tutor = Tutor::with(['user', 'subjects'])
                ->where('user_id', $userId)
                ->firstOrFail();
            
            $user = $tutor->user;
            
            // Parse home_address JSON if exists
            $homeAddress = null;
            if ($user->home_address) {
                $homeAddress = is_string($user->home_address) 
                    ? json_decode($user->home_address, true) 
                    : $user->home_address;
            }
            
            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'namaLengkap' => $user->name,
                    'email' => $user->email,
                    'photo' => $user->profile_photo_url ? url('storage/' . $user->profile_photo_url) : null,
                    'jenisKelamin' => $user->gender ? ucfirst($user->gender) : 'N/A',
                    'tanggalLahir' => $user->date_of_birth ?? 'N/A',
                    'noTelepon' => $user->telephone_number ?? 'N/A',
                    'agama' => $user->religion ? ucfirst($user->religion) : 'N/A',
                    'provinsi' => $homeAddress['province'] ?? 'N/A',
                    'kota' => $homeAddress['city'] ?? 'N/A',
                    'kecamatan' => $homeAddress['district'] ?? 'N/A',
                    'desa' => $homeAddress['village'] ?? 'N/A',
                    'alamatLengkap' => $homeAddress['address'] ?? 'N/A',
                    'latitude' => $user->latitude ?? null,
                    'longitude' => $user->longitude ?? null,
                    'keahlian' => $tutor->keahlian ?? ($tutor->subjects->pluck('name')->join(', ') ?: 'N/A'),
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
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil detail tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
