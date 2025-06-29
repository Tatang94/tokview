<?php

class SecureConfig {
    private static $encryptionKey = 'TikTokBooster2025!SecureKey#Indonesian';
    private static $cipher = 'AES-256-CBC';
    
    public static function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($data, self::$cipher, self::$encryptionKey, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    public static function decrypt($data) {
        $parts = explode('::', base64_decode($data), 2);
        if (count($parts) !== 2) return false;
        
        list($encrypted_data, $iv) = $parts;
        return openssl_decrypt($encrypted_data, self::$cipher, self::$encryptionKey, 0, $iv);
    }
    
    public static function getDatabaseConfig() {
        return [
            'host' => 'localhost',
            'dbname' => 'atzpuls1_buka',
            'username' => 'atzpuls1_buka',
            'password' => 'Buka@100'
        ];
    }
    
    public static function getApiConfig() {
        $encryptedApiKey = 'OTk0MTc5MTViOGIzNDhiMDI1ZWUzNDhlNjc4Yjc3ODg=';
        $encryptedApiUrl = 'aHR0cHM6Ly9sb2xsaXBvcC1zbW0uY29tL2FwaS92Mg==';
        
        return [
            'api_key' => base64_decode($encryptedApiKey),
            'api_url' => base64_decode($encryptedApiUrl),
            'services' => [
                'views' => 746,      // TikTok Views - Cost: Rp 22/1K | Sell: Rp 66/1K (3x markup)
                'followers' => 748,  // TikTok Followers - Cost: Rp 17.034/1K | Sell: Rp 22.034/1K (+Rp 5K profit)  
                'likes' => 6         // TikTok Likes - Cost: Rp 490/1K | Sell: Rp 1.470/1K (3x markup)
            ],
            'pricing' => [
                'views' => ['cost' => 22, 'sell' => 66, 'profit_margin' => 200],
                'followers' => ['cost' => 17034, 'sell' => 22034, 'profit_margin' => 29.4],
                'likes' => ['cost' => 490, 'sell' => 1470, 'profit_margin' => 200]
            ]
        ];
    }
    
    public static function getPaymentConfig() {
        return [
            'dana_ewallet' => [
                'phone_number' => '6289663596711',
                'account_name' => 'TikTok Booster Admin',
                'profit_margin' => 15000 // Rp 15.000 profit dari setiap transaksi
            ],
            'license_price' => 50000, // Harga license premium
            'topup_amount' => 50000,  // Amount yang akan di-topup ke Lollipop SMM
            'payment_method' => 'dana_manual',
            'admin_contact' => 'https://wa.me/6289663596711'
        ];
    }
    
    public static function encryptUrl($url) {
        $urlSalt = 'TikTokURL_' . date('Y-m-d');
        return self::encrypt($url . '|' . $urlSalt);
    }
    
    public static function decryptUrl($encryptedUrl) {
        $decrypted = self::decrypt($encryptedUrl);
        if (!$decrypted) return false;
        
        $parts = explode('|', $decrypted);
        return $parts[0] ?? false;
    }
    
    public static function validateRequest($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        $suspiciousRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12', 
            '192.168.0.0/16',
            '127.0.0.0/8'
        ];
        
        foreach ($suspiciousRanges as $range) {
            if (self::ipInRange($ip, $range)) {
                return false;
            }
        }
        
        return true;
    }
    
    private static function ipInRange($ip, $range) {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }
}

$dbConfig = SecureConfig::getDatabaseConfig();
$apiConfig = SecureConfig::getApiConfig();
$paymentConfig = SecureConfig::getPaymentConfig();

$dailyLimit = 999;
$appName = 'TikTok View Booster';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4", 
        $dbConfig['username'], 
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi database gagal");
}

$createTable = "CREATE TABLE IF NOT EXISTS boosts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url_encrypted TEXT NOT NULL,
    service_id INT DEFAULT 746,
    service_type ENUM('views', 'followers', 'likes') DEFAULT 'views',
    order_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    views_added INT DEFAULT 0,
    processing_time VARCHAR(50),
    title VARCHAR(500),
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($createTable);
} catch(PDOException $e) {
}

function callSecureAPI($endpoint, $data = null) {
    global $apiConfig;
    
    $url = $apiConfig['api_url'] . $endpoint;
    $postData = $data ? array_merge($data, ['key' => $apiConfig['api_key']]) : ['key' => $apiConfig['api_key']];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'TikTokBooster/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => $response, 'httpCode' => $httpCode, 'data' => json_decode($response, true)];
}

function validateTikTokUrl($url) {
    $patterns = [
        '/^https?:\/\/(www\.)?tiktok\.com\/@[\w\.-]+\/video\/\d+/',
        '/^https?:\/\/vm\.tiktok\.com\/[\w\d]+/',
        '/^https?:\/\/vt\.tiktok\.com\/[\w\d]+/',
        '/^https?:\/\/m\.tiktok\.com\/v\/\d+/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return true;
        }
    }
    return false;
}

function checkDailyLimit($ip) {
    global $pdo, $dailyLimit;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM boosts WHERE ip_address = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$ip]);
    $count = $stmt->fetchColumn();
    
    return [
        'canBoost' => $count < $dailyLimit,
        'boostsToday' => $count,
        'boostsRemaining' => max(0, $dailyLimit - $count)
    ];
}

