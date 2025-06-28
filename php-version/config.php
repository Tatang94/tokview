<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tiktok_booster');
define('DB_USER', 'root');
define('DB_PASS', '');

// N1Panel API configuration (ganti dengan API key asli)
define('N1PANEL_API_KEY', 'your_api_key_here');
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
?>