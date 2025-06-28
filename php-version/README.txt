TIKTOK VIEW BOOSTER - VERSI PHP
===============================

CARA SETUP:

1. Upload semua file ke hosting Anda
2. Buat database MySQL baru bernama "tiktok_booster"
3. Import file database.sql ke database tersebut
4. Edit file config.php:
   - Sesuaikan DB_HOST, DB_USER, DB_PASS dengan setting hosting Anda
   - Masukkan API key N1Panel yang asli di N1PANEL_API_KEY
5. Akses index.php di browser

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