function getTodayStats() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as totalBoosts,
            COUNT(CASE WHEN service_type = 'views' THEN 1 END) as viewsBoosts,
            COUNT(CASE WHEN service_type = 'followers' THEN 1 END) as followersBoosts,
            COUNT(CASE WHEN service_type = 'likes' THEN 1 END) as likesBoosts,
            SUM(CASE WHEN views_added > 0 THEN views_added ELSE 
                CASE service_type 
                    WHEN 'views' THEN 1000 
                    WHEN 'followers' THEN 500 
                    WHEN 'likes' THEN 1000 
                    ELSE 1000 
                END 
            END) as totalBoosts,
            COUNT(CASE WHEN status IN ('completed', 'failed') THEN 1 END) as completed,
            AVG(CASE WHEN processing_time IS NOT NULL THEN CAST(REPLACE(processing_time, 's', '') AS DECIMAL(10,2)) END) as avgTime
        FROM boosts 
        WHERE DATE(created_at) = CURDATE()
    ");
    
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $successRate = $stats['totalBoosts'] > 0 ? ($stats['completed'] / $stats['totalBoosts']) * 100 : 0;
    
    return [
        'videosToday' => (int)$stats['totalBoosts'],
        'totalViews' => (int)($stats['totalBoosts'] ?? 0),
        'successRate' => round($successRate, 1),
        'avgTime' => $stats['avgTime'] ? round($stats['avgTime'], 1) . 's' : '0s',
        'breakdown' => [
            'views' => (int)$stats['viewsBoosts'],
            'followers' => (int)$stats['followersBoosts'],
            'likes' => (int)$stats['likesBoosts']
        ]
    ];
}

function validateLicense($code) {
    $licenses = [
        'TKB2025-LICENSED' => ['type' => 'unlimited', 'daily_limit' => 999, 'features' => 'Unlimited Access']
    ];
    
    $code = strtoupper($code);
    if (isset($licenses[$code])) {
        return $licenses[$code];
    }
    return false;
}

function generateDanaPaymentInfo($amount) {
    $paymentConfig = SecureConfig::getPaymentConfig();
    $danaConfig = $paymentConfig['dana_ewallet'];
    $uniqueCode = 'TKB' . time() . rand(100, 999);
    
    return [
        'success' => true,
        'unique_code' => $uniqueCode,
        'amount' => $amount,
        'dana_number' => $danaConfig['phone_number'],
        'account_name' => $danaConfig['account_name'],
        'admin_contact' => $paymentConfig['admin_contact'],
        'expired_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'instructions' => [
            'Buka aplikasi Dana',
            'Pilih menu Transfer > Ke Nomor HP',
            'Masukkan nomor: ' . $danaConfig['phone_number'],
            'Masukkan jumlah: Rp ' . number_format($amount),
            'Tambahkan keterangan: ' . $uniqueCode,
            'Kirim screenshot bukti transfer ke admin'
        ]
    ];
}

function createManualPaymentRecord($uniqueCode, $amount) {
    // Simpan record pembayaran manual untuk tracking
    $paymentData = [
        'unique_code' => $uniqueCode,
        'amount' => $amount,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'payment_method' => 'dana_manual'
    ];
    
    // Dalam implementasi nyata, simpan ke database
    $_SESSION['pending_payment'] = $paymentData;
    
    return $paymentData;
}

function processManualTopup($amount) {
    // Fungsi untuk proses manual topup ke Lollipop SMM
    return [
        'success' => true,
        'message' => 'Manual topup request created for admin processing',
        'amount' => $amount,
        'timestamp' => date('Y-m-d H:i:s'),
        'note' => 'Admin akan memproses topup ke Lollipop SMM setelah verifikasi pembayaran'
    ];
}



session_start();

// Handle automatic verification via link
if (isset($_GET['verify']) && !empty($_GET['verify'])) {
    $verifyCode = $_GET['verify'];
    
    // Validate verification code format
    if (preg_match('/^TKB\d+$/', $verifyCode)) {
        // Auto-activate license for this verification code
        $_SESSION['license_valid'] = true;
        $_SESSION['license_info'] = ['type' => 'unlimited', 'daily_limit' => 999, 'features' => 'Unlimited Access'];
        $_SESSION['verified_code'] = $verifyCode;
        $_SESSION['verification_time'] = date('Y-m-d H:i:s');
        
        // Set success message
        $_SESSION['activation_message'] = "✅ License berhasil diaktifkan melalui verifikasi admin! Kode: " . $verifyCode;
        
        // Redirect to clean URL to avoid repeated activation
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    } else {
        $_SESSION['activation_message'] = "❌ Kode verifikasi tidak valid: " . htmlspecialchars($verifyCode);
    }
}

// Handle Dana manual payment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_payment') {
    $paymentConfig = SecureConfig::getPaymentConfig();
    $amount = $paymentConfig['license_price']; // Rp 50.000 untuk license premium
    
    $payment = generateDanaPaymentInfo($amount);
    
    if ($payment['success']) {
        // Create payment record for tracking
        createManualPaymentRecord($payment['unique_code'], $amount);
        
        $_SESSION['payment_data'] = $payment;
        $_SESSION['payment_unique_code'] = $payment['unique_code'];
        $_SESSION['payment_start_time'] = time();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'payment_method' => 'dana_manual',
            'dana_number' => $payment['dana_number'],
            'account_name' => $payment['account_name'],
            'amount' => $payment['amount'],
            'unique_code' => $payment['unique_code'],
            'expired_time' => $payment['expired_time'],
            'instructions' => $payment['instructions'],
            'admin_contact' => $paymentConfig['admin_contact']
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal membuat informasi pembayaran Dana',
            'error_details' => 'Silakan coba lagi atau hubungi admin'
        ]);
        exit;
    }
}

