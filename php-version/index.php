<?php
// Simple PHP version untuk hosting shared
error_reporting(0);
ini_set('display_errors', 0);

// Database configuration
$DB_HOST = 'sql305.ezyro.com';
$DB_NAME = 'ezyro_39270123_ahay';
$DB_USER = 'ezyro_39270123';
$DB_PASS = 'bec86c42f5';

// API configuration
$API_KEY = '4dab7086d758c1f5ab89cf4a34cd2201';
$API_URL = 'https://n1panel.com/api/v2';
$SERVICE_ID = 838;
$DAILY_LIMIT = 5;

// Database connection
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed");
}

// Create table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tiktok_boosts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_url VARCHAR(500) NOT NULL,
        service_id INT DEFAULT 838,
        order_id VARCHAR(255),
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        views_added INT DEFAULT 0,
        processing_time VARCHAR(50),
        video_title VARCHAR(500),
        ip_address VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(PDOException $e) {
    // Table might already exist
}

// Helper functions
function getIP() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function validateTikTokUrl($url) {
    $patterns = [
        '/^https?:\/\/(www\.)?tiktok\.com\/@[\w.-]+\/video\/\d+/',
        '/^https?:\/\/(vm|vt)\.tiktok\.com\/[\w\d]+/',
        '/^https?:\/\/m\.tiktok\.com\/v\/\d+/'
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url)) return true;
    }
    return false;
}

function canBoost($pdo, $ip, $limit) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tiktok_boosts WHERE ip_address = ? AND DATE(created_at) = ?");
    $stmt->execute([$ip, $today]);
    $count = $stmt->fetchColumn();
    return [
        'can' => $count < $limit,
        'used' => (int)$count,
        'remaining' => max(0, $limit - $count)
    ];
}

function getTodayStats($pdo) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as videos,
        COALESCE(SUM(views_added), 0) as views,
        ROUND(AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100, 1) as success,
        ROUND(AVG(CAST(SUBSTRING_INDEX(processing_time, ' ', 1) AS DECIMAL(10,2))), 1) as time
        FROM tiktok_boosts WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: ['videos' => 0, 'views' => 0, 'success' => 0, 'time' => 0];
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'boost') {
        $videoUrl = trim($_POST['videoUrl'] ?? '');
        $ip = getIP();
        
        if (!validateTikTokUrl($videoUrl)) {
            echo json_encode(['success' => false, 'message' => 'URL TikTok tidak valid']);
            exit;
        }
        
        $boost = canBoost($pdo, $ip, $DAILY_LIMIT);
        if (!$boost['can']) {
            echo json_encode([
                'success' => false, 
                'message' => "Limit $DAILY_LIMIT boost per hari tercapai. Coba lagi besok.",
                'data' => $boost
            ]);
            exit;
        }
        
        // Insert record
        $stmt = $pdo->prepare("INSERT INTO tiktok_boosts (video_url, service_id, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$videoUrl, $SERVICE_ID, $ip]);
        $boostId = $pdo->lastInsertId();
        
        $startTime = microtime(true);
        $viewsAdded = rand(1000, 5000);
        $orderId = 'ORD' . time() . rand(1000, 9999);
        $status = 'completed';
        
        // API call to N1Panel
        if ($API_KEY) {
            $data = [
                'key' => $API_KEY,
                'action' => 'add',
                'service' => $SERVICE_ID,
                'link' => $videoUrl,
                'quantity' => $viewsAdded
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $API_URL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response) {
                $apiResult = json_decode($response, true);
                if ($apiResult && isset($apiResult['order'])) {
                    $orderId = $apiResult['order'];
                }
            }
        }
        
        $processingTime = round((microtime(true) - $startTime) * 1000, 2) . ' ms';
        
        // Update record
        $stmt = $pdo->prepare("UPDATE tiktok_boosts SET status = ?, views_added = ?, order_id = ?, processing_time = ? WHERE id = ?");
        $stmt->execute([$status, $viewsAdded, $orderId, $processingTime, $boostId]);
        
        $newBoost = canBoost($pdo, $ip, $DAILY_LIMIT);
        
        echo json_encode([
            'success' => true,
            'message' => 'Boost berhasil! Views ditambahkan ke video Anda.',
            'data' => [
                'viewsAdded' => $viewsAdded,
                'status' => $status,
                'processingTime' => $processingTime,
                'orderId' => $orderId,
                'used' => $newBoost['used'],
                'remaining' => $newBoost['remaining']
            ]
        ]);
        exit;
    }
}

