<?php

namespace App\Http\Controllers;

use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutorProfileSelfController extends Controller
{
    /**
     * Get authenticated tutor's own profile
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $tutor = Tutor::with(['user'])
                ->where('user_id', $user->id)
                ->first();
            
            if (!$tutor) {
                return response()->json([
                    'message' => 'Tutor profile not found',
                ], 404);
            }

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
                    'keahlian' => $tutor->keahlian ?? 'N/A',
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
                'message' => 'Gagal mengambil profil tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