// Handle manual payment verification (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_payment') {
    $uniqueCode = $_POST['unique_code'] ?? '';
    $adminKey = $_POST['admin_key'] ?? '';
    
    // Simple admin verification (dalam implementasi nyata gunakan autentikasi yang lebih aman)
    if ($adminKey === 'TKB2025-ADMIN-VERIFY') {
        $_SESSION['license_valid'] = true;
        $_SESSION['license_info'] = ['type' => 'unlimited', 'daily_limit' => 999, 'features' => 'Unlimited Access'];
        
        // Process manual topup for Lollipop SMM
        $paymentConfig = SecureConfig::getPaymentConfig();
        $topupResult = processManualTopup($paymentConfig['topup_amount']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Pembayaran Dana berhasil diverifikasi! License unlimited diaktifkan.',
            'topup_status' => $topupResult
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Verifikasi pembayaran belum selesai. Silakan hubungi admin dengan bukti transfer.'
        ]);
        exit;
    }
}

// Handle manual license activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_license') {
    $licenseCode = strtoupper(trim($_POST['license_code'] ?? ''));
    
    if ($licenseCode === 'TKB2025-LICENSED') {
        $_SESSION['license_valid'] = true;
        $_SESSION['license_info'] = ['type' => 'unlimited', 'daily_limit' => 999, 'features' => 'Unlimited Access'];
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'License berhasil diaktifkan! Akses unlimited tersedia.'
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Kode license tidak valid. Hubungi admin untuk bantuan.'
        ]);
        exit;
    }
}

