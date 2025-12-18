# Fix Tutor Status Implementation

## 🐛 Masalah yang Ditemukan

### 1. Status Tutor NULL di Database

**Root Cause:** Ketika tutor mendaftar via `AuthController::storeTutorRegister()`, field `status` tidak di-set, sehingga default-nya NULL.

**Expected Flow:**

```
Tutor Register → Status "verify" → HomePending (waiting admin)
                    ↓
              Admin Action
           ↙            ↘
    Approve          Reject
  Status "active"   Status "rejected"
  → /tutor/dashboard → /tutor/rejected
```

### 2. Error di Dashboard Admin

**Issue:** Enum comparison error di `getPendingTutors()`

```php
// ❌ BEFORE (Error)
'status' => $tutor->status === TutorStatusEnum::VERIFY ? 'Menunggu' : ...

// ✅ AFTER (Fixed)
'status' => $tutor->status ? $tutor->status->displayName() : 'Menunggu'
```

## ✅ Fix yang Sudah Diterapkan

### 1. AuthController.php

**File:** `app/Http/Controllers/AuthController.php`

**Import Enum:**

```php
use App\Enums\TutorStatusEnum;
```

**Set Default Status:**

```php
$tutorData['badge'] = BadgeEnum::BRONZE;
$tutorData['status'] = TutorStatusEnum::VERIFY; // ← Added this line

DB::beginTransaction();
```

### 2. AdminDashboardController.php

**File:** `app/Http/Controllers/AdminDashboardController.php`

**Fixed Status Display:**

```php
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
                    'status' => $tutor->status ? $tutor->status->displayName() : 'Menunggu', // ← Fixed
                    'created_at' => $tutor->created_at,
                ];
            });

        // ...
    }
}
```

## 🔧 Migration untuk Update Data Lama

Untuk tutor yang sudah terdaftar dengan status NULL, jalankan query berikut:

### SQL Direct Query

```sql
-- Update all tutors with NULL status to "verify"
UPDATE tutors
SET status = 'verify'
WHERE status IS NULL;
```

### Atau Create Migration

```bash
php artisan make:migration update_null_tutor_status_to_verify
```

**Migration File:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing tutors with NULL status to "verify"
        DB::table('tutors')
            ->whereNull('status')
            ->update(['status' => 'verify']);
    }

    public function down(): void
    {
        // Rollback: set back to NULL (optional)
        DB::table('tutors')
            ->where('status', 'verify')
            ->update(['status' => null]);
    }
};
```

**Run Migration:**

```bash
php artisan migrate
```

## 🧪 Testing

### 1. Test New Registration

```bash
# Register tutor baru
POST /api/tutor/register
{
  "email": "newtutor@test.com",
  "password": "password123",
  "name": "Test Tutor",
  ...
}

# Check database
SELECT id, name, status FROM tutors WHERE user_id = <new_user_id>;
# Expected: status = "verify"
```

### 2. Test Dashboard Admin

```bash
# Get pending tutors
GET /api/admin/dashboard/pending-tutors
Authorization: Bearer <admin_token>

# Expected Response:
{
  "data": [
    {
      "id": 1,
      "name": "Test Tutor",
      "subject": "Matematika",
      "status": "Menunggu", // ← Should display correctly
      "created_at": "2025-12-17..."
    }
  ],
  "total": 1
}
```

### 3. Test Admin Actions

```bash
# Approve tutor
PATCH /api/admin/tutor/approve
{ "user_id": 1 }

# Check: tutor should redirect to /tutor/dashboard
# Database: status should be "active"

# Reject tutor
PATCH /api/admin/tutor/reject
{ "user_id": 2 }

# Check: tutor should redirect to /tutor/rejected
# Database: status should be "rejected"
```

### 4. Test HomePending Redirect Logic

**File:** `HomePending.vue`

Logic sudah benar:

```javascript
const status = tutor.value.status?.toLowerCase();
if (status === "active" || status === "approved") {
    router.push("/tutor/dashboard");
} else if (status === "rejected") {
    router.push("/tutor/rejected");
}
```

## 📋 Checklist

-   [x] Fix AuthController to set default status "verify"
-   [x] Fix AdminDashboardController enum comparison
-   [ ] Run migration to update existing NULL status to "verify"
-   [ ] Test new tutor registration
-   [ ] Test admin dashboard displays pending tutors
-   [ ] Test approve/reject functionality
-   [ ] Test redirect logic in HomePending

## 🔐 Important Notes

1. **Default Status:** Semua tutor baru akan otomatis dapat status "verify"
2. **Admin Action:** Admin harus approve/reject tutor secara manual
3. **Auto Redirect:** HomePending akan auto redirect berdasarkan status:
    - `verify` → Stay in HomePending
    - `active` → Redirect to /tutor/dashboard
    - `rejected` → Redirect to /tutor/rejected
4. **Status Check:** Interval check setiap 30 detik di HomePending

## 🚀 Next Steps

1. Update database tutors yang status-nya NULL
2. Test full flow dari registration sampai approval
3. Verifikasi email notification saat approve/reject (jika ada)
4. Monitor error logs untuk memastikan tidak ada error lagi

---

**Date:** December 17, 2025  
**Status:** ✅ FIXED - Ready to test
