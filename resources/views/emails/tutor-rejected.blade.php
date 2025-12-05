<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Pendaftaran Tutor</title>
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
            <h1>Pemberitahuan Pendaftaran</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Halo {{ $tutor->user->name }},</p>
            
            <p class="message">
                Terima kasih atas minat Anda untuk bergabung sebagai tutor di Bimbel Lazuardy.
            </p>
            
            <p class="message">
                Setelah melakukan peninjauan menyeluruh terhadap pendaftaran Anda, dengan berat hati kami informasikan bahwa <strong>pendaftaran Anda sebagai tutor belum dapat kami setujui</strong> pada saat ini.
            </p>
            
            <div class="info-box">
                <h3>ℹ️ Kemungkinan Alasan:</h3>
                <ul>
                    <li>Dokumen pendukung yang belum lengkap atau tidak sesuai</li>
                    <li>Keahlian yang didaftarkan tidak sesuai dengan kebutuhan saat ini</li>
                    <li>Jadwal ketersediaan yang terbatas</li>
                    <li>Persyaratan kualifikasi belum terpenuhi</li>
                </ul>
            </div>
            
            <p class="message">
                Kami sangat menghargai usaha dan waktu yang Anda investasikan dalam proses pendaftaran ini. Kami mendorong Anda untuk:
            </p>
            
            <ul style="color: #555; line-height: 1.8;">
                <li>Menghubungi tim kami untuk mendapatkan feedback detail</li>
                <li>Melengkapi dokumen atau kualifikasi yang diperlukan</li>
                <li>Mendaftar kembali di lain waktu</li>
            </ul>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.frontend_url') }}" class="button">
                    Kembali ke Beranda
                </a>
            </div>
            
            <p class="message">
                Jika Anda memiliki pertanyaan atau ingin mendiskusikan keputusan ini lebih lanjut, silakan hubungi kami.
            </p>
            
            <p class="message">
                Salam hormat,<br>
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