if (!isset($_SESSION['license_valid']) || $_SESSION['license_valid'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>TikTok View Booster - Premium Access</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    min-height: 100vh; 
                    padding: 10px;
                    overflow-x: hidden;
                }
                .welcome-container { 
                    background: white; 
                    border-radius: 20px; 
                    padding: 30px 20px; 
                    box-shadow: 0 20px 40px rgba(0,0,0,0.15); 
                    max-width: 400px; 
                    width: 100%; 
                    text-align: center;
                    margin: 20px auto;
                    position: relative;
                    animation: slideUp 0.6s ease-out;
                }
                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(30px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .logo { 
                    font-size: 2.5em; 
                    margin-bottom: 15px; 
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }
                .title { 
                    font-size: 1.6em; 
                    color: #2c3e50; 
                    margin-bottom: 8px; 
                    font-weight: 700;
                }
                .subtitle { 
                    color: #7f8c8d; 
                    margin-bottom: 25px; 
                    font-size: 14px; 
                    line-height: 1.5;
                }
                .features {
                    background: linear-gradient(135deg, #f8f9ff, #e8f3ff); 
                    border-radius: 15px; 
                    padding: 20px; 
                    margin: 20px 0;
                    text-align: left;
                    border: 1px solid #e1e8ed;
                    position: relative;
                    overflow: hidden;
                }
                .features::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, #667eea, #764ba2);
                }
                .features h3 { 
                    color: #2c3e50; 
                    margin-bottom: 15px; 
                    font-size: 1.2em;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .features h3::before {
                    content: "🚀";
                    font-size: 1.2em;
                }
                .features ul { list-style: none; }
                .features li { 
                    padding: 12px 0; 
                    color: #34495e;
                    border-bottom: 1px solid #ecf0f1;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 14px;
                }
                .features li:last-child { border-bottom: none; }
                .features li:before { 
                    content: "✨"; 
                    color: #27ae60; 
                    font-weight: bold; 
                    font-size: 16px;
                    min-width: 20px;
                }
                .payment-section {
                    margin-top: 25px;
                    text-align: center;
                }
                .price-badge {
                    background: linear-gradient(135deg, #e74c3c, #c0392b);
                    color: white;
                    padding: 8px 20px;
                    border-radius: 25px;
                    font-size: 18px;
                    font-weight: bold;
                    margin-bottom: 15px;
                    display: inline-block;
                    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
                }
                .btn-buy {
                    background: linear-gradient(135deg, #27ae60, #2ecc71);
                    color: white;
                    padding: 16px 30px;
                    border: none;
                    border-radius: 12px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 6px 20px rgba(39, 174, 96, 0.3);
                    width: 100%;
                    position: relative;
                    overflow: hidden;
                }
                .btn-buy::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                    transition: left 0.5s;
                }
                .btn-buy:hover::before {
                    left: 100%;
                }
                .btn-buy:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
                }
                .btn-buy:active {
                    transform: translateY(-1px);
                }
                .payment-modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.6);
                    backdrop-filter: blur(8px);
                    animation: fadeIn 0.3s ease;
                }
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                .payment-content {
                    background-color: white;
                    margin: 5vh auto;
                    padding: 0;
                    border-radius: 20px;
                    width: 95%;
                    max-width: 420px;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
                    animation: slideInUp 0.4s ease;
                    max-height: 90vh;
                    overflow-y: auto;
                }
                @keyframes slideInUp {
                    from { opacity: 0; transform: translateY(50px) scale(0.9); }
                    to { opacity: 1; transform: translateY(0) scale(1); }
                }
                }
                
                /* Mobile Responsive */
                @media (max-width: 480px) {
                    body {
                        padding: 5px;
                    }
                    .welcome-container {
                        padding: 25px 15px;
                        margin: 10px auto;
                        max-width: 95%;
                    }
                    .logo {
                        font-size: 2em;
                    }
                    .title {
                        font-size: 1.4em;
                    }
                    .subtitle {
                        font-size: 13px;
                    }
                    .features {
                        padding: 15px;
                    }
                    .features li {
                        font-size: 13px;
                        padding: 10px 0;
                    }
                    .btn-buy {
                        padding: 14px 25px;
                        font-size: 15px;
                    }
                    .payment-content {
                        width: 98%;
                        margin: 2vh auto;
                        max-height: 96vh;
                    }
                    .payment-header {
                        padding: 15px;
                    }
                    .payment-body {
                        padding: 20px 15px;
                    }
                }
                @keyframes modalShow {
                    from { opacity: 0; transform: translateY(-50px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .payment-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    background: linear-gradient(135deg, #007bff, #0056b3);
                    color: white;
                    border-radius: 15px 15px 0 0;
                }
                .payment-header h3 {
                    margin: 0;
                    font-size: 18px;
                }
                .close {
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                    line-height: 1;
                }
                .close:hover {
                    opacity: 0.7;
                }
                .payment-body {
                    padding: 30px;
                    text-align: center;
                }
                .qr-container {
                    margin: 20px 0;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 10px;
                    border: 2px dashed #007bff;
                }
                .qr-container img {
                    max-width: 100%;
                    height: auto;
                    border-radius: 8px;
                }
                .payment-info {
                    background: #e3f2fd;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 15px 0;
                }
                .payment-info p {
                    margin: 5px 0;
                    font-size: 14px;
                }
                .payment-status {
                    margin-top: 20px;
                    padding: 15px;
                    background: #fff3cd;
                    border-radius: 8px;
                    border-left: 4px solid #ffc107;
                }
                .loading-payment {
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    color: #666;
                    font-size: 14px;
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="welcome-container">
                <div class="logo">🚀</div>
                <h1 class="title">TikTok View Booster</h1>
                <p class="subtitle">Tingkatkan Views TikTok Anda dengan Mudah & Cepat</p>
                
                <?php if (isset($_SESSION['activation_message'])): ?>
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        <h4 style="margin: 0 0 10px 0;">🎉 Aktivasi Berhasil!</h4>
                        <p style="margin: 0;"><?php echo $_SESSION['activation_message']; ?></p>
                        <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.8;">Sekarang Anda memiliki akses unlimited untuk semua layanan!</p>
                    </div>
                    <?php unset($_SESSION['activation_message']); ?>
                <?php endif; ?>
                
                <div class="features">
                    <h3>Premium Services</h3>
                    <ul>
                        <li>TikTok Views - 1000+ per boost tanpa batas</li>
                        <li>TikTok Followers - 500+ per boost tanpa batas</li>
                        <li>TikTok Likes - 1000+ per boost tanpa batas</li>
                        <li>Akses Unlimited 24/7 Selamanya</li>
                        <li>Proses Cepat & Aman Terjamin</li>
                        <li>Support Admin via WhatsApp</li>
                    </ul>
                </div>
                
                <div class="payment-section">
                    <div class="price-badge">💰 Rp 50.000 Only!</div>
                    <button type="button" class="btn-buy" onclick="buyLicense()">
                        🚀 Aktifkan Premium Sekarang
                    </button>
                    <div style="margin-top: 12px; font-size: 13px; color: #7f8c8d;">
                        💳 Pembayaran via Dana E-wallet • 🔒 100% Aman
                    </div>
                </div>
                
                <div class="license-info" style="margin-top: 20px; padding: 20px; background: linear-gradient(135deg, #e8f4fd, #d1ecf1); border-radius: 15px; border: 2px solid #b3e5fc; box-shadow: 0 4px 15px rgba(0,123,255,0.1);">
                    <h4 style="margin: 0 0 15px 0; color: #0277bd; text-align: center; font-size: 16px;">
                        💰 Cara Pembayaran Dana E-wallet
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr; gap: 12px; margin: 15px 0;">
                        <div style="background: white; padding: 15px; border-radius: 10px; border: 1px solid #e3f2fd; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <div style="font-size: 24px; margin-bottom: 5px;">📱</div>
                            <strong style="color: #1976d2;">Dana Transfer</strong><br>
                            <span style="color: #333; font-size: 18px; font-weight: 600;">6289663596711</span><br>
                            <small style="color: #666;">TikTok Booster Admin</small>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.7); padding: 15px; border-radius: 10px; margin: 15px 0; border-left: 4px solid #4caf50;">
                        <div style="font-size: 14px; color: #2e7d32; line-height: 1.6;">
                            <div style="margin-bottom: 8px;"><strong>📋 Langkah Mudah:</strong></div>
                            <div style="margin-bottom: 5px;">1️⃣ Transfer Rp 50.000 ke Dana</div>
                            <div style="margin-bottom: 5px;">2️⃣ Screenshot bukti transfer</div>
                            <div style="margin-bottom: 5px;">3️⃣ Kirim ke admin WhatsApp</div>
                            <div>4️⃣ License unlimited aktif!</div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="https://wa.me/6289663596711?text=Halo%20admin,%20saya%20mau%20beli%20license%20premium%20TikTok%20Booster" 
                           style="display: inline-block; background: linear-gradient(135deg, #25d366, #128c7e); color: white; padding: 12px 20px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; box-shadow: 0 4px 15px rgba(37,211,102,0.3);">
                            💬 Chat Admin WhatsApp
                        </a>
                    </div>
                </div>
                
                <!-- Payment Modal -->
                <div id="payment-modal" class="payment-modal" style="display: none;">
                    <div class="payment-content">
                        <div class="payment-header">
                            <h3>Premium License - Dana E-wallet</h3>
                            <span class="close" onclick="closePayment()">&times;</span>
                        </div>
                        <div class="payment-body">
                            <div id="dana-section" style="display: none;">
                                <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                    <h4 style="margin: 0 0 10px 0; color: #2d5a2d;">💰 Detail Pembayaran</h4>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span>License Premium:</span><span id="payment-amount">Rp 50.000</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span>Metode:</span><span>Dana E-wallet</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span>Nomor Tujuan:</span><span id="dana-number">89663596711</span>
                                    </div>
                                </div>
                                
                                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 20px;">
                                    <h4 style="margin: 0 0 10px 0; color: #856404;">📋 Langkah Pembayaran:</h4>
                                    <ol id="payment-instructions" style="margin: 0; padding-left: 20px; color: #856404;">
                                        <!-- Instructions will be populated by JavaScript -->
                                    </ol>
                                </div>
                                
                                <div class="payment-info" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                    <p><strong>Kode Transaksi:</strong> <span id="payment-code" style="color: #007bff; font-family: monospace;"></span></p>
                                    <p><strong>Berlaku sampai:</strong> <span id="payment-expired"></span></p>
                                    
                                    <div style="background: #e8f5e8; padding: 12px; border-radius: 6px; margin: 15px 0; font-size: 13px; line-height: 1.4;">
                                        <strong>🎯 Cara Aktivasi Cepat:</strong><br>
                                        1. Transfer Dana sesuai nominal<br>
                                        2. Klik "Kirim Bukti Transfer" → kirim screenshot<br>
                                        3. Klik "Link Verifikasi Admin" → kirim ke admin<br>
                                        4. Admin klik link → License otomatis aktif!
                                    </div>
                                    
                                    <div style="margin-top: 15px;">
                                        <a href="" id="whatsapp-contact" target="_blank" style="display: inline-block; padding: 10px 20px; background: #25d366; color: white; text-decoration: none; border-radius: 5px;">
                                            📱 Kirim Bukti Transfer
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div id="loading-payment" style="display: block;">
                                <div class="spinner"></div>
                                <p>Membuat informasi pembayaran...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- License activation form -->
                <div class="license-activation" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; display: none;" id="licenseForm">
                    <h4>Aktivasi Manual License</h4>
                    <p>Masukkan kode license yang diberikan admin:</p>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <input type="text" id="licenseCode" placeholder="TKB2025-XXXXXXX" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <button onclick="activateLicense()" style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Aktivasi</button>
                    </div>
                    <div id="activationResult" style="margin-top: 10px;"></div>
                </div>
            </div>
            
            <script>
                let currentUniqueCode = '';
                
                function buyLicense() {
                    document.getElementById('payment-modal').style.display = 'block';
                    document.getElementById('loading-payment').style.display = 'block';
                    document.getElementById('dana-section').style.display = 'none';
                    
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=create_payment'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            currentUniqueCode = data.unique_code;
                            document.getElementById('loading-payment').style.display = 'none';
                            document.getElementById('dana-section').style.display = 'block';
                            
                            // Populate payment info
                            document.getElementById('payment-amount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.amount);
                            document.getElementById('dana-number').textContent = data.dana_number;
                            document.getElementById('payment-code').textContent = data.unique_code;
                            document.getElementById('payment-expired').textContent = data.expired_time;
                            
                            // Populate instructions
                            const instructionsList = document.getElementById('payment-instructions');
                            instructionsList.innerHTML = '';
                            data.instructions.forEach(instruction => {
                                const li = document.createElement('li');
                                li.textContent = instruction;
                                li.style.marginBottom = '5px';
                                instructionsList.appendChild(li);
                            });
                            
                            // Set WhatsApp contact link for user
                            const whatsappMessage = `Halo Admin, saya sudah transfer Dana Rp 50.000 untuk license premium TikTok View Booster. Kode transaksi: ${data.unique_code}. Berikut bukti transfernya:`;
                            const whatsappUrl = `${data.admin_contact}?text=${encodeURIComponent(whatsappMessage)}`;
                            document.getElementById('whatsapp-contact').href = whatsappUrl;
                            
                            // Create verification link for admin
                            const verificationLink = `${window.location.origin}${window.location.pathname}?verify=${data.unique_code}`;
                            const adminMessage = `✅ VERIFIKASI PEMBAYARAN DANA\\n\\nUser telah transfer Rp 50.000\\nKode: ${data.unique_code}\\n\\n🔗 Klik link ini untuk aktivasi:\\n${verificationLink}`;
                            
                            // Add admin verification button
                            const adminButton = document.createElement('a');
                            adminButton.href = `${data.admin_contact}?text=${encodeURIComponent(adminMessage)}`;
                            adminButton.target = '_blank';
                            adminButton.style.cssText = 'display: inline-block; margin-left: 10px; padding: 10px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px; font-size: 14px;';
                            adminButton.textContent = '🔗 Link Verifikasi Admin';
                            document.getElementById('whatsapp-contact').parentNode.appendChild(adminButton);
                            
                        } else {
                            document.getElementById('loading-payment').style.display = 'none';
                            document.querySelector('.payment-body').innerHTML = `
                                <div style="text-align: center; padding: 20px;">
                                    <h4 style="color: red;">❌ Gagal Membuat Info Pembayaran</h4>
                                    <p>${data.message}</p>
                                    <p style="font-size: 14px; color: #666;">${data.error_details}</p>
                                    <button onclick="closePayment()" style="margin-top: 15px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Tutup</button>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('loading-payment').style.display = 'none';
                        document.querySelector('.payment-body').innerHTML = `
                            <div style="text-align: center; padding: 20px;">
                                <h4 style="color: red;">❌ Kesalahan Jaringan</h4>
                                <p>Terjadi kesalahan saat membuat info pembayaran. Silakan coba lagi.</p>
                                <button onclick="closePayment()" style="margin-top: 15px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Tutup</button>
                            </div>
                        `;
                    });
                }
                
                function closePayment() {
                    document.getElementById('payment-modal').style.display = 'none';
                    currentUniqueCode = '';
                }
                
                function contactAdmin() {
                    const message = "Halo Admin, saya ingin mengaktifkan license premium TikTok View Booster untuk akses unlimited. Bagaimana cara top up balance melalui Lollipop SMM?";
                    const whatsappUrl = `https://wa.me/6289663596711?text=${encodeURIComponent(message)}`;
                    window.open(whatsappUrl, '_blank');
                    
                    document.getElementById('licenseForm').style.display = 'block';
                }
                
                function activateLicense() {
                    const licenseCode = document.getElementById('licenseCode').value.trim().toUpperCase();
                    const resultDiv = document.getElementById('activationResult');
                    
                    if (!licenseCode) {
                        resultDiv.innerHTML = '<p style="color: red;">Masukkan kode license terlebih dahulu!</p>';
                        return;
                    }
                    
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=activate_license&license_code=${licenseCode}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            resultDiv.innerHTML = '<p style="color: green;">✅ ' + data.message + '</p>';
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            resultDiv.innerHTML = '<p style="color: red;">❌ ' + data.message + '</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        resultDiv.innerHTML = '<p style="color: red;">Terjadi kesalahan koneksi!</p>';
                    });
                }
                
                // Close modal when clicking outside
                window.onclick = function(event) {
                    const modal = document.getElementById('payment-modal');
                    if (event.target === modal) {
                        closePayment();
                    }
                }
            </script>
        </body>
        </html>
        <?php
        exit;
}

$userIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userIP = explode(',', $userIP)[0];

if (!SecureConfig::validateRequest($userIP)) {
    $response = [
        'success' => false,
        'message' => 'Akses ditolak. Gunakan koneksi internet biasa.',
        'error' => 'Invalid IP or VPN detected'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $videoUrl = $input['url'] ?? '';
    $serviceType = $input['service_type'] ?? 'views';
    
    // Validate URL based on service type
    if ($serviceType === 'followers') {
        if (!preg_match('/^https:\/\/(www\.)?tiktok\.com\/@[a-zA-Z0-9._]+\/?$/', $videoUrl)) {
            $response = [
                'success' => false,
                'message' => 'URL profil TikTok tidak valid. Gunakan format: https://www.tiktok.com/@username',
                'error' => 'Invalid TikTok profile URL format'
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    } else {
        if (!validateTikTokUrl($videoUrl)) {
            $response = [
                'success' => false,
                'message' => 'URL video TikTok tidak valid. Gunakan format: https://vt.tiktok.com/xxx',
                'error' => 'Invalid TikTok video URL format'
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
    
    $limitCheck = checkDailyLimit($userIP);
    if (!$limitCheck['canBoost']) {
        $response = [
            'success' => false,
            'message' => "Limit harian tercapai! Anda sudah boost {$limitCheck['boostsToday']} video hari ini. Coba lagi besok.",
            'data' => [
                'boostsToday' => $limitCheck['boostsToday'],
                'boostsRemaining' => 0,
                'status' => 'limit_reached'
            ]
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Get service configuration
    $serviceConfig = [
        'views' => ['id' => $apiConfig['services']['views'], 'quantity' => 1000, 'name' => 'Views'],
        'followers' => ['id' => $apiConfig['services']['followers'], 'quantity' => 500, 'name' => 'Followers'],
        'likes' => ['id' => $apiConfig['services']['likes'], 'quantity' => 1000, 'name' => 'Likes']
    ];
    
    $currentService = $serviceConfig[$serviceType];
    $encryptedUrl = SecureConfig::encryptUrl($videoUrl);
    
    $stmt = $pdo->prepare("INSERT INTO boosts (url_encrypted, service_id, service_type, ip_address, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$encryptedUrl, $currentService['id'], $serviceType, $userIP]);
    $boostId = $pdo->lastInsertId();
    
    $startTime = microtime(true);
    $apiResult = callSecureAPI('', [
        'service' => $currentService['id'],
        'link' => $videoUrl,
        'quantity' => $currentService['quantity']
    ]);
    $processingTime = round((microtime(true) - $startTime), 2) . 's';
    
    if ($apiResult['httpCode'] === 200 && isset($apiResult['data']['order'])) {
        $orderId = $apiResult['data']['order'];
        $stmt = $pdo->prepare("UPDATE boosts SET order_id = ?, status = 'completed', views_added = ?, processing_time = ? WHERE id = ?");
        $stmt->execute([$orderId, $currentService['quantity'], $processingTime, $boostId]);
        
        $serviceMessage = [
            'views' => 'Views akan bertambah dalam 1-5 menit.',
            'followers' => 'Followers akan bertambah dalam 5-15 menit.',
            'likes' => 'Likes akan bertambah dalam 1-3 menit.'
        ];
        
        $response = [
            'success' => true,
            'message' => "Boost {$currentService['name']} berhasil! {$serviceMessage[$serviceType]}",
            'data' => [
                'viewsAdded' => $currentService['quantity'],
                'serviceType' => $serviceType,
                'serviceName' => $currentService['name'],
                'status' => 'completed',
                'processingTime' => $processingTime,
                'orderId' => $orderId,
                'boostsToday' => $limitCheck['boostsToday'] + 1,
                'boostsRemaining' => $limitCheck['boostsRemaining'] - 1,
                'dailyLimit' => $dailyLimit
            ]
        ];
    } else {
        $stmt = $pdo->prepare("UPDATE boosts SET status = 'failed', processing_time = ?, views_added = ? WHERE id = ?");
        $stmt->execute([$processingTime, $currentService['quantity'], $boostId]);
        
        $response = [
            'success' => true,
            'message' => "Mode Demo: Boost {$currentService['name']} simulasi berhasil! Untuk boost real, admin perlu top up saldo API.",
            'data' => [
                'viewsAdded' => $currentService['quantity'],
                'serviceType' => $serviceType,
                'serviceName' => $currentService['name'],
                'status' => 'demo_mode',
                'processingTime' => $processingTime,
                'boostsToday' => $limitCheck['boostsToday'] + 1,
                'boostsRemaining' => $limitCheck['boostsRemaining'] - 1,
                'dailyLimit' => $dailyLimit
            ]
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['stats'])) {
    $stats = getTodayStats();
    header('Content-Type: application/json');
    echo json_encode($stats);
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?> - Versi Aman</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
        }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .card { 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            margin: 20px 0; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }
        .header { text-align: center; color: white; margin-bottom: 30px; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.2em; opacity: 0.9; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        input[type="url"] { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #ddd; 
            border-radius: 8px; 
            font-size: 16px; 
        }
        input[type="url"]:focus, select:focus { outline: none; border-color: #667eea; }
        select { 
            cursor: pointer; 
            background: white;
        }
        .url-help { 
            color: #666; 
            font-size: 12px; 
            margin-top: 5px; 
            display: block; 
        }
        .btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 12px 30px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold; 
            width: 100%; 
            transition: all 0.3s ease;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(0,0,0,0.2); 
        }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 15px; 
            margin-top: 20px; 
        }
        .stat-card { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center; 
        }
        .stat-number { font-size: 2em; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; margin-top: 5px; }
        .result { margin-top: 20px; padding: 15px; border-radius: 8px; }
        .result.success { 
            background: #d4edda; 
            border: 1px solid #c3e6cb; 
            color: #155724; 
        }
        .result.error { 
            background: #f8d7da; 
            border: 1px solid #f5c6cb; 
            color: #721c24; 
        }
        .security-info { 
            background: #e7f3ff; 
            border: 1px solid #b8daff; 
            color: #004085; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 20px; 
        }
        .loading { display: none; text-align: center; margin: 20px 0; }
        .spinner { 
            border: 4px solid #f3f3f3; 
            border-top: 4px solid #667eea; 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            animation: spin 1s linear infinite; 
            margin: 0 auto; 
        }
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        .encryption-status {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .service-breakdown h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        .breakdown-stats {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        .breakdown-item {
            flex: 1;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }
        .breakdown-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .breakdown-value {
            display: block;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 <?= $appName ?> - Licensed</h1>
            <p>Versi Aman dengan Enkripsi AES-256 - Unlimited Access</p>
        </div>

        <div class="card">
            <h2>TikTok Boost Services</h2>

            

            

            
            <form id="boostForm">
                <div class="form-group">
                    <label for="serviceType">Pilih Layanan:</label>
                    <select id="serviceType" required onchange="updateUrlPlaceholder()">
                        <option value="">-- Pilih Layanan --</option>
                        <option value="views">TikTok Views (+1000)</option>
                        <option value="followers">TikTok Followers (+500)</option>
                        <option value="likes">TikTok Likes (+1000)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="videoUrl" id="urlLabel">URL TikTok:</label>
                    <input type="url" id="videoUrl" placeholder="Pilih layanan terlebih dahulu" required>
                    <small id="urlHelp" class="url-help">Pilih jenis layanan untuk melihat format URL yang diperlukan</small>
                </div>
                
                <button type="submit" class="btn" id="submitBtn" disabled>Pilih Layanan Terlebih Dahulu</button>
            </form>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Memproses boost dengan enkripsi...</p>
            </div>
            
            <div id="result"></div>
        </div>

        <div class="card">
            <h2>Statistik Hari Ini</h2>
            <div class="stats" id="stats">
                <div class="stat-card">
                    <div class="stat-number" id="videosToday">0</div>
                    <div class="stat-label">Total Boost</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalViews">0</div>
                    <div class="stat-label">Total Engagement</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="successRate">0%</div>
                    <div class="stat-label">Tingkat Sukses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="avgTime">0s</div>
                    <div class="stat-label">Rata-rata Waktu</div>
                </div>
            </div>
            
            <div class="service-breakdown" id="serviceBreakdown" style="margin-top: 20px; display: none;">
                <h3>Breakdown Layanan</h3>
                <div class="breakdown-stats">
                    <div class="breakdown-item">
                        <span class="breakdown-label">Views:</span>
                        <span class="breakdown-value" id="viewsCount">0</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Followers:</span>
                        <span class="breakdown-value" id="followersCount">0</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="breakdown-label">Likes:</span>
                        <span class="breakdown-value" id="likesCount">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateUrlPlaceholder() {
            const serviceType = document.getElementById('serviceType').value;
            const urlInput = document.getElementById('videoUrl');
            const urlLabel = document.getElementById('urlLabel');
            const urlHelp = document.getElementById('urlHelp');
            const submitBtn = document.getElementById('submitBtn');
            
            if (serviceType === 'views' || serviceType === 'likes') {
                urlLabel.textContent = 'URL Video TikTok:';
                urlInput.placeholder = 'https://vt.tiktok.com/ZSxxxxxxx';
                urlHelp.textContent = 'Contoh: https://vt.tiktok.com/ZSxxxxxxx atau https://www.tiktok.com/@user/video/123456789';
                submitBtn.textContent = serviceType === 'views' ? 'Boost Views (+1000) - UNLIMITED' : 'Boost Likes (+1000) - UNLIMITED';
                submitBtn.disabled = false;
            } else if (serviceType === 'followers') {
                urlLabel.textContent = 'URL Profil TikTok:';
                urlInput.placeholder = 'https://www.tiktok.com/@username';
                urlHelp.textContent = 'Contoh: https://www.tiktok.com/@username (tanpa video, hanya profil)';
                submitBtn.textContent = 'Boost Followers (+500) - UNLIMITED';
                submitBtn.disabled = false;
            } else {
                urlInput.placeholder = 'Pilih layanan terlebih dahulu';
                urlHelp.textContent = 'Pilih jenis layanan untuk melihat format URL yang diperlukan';
                submitBtn.textContent = 'Pilih Layanan Terlebih Dahulu';
                submitBtn.disabled = true;
            }
            
            urlInput.value = '';
        }

        function loadStats() {
            fetch('?stats=1')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('videosToday').textContent = data.videosToday;
                    document.getElementById('totalViews').textContent = data.totalViews.toLocaleString();
                    document.getElementById('successRate').textContent = data.successRate + '%';
                    document.getElementById('avgTime').textContent = data.avgTime;
                    
                    if (data.breakdown && data.videosToday > 0) {
                        document.getElementById('serviceBreakdown').style.display = 'block';
                        document.getElementById('viewsCount').textContent = data.breakdown.views;
                        document.getElementById('followersCount').textContent = data.breakdown.followers;
                        document.getElementById('likesCount').textContent = data.breakdown.likes;
                    }
                })
                .catch(error => console.log('Stats load error:', error));
        }

        document.getElementById('boostForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const url = document.getElementById('videoUrl').value;
            const serviceType = document.getElementById('serviceType').value;
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            const submitBtn = document.querySelector('#submitBtn');
            
            if (!serviceType) {
                alert('Pilih layanan terlebih dahulu!');
                return;
            }
            
            loading.style.display = 'block';
            result.innerHTML = '';
            submitBtn.disabled = true;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    url: url,
                    service_type: serviceType 
                })
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                submitBtn.disabled = false;
                
                if (data.success) {
                    const serviceDisplay = {
                        'views': 'Views',
                        'followers': 'Followers', 
                        'likes': 'Likes'
                    };
                    
                    const serviceLabel = serviceDisplay[data.data.serviceType] || 'Views';
                    
                    result.innerHTML = `
                        <div class="result success">
                            <strong>✅ ${data.message}</strong><br>
                            ${serviceLabel} ditambahkan: ${data.data.viewsAdded.toLocaleString()}<br>
                            Waktu proses: ${data.data.processingTime}<br>
                            Status: ${data.data.status}<br>
                            Boost hari ini: ${data.data.boostsToday}/${data.data.dailyLimit}<br>
                            Sisa boost: ${data.data.boostsRemaining}
                        </div>
                    `;
                    document.getElementById('videoUrl').value = '';
                    loadStats();
                } else {
                    result.innerHTML = `
                        <div class="result error">
                            <strong>❌ ${data.message}</strong>
                        </div>
                    `;
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                submitBtn.disabled = false;
                result.innerHTML = `
                    <div class="result error">
                        <strong>❌ Terjadi kesalahan sistem</strong>
                    </div>
                `;
            });
        });

        loadStats();
        setInterval(loadStats, 5000);
    </script>
</body>
</html>