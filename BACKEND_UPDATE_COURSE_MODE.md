# Backend Update: Course Mode untuk Jadwal Mengajar

## 📋 Ringkasan

Menambahkan pilihan `course_mode` (online, offline, flexible) ke table `tutors` untuk memungkinkan siswa memfilter tutor berdasarkan metode pembelajaran.

## 🗄️ Database Migration

### 1. Update Enum di Table Tutors

Buat migration baru untuk update kolom `course_mode`:

```bash
php artisan make:migration update_course_mode_in_tutors_table
```

Isi migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            // Update enum untuk course_mode, tambahkan 'flexible'
            $table->enum('course_mode', ['online', 'offline', 'flexible'])
                  ->nullable()
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            // Kembalikan ke enum lama
            $table->enum('course_mode', ['online', 'offline'])
                  ->nullable()
                  ->change();
        });
    }
};
```

Jalankan migration:

```bash
php artisan migrate
```

### 2. Update Model Tutor

File: `app/Models/Tutor.php`

Tambahkan `course_mode` ke `$fillable` jika belum ada:

```php
protected $fillable = [
    'user_id',
    'keahlian',
    'market_siswa',
    'pengalaman',
    'organisasi',
    'skil_bahasa',
    'learning_method',
    'course_mode', // ← Pastikan ini ada
    'price',
    'bank',
    'rekening',
    'cv_path',
    'ktp_path',
    'ijazah_path',
    'bank_name',
    'bank_account_number',
    'bank_account_name',
    'description',
    'education',
    'status',
    // ... fields lainnya
];

protected $casts = [
    'education' => 'array',
    'status' => TutorStatus::class,
];
```

## 🔧 Controller Updates

### 1. ProfileController - updateTutorProfile()

File: `app/Http/Controllers/ProfileController.php`

Update method untuk handle `course_mode`:

```php
public function updateTutorProfile(Request $request)
{
    try {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userData = [];
        $tutorData = [];

        // ... existing code ...

        // Handle course_mode
        if ($request->has('course_mode')) {
            $tutorData['course_mode'] = $request->input('course_mode');
        }

        // ... existing schedule handling code ...

        // Update tutor data
        if (!empty($tutorData) && $user->tutor) {
            $user->tutor->update($tutorData);
        }

        // ... rest of the code ...

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load('tutor'),
        ], 200);

    } catch (\Exception $e) {
        Log::error('Profile update error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to update profile',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

### 2. LoginController - me()

File: `app/Http/Controllers/Auth/LoginController.php`

Pastikan `course_mode` di-load saat fetch user data:

```php
public function me(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Load tutor dengan schedules
    $user->load(['tutor.schedules']);

    // Format response
    $userData = $user->toArray();

    if ($user->tutor) {
        // ... existing schedule formatting code ...

        // Ensure course_mode is included
        $userData['tutor']['course_mode'] = $user->tutor->course_mode;
    }

    return response()->json(['user' => $userData], 200);
}
```

### 3. FindTutorController - show() & index()

File: `app/Http/Controllers/FindTutorController.php`

Update untuk include `course_mode` dan enable filtering:

```php
public function index(Request $request)
{
    try {
        $query = User::with(['tutor.schedules'])
            ->whereHas('tutor', function ($q) {
                $q->where('status', 'active');
            });

        // Filter by course_mode if provided
        if ($request->has('course_mode')) {
            $courseMode = $request->input('course_mode');

            $query->whereHas('tutor', function ($q) use ($courseMode) {
                if ($courseMode === 'flexible') {
                    // If looking for flexible, return all tutors (including flexible, online, offline)
                    // Or specifically only flexible tutors
                    $q->where('course_mode', 'flexible');
                } else {
                    // If looking for online/offline, include flexible tutors too
                    $q->where(function ($subQ) use ($courseMode) {
                        $subQ->where('course_mode', $courseMode)
                             ->orWhere('course_mode', 'flexible');
                    });
                }
            });
        }

        // ... existing filters (keahlian, market_siswa, etc) ...

        $tutors = $query->get();

        return response()->json([
            'message' => 'Tutors retrieved successfully',
            'data' => $tutors,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Find tutor error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to retrieve tutors',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function show($id)
{
    try {
        $user = User::with(['tutor.schedules'])->findOrFail($id);

        if (!$user->tutor) {
            return response()->json([
                'message' => 'Tutor not found'
            ], 404);
        }

        // Format response including course_mode
        $tutorData = $user->toArray();

        // Ensure course_mode is included
        $tutorData['tutor']['course_mode'] = $user->tutor->course_mode;

        return response()->json([
            'message' => 'Tutor detail retrieved successfully',
            'data' => $tutorData,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Show tutor error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to retrieve tutor detail',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

### 4. AdminDashboardController - getTutorDetail()

File: `app/Http/Controllers/Admin/AdminDashboardController.php`

Include `course_mode` di tutor detail untuk admin:

```php
public function getTutorDetail($id)
{
    try {
        $user = User::with(['tutor.schedules'])->findOrFail($id);

        if (!$user->tutor) {
            return response()->json([
                'message' => 'Tutor not found'
            ], 404);
        }

        $tutorData = $user->toArray();

        // Include course_mode
        $tutorData['tutor']['course_mode'] = $user->tutor->course_mode;

        return response()->json([
            'message' => 'Tutor detail retrieved successfully',
            'data' => $tutorData,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Admin get tutor detail error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to retrieve tutor detail',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

## ✅ Testing

### 1. Test Update Course Mode

```bash
curl -X PATCH http://localhost:8000/api/tutor/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "course_mode": "flexible"
  }'
```

### 2. Test Get User with Course Mode

```bash
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Response should include:

```json
{
  "user": {
    "tutor": {
      "course_mode": "flexible",
      ...
    }
  }
}
```

### 3. Test Filter Tutors by Course Mode

```bash
# Cari tutor yang bisa online (akan return online + flexible)
curl -X GET "http://localhost:8000/api/tutors?course_mode=online"

# Cari tutor yang bisa offline (akan return offline + flexible)
curl -X GET "http://localhost:8000/api/tutors?course_mode=offline"

# Cari tutor yang flexible
curl -X GET "http://localhost:8000/api/tutors?course_mode=flexible"
```

## 📝 Validation (Optional)

Tambahkan validation di ProfileController:

```php
$request->validate([
    'course_mode' => 'nullable|in:online,offline,flexible',
    // ... other validations
]);
```

## 🎯 Use Cases

1. **Tutor Flexible**: Ditampilkan untuk siswa yang mencari online atau offline
2. **Tutor Online Only**: Hanya muncul di pencarian online
3. **Tutor Offline Only**: Hanya muncul di pencarian offline

## 🔄 Data Display Logic

Frontend akan menampilkan:

-   `online` → 🌐 Online
-   `offline` → 📍 Offline
-   `flexible` → 🌐 Online • 📍 Offline

Siswa dapat memfilter berdasarkan kebutuhan mereka, dan tutor dengan `flexible` akan muncul di kedua kategori.
