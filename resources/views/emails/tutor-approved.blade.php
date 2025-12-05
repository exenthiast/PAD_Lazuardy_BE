<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Bergabung!</title>
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
            background: linear-gradient(135deg, #41a6c2 0%, #5cb3cc 100%);
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
            color: #41a6c2;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .message {
            font-size: 16px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 25px;
        }
        .highlight-box {
            background: #e3f2f7;
            border-left: 4px solid #41a6c2;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .highlight-box h3 {
            margin: 0 0 10px 0;
            color: #41a6c2;
            font-size: 18px;
        }
        .highlight-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .highlight-box li {
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
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #41a6c2;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Selamat Bergabung!</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Halo {{ $tutor->user->name }},</p>
            
            <p class="message">
                Kami dengan senang hati menyampaikan bahwa <strong>akun tutor Anda telah disetujui</strong> oleh tim Bimbel Lazuardy! 🎊
            </p>
            
            <p class="message">
                Terima kasih telah bergabung dengan keluarga besar Bimbel Lazuardy. Anda sekarang resmi menjadi bagian dari tim pengajar kami yang berdedikasi untuk memberikan pendidikan berkualitas kepada siswa-siswa Indonesia.
            </p>
            
            <div class="highlight-box">
                <h3>📋 Langkah Selanjutnya:</h3>
                <ul>
                    <li>Login ke dashboard tutor Anda</li>
                    <li>Lengkapi profil Anda jika ada yang masih kosong</li>
                    <li>Atur jadwal ketersediaan mengajar Anda</li>
                    <li>Siap menerima siswa pertama Anda!</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ config('app.frontend_url') }}/tutor/dashboard" class="button">
                    Login ke Dashboard
                </a>
            </div>
            
            <p class="message">
                Jika Anda memiliki pertanyaan atau membutuhkan bantuan, jangan ragu untuk menghubungi kami melalui email atau WhatsApp.
            </p>
            
            <p class="message">
                Semangat mengajar! 💪📚
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