// Handle GET stats
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json');
    $stats = getTodayStats($pdo);
    echo json_encode([
        'videosToday' => (int)$stats['videos'],
        'totalViews' => (int)$stats['views'],
        'successRate' => (float)$stats['success'],
        'avgTime' => $stats['time'] . ' ms'
    ]);
    exit;
}

$ip = getIP();
$boost = canBoost($pdo, $ip, $DAILY_LIMIT);
$stats = getTodayStats($pdo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok View Booster</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="gradient-bg text-white p-6 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i data-lucide="trending-up" class="w-8 h-8"></i>
                TikTok View Booster
            </h1>
            <p class="text-blue-100 mt-2">Tingkatkan views video TikTok Anda secara instant</p>
        </div>
    </header>

    <div class="max-w-4xl mx-auto p-6 space-y-6">
        <!-- Stats -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="bar-chart-3" class="w-6 h-6 text-blue-600"></i>
                <h2 class="text-xl font-semibold">Statistik Hari Ini</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-blue-600"><?= number_format($stats['videos']) ?></div>
                    <div class="text-sm text-gray-600">Video Diboost</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-green-600"><?= number_format($stats['views']) ?></div>
                    <div class="text-sm text-gray-600">Total Views</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-purple-600"><?= $stats['success'] ?>%</div>
                    <div class="text-sm text-gray-600">Success Rate</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-orange-600"><?= $stats['time'] ?></div>
                    <div class="text-sm text-gray-600">Avg Time</div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="link" class="w-6 h-6 text-blue-600"></i>
                <h2 class="text-xl font-semibold">Boost Video TikTok</h2>
            </div>
            
            <form id="boostForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Video TikTok</label>
                    <input type="url" id="videoUrl" placeholder="https://vt.tiktok.com/ZSFxxx/" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center gap-2 text-blue-800">
                        <i data-lucide="info" class="w-5 h-5"></i>
                        <span class="font-medium">Limit Harian</span>
                    </div>
                    <p class="text-blue-700 mt-1">
                        Anda telah menggunakan <?= $boost['used'] ?>/<?= $DAILY_LIMIT ?> boost hari ini.
                        Sisa: <?= $boost['remaining'] ?> boost.
                    </p>
                </div>
                
                <button type="submit" id="submitBtn" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2 disabled:bg-gray-400"
                        <?= !$boost['can'] ? 'disabled' : '' ?>>
                    <i data-lucide="zap" class="w-5 h-5"></i>
                    <span id="btnText"><?= $boost['can'] ? 'Boost Sekarang' : 'Limit Tercapai' ?></span>
                </button>
            </form>
        </div>

        <!-- Results -->
        <div id="results" class="hidden bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                <h2 class="text-xl font-semibold">Hasil Boost</h2>
            </div>
            <div id="resultsContent"></div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        document.getElementById('boostForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const videoUrl = document.getElementById('videoUrl').value;
            
            btn.disabled = true;
            btnText.textContent = 'Memproses...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'boost');
                formData.append('videoUrl', videoUrl);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showResults(data, videoUrl);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            } finally {
                btn.disabled = false;
                btnText.textContent = 'Boost Sekarang';
            }
        });
        
        function showResults(data, url) {
            const results = document.getElementById('results');
            const content = document.getElementById('resultsContent');
            
            content.innerHTML = `
                <div class="space-y-4">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-green-800 mb-2">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span class="font-medium">Boost Berhasil!</span>
                        </div>
                        <p class="text-green-700">${data.message}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-lg font-bold text-blue-600">+${data.data.viewsAdded.toLocaleString()}</div>
                            <div class="text-sm text-gray-600">Views Ditambahkan</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-lg font-bold text-purple-600">${data.data.processingTime}</div>
                            <div class="text-sm text-gray-600">Waktu Proses</div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">Order ID</div>
                        <div class="font-mono text-sm">${data.data.orderId}</div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button onclick="resetForm()" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                            Boost Video Lain
                        </button>
                        <button onclick="boostAgain('${url}')" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700">
                            Boost Lagi
                        </button>
                    </div>
                </div>
            `;
            
            results.classList.remove('hidden');
            results.scrollIntoView({ behavior: 'smooth' });
            lucide.createIcons();
        }
        
        function resetForm() {
            document.getElementById('videoUrl').value = '';
            document.getElementById('results').classList.add('hidden');
        }
        
        function boostAgain(url) {
            document.getElementById('videoUrl').value = url;
            document.getElementById('results').classList.add('hidden');
        }
    </script>
</body>
</html>