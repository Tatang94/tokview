<?php
// =====================================================
// KONFIGURASI UNTUK HOSTING SHARED
// =====================================================
// Edit bagian ini sesuai dengan detail hosting Anda

// Database configuration - GANTI DENGAN DATA HOSTING ANDA
define('DB_HOST', 'localhost'); // atau server database hosting Anda
define('DB_NAME', 'if0_39341535_tiktok'); // nama database yang disediakan hosting
define('DB_USER', 'if0_39341535'); // username database Anda
define('DB_PASS', 'password_anda'); // password database Anda

// Table prefix (jika hosting mengharuskan prefix)
define('TABLE_PREFIX', ''); // kosongkan jika tidak perlu prefix

// N1Panel API configuration
define('N1PANEL_API_KEY', 'your_api_key_here'); // API key akan diisi otomatis dari environment
define('N1PANEL_API_URL', 'https://n1panel.com/api/v2');
define('TIKTOK_SERVICE_ID', 838);

// Rate limiting
define('DAILY_BOOST_LIMIT', 5);

// Security settings
define('ENABLE_VPN_DETECTION', true);
define('ENABLE_RATE_LIMITING', true);

// App settings
define('APP_NAME', 'TikTok View Booster');
define('APP_VERSION', '1.0.0');
define('DEFAULT_VIEWS_RANGE', [1000, 5000]);

// Error reporting (set ke false untuk production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>