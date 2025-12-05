<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Terverifikasi</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #555;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .message {
            font-size: 16px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 25px;
        }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .success-box h3 {
            margin: 0 0 10px 0;
            color: #065f46;
            font-size: 18px;
        }
        .payment-details {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .payment-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-details td {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .payment-details td:first-child {
            font-weight: 600;
            color: #333;
            width: 40%;
        }
        .payment-details tr:last-child td {
            border-bottom: none;
        }
        .button {
            display: inline-block;
            padding: 15px 35px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 20px 0;
            transition: background 0.3s;
        }
        .button:hover {
            background: #059669;
        }
        .footer {
            background: #f8f9fa;
            padding: 25px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Pembayaran Terverifikasi</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Halo {{ $payment->user->name }},</p>
            
            <p class="message">
                Selamat! Pembayaran Anda telah berhasil diverifikasi oleh tim kami.
            </p>
            
            <div class="success-box">
                <h3>✓ Status Pembayaran: TERVERIFIKASI</h3>
                <p style="margin: 10px 0 0 0; color: #555;">
                    Transaksi Anda telah dikonfirmasi dan paket kelas Anda sudah aktif.
                </p>
            </div>
            
            <div class="payment-details">
                <table>
                    <tr>
                        <td>Paket Kelas</td>
                        <td>{{ $payment->package->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Total Pembayaran</td>
                        <td><strong>Rp {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <td>Tanggal Pembayaran</td>
                        <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('d F Y, H:i') }} WIB</td>
                    </tr>
                    <tr>
                        <td>Metode Pembayaran</td>
                        <td>{{ ucfirst($payment->payment_method ?? 'Transfer Bank') }}</td>
                    </tr>
                </table>
            </div>
            
            <p class="message">
                <strong>Langkah Selanjutnya:</strong>
            </p>
            
            <ul style="color: #555; line-height: 1.8;">
                <li>Akses dashboard Anda untuk melihat detail paket</li>
                <li>Pilih tutor yang sesuai dengan kebutuhan Anda</li>
                <li>Jadwalkan sesi bimbingan pertama Anda</li>
                <li>Mulai perjalanan belajar Anda!</li>
            </ul>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.frontend_url') }}/student/dashboard" class="button">
                    Buka Dashboard Saya
                </a>
            </div>
            
            <p class="message">
                Terima kasih telah memilih Bimbel Lazuardy. Kami berkomitmen untuk memberikan pengalaman belajar terbaik untuk Anda!
            </p>
            
            <p class="message">
                Salam,<br>
                <strong>Tim Bimbel Lazuardy</strong>
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Bimbel Lazuardy</strong></p>
            <p>Email: support@lazuardy.com | WhatsApp: +62 812-3456-7890</p>
            <p style="margin-top: 15px; font-size: 12px; color: #999;">
                Email ini dikirim otomatis. Mohon tidak membalas email ini.
            </p>
        </div>
    </div>
</body>
</html>
