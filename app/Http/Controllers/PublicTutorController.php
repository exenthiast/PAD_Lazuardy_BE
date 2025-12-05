<?php

namespace App\Http\Controllers;

use App\Enums\TutorStatusEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicTutorController extends Controller
{
    /**
     * Get all active tutors (public endpoint - no auth required)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::select('users.*')
            ->selectRaw('COALESCE(AVG(reviews.rate), 0) as avg_rating')
            ->selectRaw('COUNT(reviews.id) as review_count')
            ->join('tutors', 'users.id', '=', 'tutors.user_id')
            ->leftJoin('reviews', 'users.id', '=', 'reviews.to_user_id')
            ->where('users.role', 'tutor')
            ->where('tutors.status', TutorStatusEnum::ACTIVE->value)
            ->groupBy('users.id');

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('users.name', 'like', '%' . $request->search . '%');
        }

        // Filter by subject
        if ($request->has('subject') && $request->subject) {
            $query->whereHas('tutor.subjects', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->subject . '%');
            });
        }

        // Filter by level (class)
        if ($request->has('level') && $request->level) {
            $query->whereHas('tutor.subjects.class', function ($q) use ($request) {
                $q->where('name', $request->level);
            });
        }

        // Filter by teaching mode
        if ($request->has('mode') && $request->mode) {
            $mode = strtolower($request->mode);
            $query->whereHas('tutor', function ($q) use ($mode) {
                $q->where(function ($subQ) use ($mode) {
                    $subQ->where('course_mode', $mode)
                         ->orWhere('course_mode', 'both');
                });
            });
        }

        // Get all tutors with relations
        $users = $query->with(['tutor.subjects.class'])->get();

        // Map the results
        $tutors = $users->map(function ($user) {
            // Get tutor relation
            $tutor = $user->tutor;
            
            if (!$tutor) {
                return null;
            }

            // Get subjects with classes
            $subjects = $tutor->subjects->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'class' => [
                        'id' => $subject->class->id ?? null,
                        'name' => $subject->class->name ?? null,
                    ]
                ];
            });

            // Get profile photo URL
            $photoUrl = null;
            if ($user->profile_photo_url) {
                // Check if it's already a full URL
                if (filter_var($user->profile_photo_url, FILTER_VALIDATE_URL)) {
                    $photoUrl = $user->profile_photo_url;
                } else {
                    $photoUrl = url('storage/' . $user->profile_photo_url);
                }
            }

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $photoUrl,
                'telephone_number' => $user->telephone_number,
                'gender' => $user->gender,
                'market_siswa' => $tutor->market_siswa,
                'keahlian' => $tutor->keahlian,
                'subjects' => $subjects,
                'teaching_method' => [
                    // course_mode hanya online/offline, tidak ada 'both'
                    // jika null, anggap both
                    'course_mode' => $tutor->course_mode ?? 'both',
                    'description' => $tutor->learning_method ?? null,
                ],
                'tutor_info' => [
                    'price' => $tutor->price ?? 0,
                    'salary' => $tutor->salary ?? 0,
                    'description' => $tutor->description ?? null,
                    'experience' => $tutor->experience ?? null,
                    'pengalaman' => $tutor->pengalaman ?? null,
                ],
                'rating' => [
                    'average' => round($user->avg_rating, 1),
                    'count' => (int) $user->review_count,
                ],
                'qualification' => $tutor->qualification ?? [],
                'education' => $tutor->education ?? [],
                'organization' => $tutor->organization ?? [],
                'skil_bahasa' => $tutor->skil_bahasa,
                'organisasi' => $tutor->organisasi,
            ];
        })->filter()->values(); // Remove nulls and reset keys

        return response()->json([
            'status' => 'success',
            'data' => $tutors,
            'total' => $tutors->count(),
            'filters_applied' => [
                'search' => $request->search ?? null,
                'subject' => $request->subject ?? null,
                'level' => $request->level ?? null,
                'mode' => $request->mode ?? null,
            ]
        ]);
    }
}
