TIKTOK VIEW BOOSTER - VERSI PHP
===============================

CARA SETUP:

1. Upload semua file ke hosting Ezyro Anda
2. Import file database_ezyro.sql ke database: ezyro_39270123_ahay
3. Akses index_hosting.php di browser
4. File config_hosting.php sudah dikonfigurasi dengan:
   - Database Ezyro: sql305.ezyro.com
   - API Key N1Panel: 4dab7086d758c1f5ab89cf4a34cd2201
5. Untuk testing, gunakan debug_test.php

STRUKTUR FILE:
- index.php = File utama aplikasi (frontend + backend)
- config.php = Konfigurasi database dan API
- database.sql = Schema database untuk import
- README.txt = File instruksi ini

FITUR:
- Design sama persis dengan versi Express.js
- Database MySQL yang kompatibel dengan hosting shared
- Limit 5 boost per IP per hari
- Validasi URL TikTok
- Tracking statistik harian
- Responsive mobile design
- Single file application (mudah deploy)

REQUIREMENTS:
- PHP 7.4+ dengan PDO MySQL
- MySQL 5.7+ atau MariaDB 10.2+
- Hosting yang support PHP dan MySQL

KEAMANAN:
- IP-based rate limiting
- SQL injection protection dengan prepared statements
- Input validation dan sanitization
- Session management

Untuk menggunakan API N1Panel yang sesungguhnya, edit bagian "Simulate API call" di index.php dan ganti dengan implementasi API call yang real.