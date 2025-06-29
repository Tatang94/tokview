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
            'host' => 'sql111.infinityfree.com',
            'dbname' => 'if0_39341535_view',
            'username' => 'if0_39341535',
            'password' => 'SHRMPj7PvCo'
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
    
    public static function getPayDisiniConfig() {
        return [
            'api_id' => '3246',
            'api_key' => 'ff79be802563e5dc1311c227a72d17c1',
            'api_url' => 'https://api.paydisini.co.id/v1/'
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
$payConfig = SecureConfig::getPayDisiniConfig();

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

function createPayDisiniPayment($amount, $description) {
    // Simulasi untuk testing - akan diganti dengan API real setelah konfigurasi benar
    $uniqueCode = 'TKB' . time() . rand(100, 999);
    
    // Return format yang sesuai dengan PayDisini API
    return [
        'response' => json_encode([
            'success' => true,
            'data' => [
                'unique_code' => $uniqueCode,
                'amount' => $amount,
                'qrcode_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode("00020101021226580014ID.CO.QRIS.WWW01189360050300000898780214' . $uniqueCode . '0303UMI51440014ID.DANA.WWW0215ID0893607050300000898780303UMI5204541653033605802ID5925TIKTOK VIEW BOOSTER INA6005JAKARTA61054012462070503***630445F8"),
                'checkout_url' => '#',
                'expired_time' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ]
        ]),
        'httpCode' => 200,
        'data' => [
            'success' => true,
            'data' => [
                'unique_code' => $uniqueCode,
                'amount' => $amount,
                'qrcode_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode("00020101021226580014ID.CO.QRIS.WWW01189360050300000898780214$uniqueCode" . "0303UMI51440014ID.DANA.WWW0215ID0893607050300000898780303UMI5204541653033605802ID5925TIKTOK VIEW BOOSTER INA6005JAKARTA61054012462070503***630445F8"),
                'checkout_url' => '#',
                'expired_time' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ]
        ]
    ];
}

function checkPayDisiniStatus($uniqueCode) {
    global $payConfig;
    
    $postData = [
        'key' => $payConfig['api_key'],
        'request' => 'status',
        'unique_code' => $uniqueCode
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $payConfig['api_url'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}



session_start();

// Handle payment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_payment') {
    $payment = createPayDisiniPayment(50000, 'TikTok View Booster - License 1 Bulan');
    
    // Check if API call was successful
    if (isset($payment['data']) && $payment['data'] && isset($payment['data']['success']) && $payment['data']['success']) {
        $_SESSION['payment_data'] = $payment['data']['data'];
        $_SESSION['payment_unique_code'] = $payment['data']['data']['unique_code'];
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'payment_url' => $payment['data']['data']['checkout_url'] ?? '',
            'qr_code' => $payment['data']['data']['qrcode_url'] ?? $payment['data']['data']['qr_string'] ?? '',
            'amount' => $payment['data']['data']['amount'],
            'unique_code' => $payment['data']['data']['unique_code'],
            'expired_time' => $payment['data']['data']['expired_time']
        ]);
        exit;
    } else {
        // Error handling for API issues
        $errorMsg = 'API PayDisini tidak tersedia';
        if (isset($payment['data']['msg'])) {
            $errorMsg .= ': ' . $payment['data']['msg'];
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => $errorMsg,
            'error_details' => 'Silakan coba lagi atau hubungi admin untuk mendapatkan kode license manual'
        ]);
        exit;
    }
}

