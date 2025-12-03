<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\User;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // Redirect user ke penyedia autentikasi
    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(string $provider)
    {
        try 
        {
            $socialiteUser = Socialite::driver($provider)->stateless()->user();
            $providerIdColumn = $provider . "_id";

            $user = User::where('email', $socialiteUser->getEmail())
                        ->orWhere($providerIdColumn, $socialiteUser->getId())
                        ->first();

            $loggedUser = null;

            if($user)
            {
                if(empty($user->$providerIdColumn))
                {
                    $user->{$providerIdColumn} = $socialiteUser->getId();
                    $user->save();
                }
                $loggedUser = $user;
            } else {
                $userEmail = $socialiteUser->getEmail() ?? $socialiteUser->getId().'@'.$provider.'.local';
                
                $newUser = User::create([
                    'name' => $socialiteUser->getName(),
                    'email' => $userEmail,
                    $providerIdColumn => $socialiteUser->getId(),
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now()
                ]);

                $loggedUser = $newUser;
            }

            $token = $loggedUser->createToken('auth_token')->plainTextToken;

            // Load relasi tutor untuk cek kelengkapan data
            $loggedUser->load('tutor');

            // Ambil frontend URL dari config
            $frontend = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
            
            // Cek apakah user sudah punya role
            if (empty($loggedUser->role)) {
                // User baru, belum ada role -> redirect ke choose-role
                $redirectUrl = rtrim($frontend, '/') . '/auth/callback?status=NEEDS_ROLE&t=' . urlencode($token);
                return redirect()->away($redirectUrl);
            }
            
            // User lama, sudah punya role -> redirect langsung dengan token
            $redirectUrl = rtrim($frontend, '/') . '/auth/callback?token=' . urlencode($token);
            return redirect()->away($redirectUrl);
        } catch(Exception $e) 
        {
            return response()->json([
                'message' => 'Gagal otentikasi melalui ' . ucfirst($provider) . '.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set role untuk user yang baru login via social auth
     */
    public function setRole()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $role = request()->input('role');
            
            if (!in_array($role, ['student', 'tutor'])) {
                return response()->json([
                    'message' => 'Role tidak valid. Pilih student atau tutor.'
                ], 422);
            }

            // Update role user
            $user->role = $role;
            $user->save();

            return response()->json([
                'message' => 'Role berhasil disimpan',
                'user' => $user,
                'role' => $role
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete tutor profile after social login
     */
    public function completeTutorProfile()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Cek role - karena di model ada cast ke RoleEnum, kita ambil value nya
            $userRole = $user->role;
            if ($userRole instanceof \App\Enums\RoleEnum) {
                $userRole = $userRole->value;
            }

            if ($userRole !== 'tutor') {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya tutor yang dapat melengkapi profil.',
                    'debug' => [
                        'current_role' => $userRole,
                        'role_type' => gettype($user->role),
                        'user_id' => $user->id
                    ]
                ], 403);
            }

            $validated = request()->validate([
                'keahlian' => 'required|string|max:255',
                'marketSiswa' => 'required|in:sd,smp,sma,umum',
                'pengalaman' => 'nullable|string',
                'skilBahasa' => 'nullable|string',
                'organisasi' => 'nullable|string',
                'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
                'ktp' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'ijazah' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            // Cek apakah tutor sudah ada
            $tutor = $user->tutor;
            
            if (!$tutor) {
                // Buat tutor baru
                $tutor = $user->tutor()->create([
                    'keahlian' => $validated['keahlian'],
                    'market_siswa' => $validated['marketSiswa'],
                    'pengalaman' => $validated['pengalaman'] ?? null,
                    'skil_bahasa' => $validated['skilBahasa'] ?? null,
                    'organisasi' => $validated['organisasi'] ?? null,
                    'status' => 'verify', // Status menunggu verifikasi admin
                ]);
            } else {
                // Update tutor yang sudah ada
                $tutor->update([
                    'keahlian' => $validated['keahlian'],
                    'market_siswa' => $validated['marketSiswa'],
                    'pengalaman' => $validated['pengalaman'] ?? null,
                    'skil_bahasa' => $validated['skilBahasa'] ?? null,
                    'organisasi' => $validated['organisasi'] ?? null,
                ]);
            }

            // Handle file uploads jika ada
            if (request()->hasFile('cv')) {
                $cvPath = request()->file('cv')->store('tutor/cv', 'public');
                $tutor->cv_path = $cvPath;
            }

            if (request()->hasFile('ktp')) {
                $ktpPath = request()->file('ktp')->store('tutor/ktp', 'public');
                $tutor->ktp_path = $ktpPath;
            }

            if (request()->hasFile('ijazah')) {
                $ijazahPath = request()->file('ijazah')->store('tutor/ijazah', 'public');
                $tutor->ijazah_path = $ijazahPath;
            }

            $tutor->save();

            // Reload user dengan relasi tutor
            $user->load('tutor');

            return response()->json([
                'message' => 'Profil tutor berhasil dilengkapi. Menunggu verifikasi admin.',
                'user' => $user,
                'tutor' => $tutor,
                'status' => 'verify'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan profil tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload tutor profile photo
     */
    public function uploadPhoto()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            request()->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if (request()->hasFile('photo')) {
                $file = request()->file('photo');
                $path = $file->store('tutor/photos', 'public');
                
                $user->profile_photo_url = $path;
                $user->save();

                // Return full URL untuk frontend
                $fullUrl = url('storage/' . $path);

                return response()->json([
                    'message' => 'Foto profil berhasil diperbarui',
                    'photo_url' => $fullUrl,
                    'photo_path' => $path
                ], 200);
            }

            return response()->json([
                'message' => 'Tidak ada file yang diunggah'
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mengunggah foto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update tutor profile
     */
    public function updateTutorProfile()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Cek role
            $userRole = $user->role;
            if ($userRole instanceof \App\Enums\RoleEnum) {
                $userRole = $userRole->value;
            }

            if ($userRole !== 'tutor') {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya tutor yang dapat mengupdate profil.'
                ], 403);
            }

            // Handle name update dengan validasi 7 hari
            if (request()->has('name')) {
                $lastNameEdit = $user->last_name_edit;
                
                if ($lastNameEdit) {
                    $daysSinceLastEdit = now()->diffInDays($lastNameEdit);
                    
                    if ($daysSinceLastEdit < 7) {
                        $nextEditDate = \Carbon\Carbon::parse($lastNameEdit)->addDays(7)->format('d F Y');
                        return response()->json([
                            'message' => "Nama hanya dapat diubah sekali dalam 7 hari. Anda dapat mengubah nama kembali pada {$nextEditDate}",
                            'can_edit_at' => $nextEditDate,
                            'days_remaining' => 7 - $daysSinceLastEdit
                        ], 422);
                    }
                }
                
                $user->name = request()->input('name');
                $user->last_name_edit = now();
                $user->save();
            }

            $tutor = $user->tutor;
            
            if (!$tutor) {
                return response()->json([
                    'message' => 'Data tutor tidak ditemukan'
                ], 404);
            }

            // Update fields yang dikirim menggunakan property assignment langsung
            // untuk menghindari validation errors dari mass assignment
            
            if (request()->has('description')) {
                $tutor->description = request()->input('description');
            }

            if (request()->has('keahlian')) {
                $tutor->keahlian = request()->input('keahlian');
            }

            if (request()->has('skills')) {
                // Bisa disimpan sebagai JSON atau string
                $skills = request()->input('skills');
                if (is_array($skills) && !empty($skills)) {
                    $tutor->keahlian = $skills[0];
                }
            }

            if (request()->has('schedule')) {
                // Simpan schedule sebagai JSON atau relasi terpisah
                $tutor->learning_method = json_encode(request()->input('schedule'));
            }

            if (request()->has('education')) {
                $tutor->education = request()->input('education');
            }

            // Simpan perubahan
            $tutor->save();

            // Reload dengan relasi
            $user->load('tutor');

            return response()->json([
                'message' => 'Profil tutor berhasil diperbarui',
                'user' => $user,
                'tutor' => $tutor
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui profil tutor',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
