# Testing Flow Booking dengan Simulasi Payment

## 📋 Overview
Sistem Instant Booking dengan simulasi payment gateway untuk demo/testing tanpa integrasi Midtrans.

## 🔧 Setup Backend

### 1. Run Migration
```bash
cd c:\laragon\www\PAD_Lazuardy_BE
php artisan migrate
```

### 2. Verify Routes
```bash
php artisan route:list | grep booking
```

Expected output:
```
POST   api/bookings .................. BookingController@store
POST   api/bookings/{id}/confirm ..... BookingController@dummyConfirm
GET    api/bookings/{id} ............. BookingController@show
```

## 🎯 Testing Flow

### Step 1: Buat Booking (Frontend → Backend)
**Endpoint:** `POST /api/bookings`

**Request Payload:**
```json
{
  "tutor_id": 2,
  "schedule_date": "2024-12-10",
  "schedule_time": "09:00",
  "price": 150000
}
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "Booking created",
  "booking_id": 1,
  "payment_url": "http://localhost:5173/payment/simulation/1"
}
```

### Step 2: Auto Redirect ke Payment Simulation
Frontend akan otomatis redirect ke:
```
http://localhost:5173/payment/simulation/1
```

### Step 3: User Klik "Bayar Sekarang"
**Endpoint:** `POST /api/bookings/{id}/confirm`

**Expected Response:**
```json
{
  "status": "success",
  "message": "Pembayaran berhasil dikonfirmasi",
  "data": {
    "booking_id": 1,
    "status": "paid",
    "schedule_time": "2024-12-10 09:00:00",
    "price": 150000
  }
}
```

### Step 4: Success Modal → Redirect Dashboard
User melihat modal sukses dan redirect ke `/dashboard`

## 📁 Files Created/Modified

### Backend (Laravel)
1. ✅ `database/migrations/2024_12_05_100000_create_bookings_table.php`
2. ✅ `app/Models/Booking.php` (sudah ada, tidak diubah)
3. ✅ `app/Http/Controllers/BookingController.php` (modified)
4. ✅ `routes/api.php` (added routes)

### Frontend (Vue)
1. ✅ `src/views/payment/PaymentSimulation.vue` (new)
2. ✅ `src/router/index.js` (added route)
3. ✅ `src/views/tutors/TutorDetailPage.vue` (modified)

## 🧪 Manual Testing Steps

### 1. Start Servers
```bash
# Terminal 1 - Backend
cd c:\laragon\www\PAD_Lazuardy_BE
php artisan serve

# Terminal 2 - Frontend
cd c:\Users\USER\Documents\Vue JS\project-lazuardy
npm run dev
```

### 2. Test Flow
1. Buka `http://localhost:5173/tutors`
2. Pilih tutor → klik "Booking Sesi"
3. Klik salah satu jam (misal: 09:00)
4. Modal konfirmasi muncul → klik "Lanjut ke Pembayaran"
5. Redirect otomatis ke `http://localhost:5173/payment/simulation/1`
6. Pilih metode payment (dummy)
7. Klik "Bayar Sekarang"
8. Modal success muncul → klik "Kembali ke Dashboard"

### 3. Verify Database
```sql
SELECT * FROM bookings ORDER BY id DESC LIMIT 5;
```

Expected data:
```
id | user_id | tutor_id | schedule_time       | price  | status | payment_url
1  | 1       | 2        | 2024-12-10 09:00:00 | 150000 | paid   | http://localhost:5173/payment/simulation/1
```

## 🐛 Troubleshooting

### Error: "Booking tidak ditemukan"
- Cek apakah migration sudah dijalankan
- Verify booking ID di database

### Error: "User not found"
- Pastikan ada user dengan ID 1 di tabel `users`
- Controller menggunakan user ID 1 sebagai default untuk testing

### Payment URL tidak redirect
- Cek CORS settings di backend
- Verify URL di `.env` frontend: `VITE_API_BASE_URL=http://127.0.0.1:8000`

### Status tidak berubah jadi "paid"
- Cek response dari endpoint `/api/bookings/{id}/confirm`
- Verify method di BookingController sudah benar

## 📊 Database Schema

### Table: `bookings`
```sql
CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tutor_id BIGINT UNSIGNED NOT NULL,
    schedule_time DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('unpaid','paid','completed','cancelled','refunded') DEFAULT 'unpaid',
    payment_url VARCHAR(255) NULL,
    payment_token VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 🚀 Next Steps

1. [ ] Integrate real payment gateway (Midtrans/Xendit)
2. [ ] Add webhook handler for payment notification
3. [ ] Implement booking cancellation
4. [ ] Add email notification for confirmed booking
5. [ ] Create booking history page for students
6. [ ] Add tutor schedule blocking after booking confirmed

## 📝 Notes

- Simulasi ini hanya untuk demo/testing
- Status langsung berubah `unpaid` → `paid` tanpa real payment
- User authentication optional (default ke user ID 1)
- Payment URL hardcoded ke `http://localhost:5173`
- Ganti URL saat production