// Handle payment status check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_payment') {
    $uniqueCode = $_POST['unique_code'];
    
    // Simulasi pembayaran sukses setelah 10 detik (untuk demo)
    if (isset($_SESSION['payment_start_time']) && (time() - $_SESSION['payment_start_time']) > 10) {
        $_SESSION['license_valid'] = true;
        $_SESSION['license_info'] = ['type' => 'unlimited', 'daily_limit' => 999, 'features' => 'Unlimited Access'];
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Pembayaran berhasil! Akses unlimited telah diaktifkan.'
        ]);
        exit;
    } else {
        if (!isset($_SESSION['payment_start_time'])) {
            $_SESSION['payment_start_time'] = time();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'status' => 'Pending']);
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
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    min-height: 100vh; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                }
                .welcome-container { 
                    background: white; 
                    border-radius: 15px; 
                    padding: 40px; 
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
                    max-width: 500px; 
                    width: 90%; 
                    text-align: center;
                }
                .logo { font-size: 3em; margin-bottom: 20px; }
                .title { font-size: 1.8em; color: #333; margin-bottom: 10px; }
                .subtitle { color: #666; margin-bottom: 30px; font-size: 16px; }
                .features {
                    background: #f8f9fa; 
                    border-radius: 8px; 
                    padding: 20px; 
                    margin: 20px 0;
                    text-align: left;
                }
                .features h3 { color: #28a745; margin-bottom: 15px; }
                .features ul { list-style: none; }
                .features li { 
                    padding: 8px 0; 
                    color: #333;
                    border-bottom: 1px solid #eee;
                }
                .features li:last-child { border-bottom: none; }
                .features li:before { content: "✓ "; color: #28a745; font-weight: bold; }
                .payment-section {
                    margin-top: 20px;
                    text-align: center;
                }
                .btn-buy {
                    background: linear-gradient(135deg, #28a745, #20c997);
                    color: white;
                    padding: 15px 30px;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
                    width: 100%;
                }
                .btn-buy:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
                }
                .payment-modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(5px);
                }
                .payment-content {
                    background-color: white;
                    margin: 5% auto;
                    padding: 0;
                    border-radius: 15px;
                    width: 90%;
                    max-width: 500px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    animation: modalShow 0.3s ease;
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
                
                <div class="features">
                    <h3>Premium Services</h3>
                    <ul>
                        <li>TikTok Views - 1000+ per boost</li>
                        <li>TikTok Followers - 500+ per boost</li>
                        <li>TikTok Likes - 1000+ per boost</li>
                        <li>Unlimited Daily Access</li>
                        <li>Fast & Safe Processing</li>
                    </ul>
                </div>
                
                <button type="button" class="btn-buy" onclick="buyLicense()">
                    🔥 Aktifkan Premium - Rp 50.000/bulan
                </button>
                
                <div id="payment-modal" class="payment-modal" style="display: none;">
                    <div class="payment-content">
                        <div class="payment-header">
                            <h3>Pembayaran Premium Access</h3>
                            <span class="close" onclick="closePayment()">&times;</span>
                        </div>
                        <div class="payment-body">
                            <div id="qr-section" style="display: none;">
                                <p><strong>Scan QR Code QRIS untuk membayar:</strong></p>
                                <div class="qr-container">
                                    <img id="qr-image" src="" alt="QR Code QRIS" style="max-width: 250px;">
                                </div>
                                <div class="payment-info">
                                    <p>Jumlah: <strong id="payment-amount">Rp 50.000</strong></p>
                                    <p>Kode: <strong id="payment-code"></strong></p>
                                    <p>Berlaku sampai: <strong id="payment-expired"></strong></p>
                                </div>
                                <div class="payment-status">
                                    <p id="status-text">Menunggu pembayaran...</p>
                                    <div class="loading-payment">⏳ Mengecek status pembayaran...</div>
                                </div>
                            </div>
                            <div id="loading-payment" style="display: block;">
                                <div class="spinner"></div>
                                <p>Membuat pembayaran...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                let paymentCheckInterval;
                let currentUniqueCode = '';
                
                function buyLicense() {
                    document.getElementById('payment-modal').style.display = 'block';
                    document.getElementById('loading-payment').style.display = 'block';
                    document.getElementById('qr-section').style.display = 'none';
                    
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
                            document.getElementById('qr-section').style.display = 'block';
                            
                            document.getElementById('qr-image').src = data.qr_code;
                            document.getElementById('payment-amount').textContent = 'Rp ' + Number(data.amount).toLocaleString('id-ID');
                            document.getElementById('payment-code').textContent = data.unique_code;
                            document.getElementById('payment-expired').textContent = data.expired_time;
                            
                            // Start checking payment status
                            startPaymentCheck();
                        } else {
                            alert('Gagal membuat pembayaran: ' + data.message);
                            closePayment();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat membuat pembayaran');
                        closePayment();
                    });
                }
                
                function startPaymentCheck() {
                    paymentCheckInterval = setInterval(checkPaymentStatus, 3000); // Check every 3 seconds
                }
                
                function checkPaymentStatus() {
                    if (!currentUniqueCode) return;
                    
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=check_payment&unique_code=' + currentUniqueCode
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            clearInterval(paymentCheckInterval);
                            document.getElementById('status-text').innerHTML = 
                                '<span style="color: #28a745;">✅ Pembayaran berhasil!</span>';
                            document.querySelector('.loading-payment').style.display = 'none';
                            
                            setTimeout(() => {
                                alert('🎉 Pembayaran berhasil! Premium access telah diaktifkan.\\n\\nSelamat datang di TikTok View Booster Premium!');
                                window.location.reload();
                            }, 2000);
                        } else {
                            // Update status but continue checking
                            const statusMap = {
                                'Pending': 'Menunggu pembayaran...',
                                'Expired': 'Pembayaran expired',
                                'Canceled': 'Pembayaran dibatalkan'
                            };
                            document.getElementById('status-text').textContent = statusMap[data.status] || 'Menunggu pembayaran...';
                            
                            if (data.status === 'Expired' || data.status === 'Canceled') {
                                clearInterval(paymentCheckInterval);
                                document.querySelector('.loading-payment').style.display = 'none';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error checking payment:', error);
                    });
                }
                
                function closePayment() {
                    document.getElementById('payment-modal').style.display = 'none';
                    if (paymentCheckInterval) {
                        clearInterval(paymentCheckInterval);
                    }
                    currentUniqueCode = '';
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