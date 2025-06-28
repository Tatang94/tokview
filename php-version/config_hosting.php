<?php
// =====================================================
// KONFIGURASI UNTUK HOSTING SHARED
// =====================================================

// Database configuration - Ezyro hosting
define('DB_HOST', 'sql305.ezyro.com');
define('DB_NAME', 'ezyro_39270123_ahay');
define('DB_USER', 'ezyro_39270123');
define('DB_PASS', 'bec86c42f5');

// Table prefix (jika hosting mengharuskan prefix)
define('TABLE_PREFIX', ''); // kosongkan jika tidak perlu prefix

// N1Panel API configuration
define('N1PANEL_API_KEY', '4dab7086d758c1f5ab89cf4a34cd2201');
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
define('DEBUG_MODE', false);

// Safe error handling for shared hosting
if (DEBUG_MODE) {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>