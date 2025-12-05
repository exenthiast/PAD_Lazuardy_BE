<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Ditolak</title>
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
        .warning-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .warning-box h3 {
            margin: 0 0 10px 0;
            color: #991b1b;
            font-size: 18px;
        }
        .info-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #92400e;
            font-size: 18px;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 8px 0;
            color: #555;
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
            background: #41a6c2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 20px 0;
            transition: background 0.3s;
        }
        .button:hover {
            background: #358a9f;
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
            <h1>❌ Pembayaran Ditolak</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Halo {{ $payment->user->name }},</p>
            
            <p class="message">
                Terima kasih telah melakukan pembayaran untuk paket kelas di Bimbel Lazuardy.
            </p>
            
            <div class="warning-box">
                <h3>⚠️ Status Pembayaran: DITOLAK</h3>
                <p style="margin: 10px 0 0 0; color: #555;">
                    Setelah verifikasi, kami menemukan bahwa bukti pembayaran Anda tidak dapat divalidasi.
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
                        <td>Tanggal Upload</td>
                        <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('d F Y, H:i') }} WIB</td>
                    </tr>
                </table>
            </div>
            
            <div class="info-box">
                <h3>Kemungkinan Alasan Penolakan:</h3>
                <ul>
                    <li>Bukti transfer tidak jelas atau terpotong</li>
                    <li>Nominal transfer tidak sesuai dengan paket yang dipilih</li>
                    <li>Tanggal transfer sudah melewati batas waktu yang ditentukan</li>
                    <li>Rekening tujuan transfer tidak sesuai</li>
                    <li>Format atau kualitas gambar bukti pembayaran tidak memadai</li>
                </ul>
            </div>
            
            <p class="message">
                <strong>Apa yang harus dilakukan?</strong>
            </p>
            
            <ol style="color: #555; line-height: 1.8;">
                <li>Periksa kembali bukti transfer Anda</li>
                <li>Pastikan nominal dan rekening tujuan sudah benar</li>
                <li>Upload ulang bukti pembayaran yang jelas dan lengkap</li>
                <li>Atau hubungi tim kami untuk bantuan lebih lanjut</li>
            </ol>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.frontend_url') }}/student/payments" class="button">
                    Upload Ulang Bukti Pembayaran
                </a>
            </div>
            
            <p class="message">
                Jika Anda merasa ini adalah kesalahan atau membutuhkan bantuan, silakan hubungi tim support kami. Kami siap membantu Anda menyelesaikan masalah ini.
            </p>
            
            <p class="message">
                Terima kasih atas pengertiannya.<br>
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
