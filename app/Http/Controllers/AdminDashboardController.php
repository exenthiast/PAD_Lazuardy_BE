<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use App\Models\Tutor;
use App\Models\Student;
use App\Models\Review;
use App\Models\Subject;
use App\Models\TutorSalary;
use App\Models\StudentPackage;
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
        DB::beginTransaction();
        try {
            $request->validate([
                'payment_id' => 'required|exists:payments,id',
            ]);
            
            $payment = Payment::with(['order.user', 'order.package'])->findOrFail($request->payment_id);
            
            \Log::info('Verifying payment', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'user_id' => $payment->order->user_id,
                'package_id' => $payment->order->package_id,
            ]);
            
            $payment->status = PaymentStatusEnum::VALIDATED;
            $payment->save();
            
            // ===== ACTIVATE PACKAGE =====
            $package = $payment->order->package;
            
            // Calculate expiry based on package name (6 or 12 months)
            $packageName = strtolower($package->name ?? '');
            $months = 6; // Default 6 months
            
            if (strpos($packageName, '12') !== false || strpos($packageName, 'dua belas') !== false || strpos($packageName, 'tahun') !== false) {
                $months = 12;
            } elseif (strpos($packageName, '6') !== false || strpos($packageName, 'enam') !== false) {
                $months = 6;
            }
            
            $startDate = now()->toDateString();
            $expiredAt = now()->addMonths($months)->toDateString();
            $remainingSessions = $package->session ?? 24;
            
            // UpdateOrCreate student package
            $studentPackage = StudentPackage::updateOrCreate(
                [
                    'student_user_id' => $payment->order->user_id,
                    'package_id' => $payment->order->package_id,
                ],
                [
                    'status' => 'approved',
                    'start_date' => $startDate,
                    'expired_at' => $expiredAt,
                    'remaining_session' => $remainingSessions,
                    'subject_id' => $studentPackage->subject_id ?? 1,
                    'tutor_user_id' => $studentPackage->tutor_user_id ?? null,
                ]
            );
            
            \Log::info('Package activated', [
                'student_package_id' => $studentPackage->id,
                'status' => $studentPackage->status,
                'start_date' => $studentPackage->start_date,
                'expired_at' => $studentPackage->expired_at,
                'remaining_session' => $studentPackage->remaining_session,
            ]);
            // ===== END ACTIVATE PACKAGE =====
            
            DB::commit();
            
            // Send email notification (after commit)
            try {
                if ($payment->order && $payment->order->user && $payment->order->user->email) {
                    $student = $payment->order->user;
                    Mail::to($student->email)->send(new PaymentVerifiedMail($payment, $student));
                }
            } catch (\Exception $mailError) {
                \Log::error('Failed to send verification email: ' . $mailError->getMessage());
                // Don't fail the whole process if email fails
            }
            
            return response()->json([
                'message' => 'Pembayaran berhasil diverifikasi dan paket telah diaktifkan',
                'payment' => $payment->fresh(),
                'package_activated' => true,
                'student_package' => [
                    'id' => $studentPackage->id,
                    'status' => $studentPackage->status,
                    'start_date' => $studentPackage->start_date,
                    'expired_at' => $studentPackage->expired_at,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment verification error: ' . $e->getMessage(), [
                'payment_id' => $request->payment_id,
                'trace' => $e->getTraceAsString(),
            ]);
            
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
                $student = $payment->order->user;
                Mail::to($student->email)->send(new PaymentRejectedMail($payment, $student));
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
            $tutor = Tutor::with(['user', 'subjects', 'user.schedules'])
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
            
            // Get profile photo URL
            $photoUrl = null;
            if ($user->profile_photo_url) {
                if (filter_var($user->profile_photo_url, FILTER_VALIDATE_URL)) {
                    $photoUrl = $user->profile_photo_url;
                } else {
                    $photoUrl = url('storage/' . $user->profile_photo_url);
                }
            }
            
            // Get subjects
            $subjects = $tutor->subjects->pluck('name')->join(', ');
            $subjectText = $subjects ?: ($tutor->keahlian ?? 'N/A');
            
            // Get education level from JSON array
            $educationLevel = 'N/A';
            if (is_array($tutor->education) && !empty($tutor->education)) {
                $firstEdu = $tutor->education[0];
                $educationLevel = $firstEdu['title'] ?? $firstEdu['level'] ?? $firstEdu['education_level'] ?? $firstEdu['org'] ?? 'N/A';
            }
            
            // Calculate monthly and total earnings (placeholder - implement based on your payment/schedule system)
            $monthlyEarnings = 0;
            $totalEarnings = 0;
            $completedMeetings = 0;
            $totalMeetings = 0;
            
            // Get students data (placeholder - implement based on your relationship model)
            $students = [];
            
            // Group schedules by day
            $schedulesByDay = [];
            foreach ($user->schedules as $schedule) {
                $day = $schedule->day;
                if (!isset($schedulesByDay[$day])) {
                    $schedulesByDay[$day] = [];
                }
                $schedulesByDay[$day][] = [
                    'time' => $schedule->time,
                    'is_available' => true,
                    'schedule_id' => $schedule->id
                ];
            }

            // Format schedules for frontend
            $formattedSchedules = [];
            foreach ($schedulesByDay as $day => $timeSlots) {
                $dayNames = [
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                    7 => 'Sunday'
                ];
                
                $formattedSchedules[] = [
                    'day' => $day,
                    'day_name' => $dayNames[$day] ?? 'Unknown',
                    'time_slots' => $timeSlots
                ];
            }
            
            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'fullName' => $user->name,
                    'email' => $user->email,
                    'photo' => $photoUrl,
                    'subject' => $subjectText,
                    'level' => $educationLevel,
                    'birthDate' => $user->date_of_birth ?? 'N/A',
                    'phone' => $user->telephone_number ?? 'N/A',
                    'gender' => $user->gender ? (is_object($user->gender) ? $user->gender->displayName() : ucfirst($user->gender)) : 'N/A',
                    'religion' => $user->religion ? (is_object($user->religion) ? \App\Enums\ReligionEnum::displayName($user->religion->value) : ucfirst($user->religion)) : 'N/A',
                    'province' => $homeAddress['province'] ?? 'N/A',
                    'city' => $homeAddress['city'] ?? 'N/A',
                    'district' => $homeAddress['district'] ?? 'N/A',
                    'village' => $homeAddress['village'] ?? 'N/A',
                    'address' => $homeAddress['address'] ?? 'N/A',
                    'monthlyEarnings' => $monthlyEarnings,
                    'totalEarnings' => $totalEarnings,
                    'completedMeetings' => $completedMeetings,
                    'totalMeetings' => $totalMeetings,
                    'students' => $students,
                    'keahlian' => $tutor->keahlian ?? $subjectText,
                    'marketSiswa' => $tutor->market_siswa ? strtoupper($tutor->market_siswa) : 'N/A',
                    'pengalaman' => $tutor->pengalaman ?? $tutor->experience ?? 'N/A',
                    'organisasi' => $tutor->organisasi ?? $tutor->organization ?? 'N/A',
                    'skillBahasa' => $tutor->skil_bahasa ?? 'N/A',
                    'pendidikan' => $tutor->education ?? [],
                    'tentangSaya' => $tutor->description ?? 'N/A',
                    'jadwalMengajar' => $tutor->learning_method ? (is_string($tutor->learning_method) ? json_decode($tutor->learning_method, true) : $tutor->learning_method) : [],
                    'available_schedules' => [
                        'schedules' => $formattedSchedules
                    ],
                    'hargaPerPertemuan' => $tutor->price ?? 0,
                    'price' => $tutor->price ?? 0,
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
    
    /**
     * Get tutor management summary for dashboard
     * Returns top tutors with their meetings and earnings
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTutorManagementSummary(Request $request)
    {
        try {
            // This is a placeholder - adjust based on your actual data structure
            // You may need to add TakenSchedule or Session models
            $tutors = Tutor::with(['user'])
                ->where('status', TutorStatusEnum::ACTIVE)
                ->limit(5)
                ->get()
                ->map(function ($tutor) {
                    return [
                        'id' => $tutor->user_id,
                        'name' => $tutor->user->name ?? 'N/A',
                        'meetings' => 0, // TODO: Calculate from actual sessions/schedules
                        'earnings' => 0, // TODO: Calculate from completed sessions
                    ];
                });
            
            return response()->json([
                'data' => $tutors,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data kelola tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all tutors for management page (Kelola Tutor)
     * With search and filter capabilities
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTutors(Request $request)
    {
        try {
            // Build query with aggregated rating data
            $query = User::select('users.*')
                ->selectRaw('COALESCE(AVG(reviews.rate), 0) as avg_rating')
                ->selectRaw('COUNT(reviews.id) as review_count')
                ->join('tutors', 'users.id', '=', 'tutors.user_id')
                ->leftJoin('reviews', 'users.id', '=', 'reviews.to_user_id')
                ->where('users.role', 'tutor')
                ->where('tutors.status', TutorStatusEnum::ACTIVE->value)
                ->groupBy('users.id');

            // Filter by search name
            if ($request->has('search') && $request->search) {
                $query->where('users.name', 'like', '%' . $request->search . '%');
            }
            
            // Filter by subject ID
            if ($request->has('subject') && $request->subject) {
                $query->whereHas('tutor.subjects', function ($q) use ($request) {
                    $q->where('subjects.id', $request->subject);
                });
            }
            
            // Get users with tutor relation
            $users = $query->with(['tutor.subjects'])->get();
            
            // Map the results
            $tutors = $users->map(function ($user) {
                $tutor = $user->tutor;
                
                if (!$tutor) {
                    return null;
                }
                
                // Get subjects
                $subjects = $tutor->subjects->pluck('name')->join(', ');
                $subjectText = $subjects ?: ($tutor->keahlian ?? 'Tidak ada mata pelajaran');
                
                // Get profile photo URL - check if already full URL
                $photoUrl = null;
                if ($user->profile_photo_url) {
                    if (filter_var($user->profile_photo_url, FILTER_VALIDATE_URL)) {
                        $photoUrl = $user->profile_photo_url;
                    } else {
                        $photoUrl = url('storage/' . $user->profile_photo_url);
                    }
                }
                
                // Education level from JSON array
                $education = 'Tidak ada info';
                if (is_array($tutor->education) && !empty($tutor->education)) {
                    $firstEdu = $tutor->education[0];
                    // Check various possible keys for education level
                    $education = $firstEdu['title'] ?? $firstEdu['level'] ?? $firstEdu['education_level'] ?? $firstEdu['org'] ?? 'Tidak ada info';
                }
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'photo' => $photoUrl,
                    'subject' => $subjectText,
                    'level' => $education,
                    'totalStudents' => 0, // TODO: Calculate from taken_schedules
                    'totalSessions' => 0, // TODO: Calculate from completed sessions
                    'rating' => round($user->avg_rating, 1),
                    'reviews' => (int) $user->review_count,
                    'description' => $tutor->description ?? $tutor->pengalaman ?? 'Tidak ada deskripsi',
                ];
            })->filter()->values(); // Remove null values and reset keys
            
            return response()->json([
                'data' => $tutors,
                'total' => $tutors->count(),
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error in getAllTutors: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal mengambil data tutor',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Get all subjects for dropdown filter
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubjects(Request $request)
    {
        try {
            $subjects = Subject::select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();
            
            return response()->json([
                'data' => $subjects,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data mata pelajaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tutor salary history
     * 
     * @param int $userId - Tutor user ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTutorSalaryHistory($userId)
    {
        try {
            $salaries = TutorSalary::where('tutor_user_id', $userId)
                ->orderBy('paid_at', 'desc')
                ->get()
                ->map(function ($salary) {
                    // Format paid_at date to Indonesian format
                    $formattedDate = \Carbon\Carbon::parse($salary->paid_at)->locale('id')->isoFormat('D MMMM YYYY');
                    
                    // Build invoice URL
                    $invoiceUrl = $salary->invoice_file_url;
                    if (!filter_var($invoiceUrl, FILTER_VALIDATE_URL)) {
                        $invoiceUrl = url('storage/' . $invoiceUrl);
                    }
                    
                    return [
                        'id' => $salary->id,
                        'date' => $formattedDate,
                        'link' => $invoiceUrl,
                        'amount' => $salary->amount,
                        'notes' => $salary->notes,
                        'paid_at' => $salary->paid_at->format('Y-m-d'),
                    ];
                });

            return response()->json([
                'data' => $salaries,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil riwayat gaji',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit tutor salary invoice
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitSalaryInvoice(Request $request)
    {
        try {
            $request->validate([
                'tutor_id' => 'required|exists:users,id',
                'amount' => 'required|integer|min:0',
                'paid_at' => 'required|date',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
                'notes' => 'nullable|string',
            ]);

            // Upload file
            $file = $request->file('file');
            $fileName = 'invoice_' . $request->tutor_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('invoices', $fileName, 'public');

            // Create salary record
            $salary = TutorSalary::create([
                'tutor_user_id' => $request->tutor_id,
                'admin_user_id' => auth()->id(),
                'amount' => $request->amount,
                'invoice_file_url' => $filePath,
                'paid_at' => $request->paid_at,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => 'Invoice berhasil disimpan',
                'data' => [
                    'id' => $salary->id,
                    'amount' => $salary->amount,
                    'paid_at' => $salary->paid_at->format('Y-m-d'),
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
