<?php
session_start();
require_once 'config_hosting.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch(PDOException $e) {
    if (DEBUG_MODE) {
        die("Connection failed: " . $e->getMessage());
    } else {
        die("Database connection error. Please try again later.");
    }
}

// Table names with prefix
$usersTable = TABLE_PREFIX . 'users';
$boostsTable = TABLE_PREFIX . 'tiktok_boosts';

// Create tables if they don't exist (untuk auto-setup)
try {
    $createTables = "
    CREATE TABLE IF NOT EXISTS {$usersTable} (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS {$boostsTable} (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_url VARCHAR(500) NOT NULL,
        service_id INT NOT NULL,
        order_id VARCHAR(255),
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        views_added INT DEFAULT 0,
        processing_time VARCHAR(50),
        video_title VARCHAR(500),
        ip_address VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createTables);
} catch(PDOException $e) {
    // Tables mungkin sudah ada atau tidak ada permission, lanjut saja
}

// Helper functions
function validateTikTokUrl($url) {
    $patterns = [
        '/^https?:\/\/(www\.)?tiktok\.com\/@[\w.-]+\/video\/\d+/',
        '/^https?:\/\/(vm|vt)\.tiktok\.com\/[\w\d]+/',
        '/^https?:\/\/m\.tiktok\.com\/v\/\d+/',
        '/^https?:\/\/(www\.)?tiktok\.com\/t\/[\w\d]+/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return true;
        }
    }
    return false;
}

function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function canBoost($pdo, $ip) {
    global $boostsTable;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$boostsTable} WHERE ip_address = ? AND DATE(created_at) = ?");
    $stmt->execute([$ip, $today]);
    $count = $stmt->fetchColumn();
    
    return [
        'canBoost' => $count < DAILY_BOOST_LIMIT,
        'boostsToday' => (int)$count,
        'boostsRemaining' => max(0, DAILY_BOOST_LIMIT - $count)
    ];
}

function getTodayStats($pdo) {
    global $boostsTable;
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as videos_today,
        COALESCE(SUM(views_added), 0) as total_views,
        ROUND(AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100, 1) as success_rate,
        ROUND(AVG(CAST(SUBSTRING_INDEX(processing_time, ' ', 1) AS DECIMAL(10,2))), 1) as avg_time
        FROM {$boostsTable} 
        WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    
    $result = $stmt->fetch();
    return $result ?: [
        'videos_today' => 0,
        'total_views' => 0,
        'success_rate' => 0,
        'avg_time' => 0
    ];
}

// Get API key from environment or config
$apiKey = $_ENV['N1PANEL_API_KEY'] ?? (defined('N1PANEL_API_KEY') ? N1PANEL_API_KEY : null);

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'boost') {
        $input = json_decode(file_get_contents('php://input'), true);
        $videoUrl = trim($input['videoUrl'] ?? '');
        $ip = getClientIP();
        
        if (!validateTikTokUrl($videoUrl)) {
            echo json_encode([
                'success' => false,
                'message' => 'URL TikTok tidak valid. Pastikan URL dalam format yang benar.',
                'error' => 'Invalid TikTok URL format'
            ]);
            exit;
        }
        
        $canBoostResult = canBoost($pdo, $ip);
        if (!$canBoostResult['canBoost']) {
            echo json_encode([
                'success' => false,
                'message' => 'Anda sudah mencapai batas ' . DAILY_BOOST_LIMIT . ' boost per hari. Silakan coba lagi besok.',
                'data' => [
                    'boostsToday' => $canBoostResult['boostsToday'],
                    'boostsRemaining' => $canBoostResult['boostsRemaining']
                ]
            ]);
            exit;
        }
        
        // Insert boost record
        $stmt = $pdo->prepare("INSERT INTO {$boostsTable} (video_url, service_id, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$videoUrl, TIKTOK_SERVICE_ID, $ip]);
        $boostId = $pdo->lastInsertId();
        
        // API call to N1Panel (real implementation)
        $startTime = microtime(true);
        
        // Determine views to add
        $viewsToAdd = rand(DEFAULT_VIEWS_RANGE[0], DEFAULT_VIEWS_RANGE[1]);
        
        if ($apiKey && $apiKey !== 'your_api_key_here') {
            // Real API call to N1Panel
            $apiData = [
                'key' => $apiKey,
                'action' => 'add',
                'service' => TIKTOK_SERVICE_ID,
                'link' => $videoUrl,
                'quantity' => $viewsToAdd
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, N1PANEL_API_URL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Log the response for debugging
            if (DEBUG_MODE) {
                error_log("N1Panel API Response: " . $response);
                error_log("HTTP Code: " . $httpCode);
                error_log("CURL Error: " . $curlError);
            }
            
            if ($response && $httpCode === 200) {
                $apiResponse = json_decode($response, true);
                
                if ($apiResponse && isset($apiResponse['order'])) {
                    $viewsAdded = $viewsToAdd;
                    $orderId = $apiResponse['order'];
                    $status = 'completed';
                } else {
                    $viewsAdded = 0;
                    $orderId = 'API_FAILED_' . time();
                    $status = 'failed';
                }
            } else {
                $viewsAdded = 0;
                $orderId = 'NET_ERROR_' . time();
                $status = 'failed';
            }
        } else {
            // Demo mode - still add to statistics for testing
            $viewsAdded = $viewsToAdd;
            $orderId = 'DEMO_' . time() . rand(1000, 9999);
            $status = 'completed';
        }
        
        $processingTime = round((microtime(true) - $startTime) * 1000, 2) . ' ms';
        
        // Update boost record
        $stmt = $pdo->prepare("UPDATE {$boostsTable} SET 
            status = ?, 
            views_added = ?, 
            order_id = ?,
            processing_time = ?,
            video_title = 'TikTok Video',
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        $updateResult = $stmt->execute([$status, $viewsAdded, $orderId, $processingTime, $boostId]);
        
        // Debug logging
        if (DEBUG_MODE) {
            error_log("Database update result: " . ($updateResult ? 'success' : 'failed'));
            error_log("Boost ID: " . $boostId);
            error_log("Views added: " . $viewsAdded);
            error_log("Status: " . $status);
        }
        
        $updatedCanBoost = canBoost($pdo, $ip);
        
        echo json_encode([
            'success' => $status === 'completed',
            'message' => $status === 'completed' ? 'Boost berhasil! Views ditambahkan ke video Anda.' : 'Boost gagal. Silakan coba lagi.',
            'data' => [
                'viewsAdded' => $viewsAdded,
                'status' => $status,
                'processingTime' => $processingTime,
                'videoTitle' => 'TikTok Video',
                'orderId' => $orderId,
                'boostsToday' => $updatedCanBoost['boostsToday'],
                'boostsRemaining' => $updatedCanBoost['boostsRemaining']
            ]
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json');
    $stats = getTodayStats($pdo);
    
    echo json_encode([
        'videosToday' => (int)$stats['videos_today'],
        'totalViews' => (int)$stats['total_views'],
        'successRate' => (float)$stats['success_rate'],
        'avgTime' => $stats['avg_time'] . ' ms'
    ]);
    exit;
}

$ip = getClientIP();
$canBoostResult = canBoost($pdo, $ip);
$stats = getTodayStats($pdo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white p-6 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i data-lucide="trending-up" class="w-8 h-8"></i>
                <?php echo APP_NAME; ?>
            </h1>
            <p class="text-blue-100 mt-2">Tingkatkan views video TikTok Anda secara instant</p>
        </div>
    </header>

    <div class="max-w-4xl mx-auto p-6 space-y-6">
        <!-- Stats Section -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="bar-chart-3" class="w-6 h-6 text-blue-600"></i>
                <h2 class="text-xl font-semibold">Statistik Hari Ini</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['videos_today']); ?></div>
                    <div class="text-sm text-gray-600">Video Diboost</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['total_views']); ?></div>
                    <div class="text-sm text-gray-600">Total Views</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-purple-600"><?php echo $stats['success_rate']; ?>%</div>
                    <div class="text-sm text-gray-600">Success Rate</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-orange-600"><?php echo $stats['avg_time']; ?></div>
                    <div class="text-sm text-gray-600">Avg Time</div>
                </div>
            </div>
        </div>

        <!-- Boost Form -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="link" class="w-6 h-6 text-blue-600"></i>
                <h2 class="text-xl font-semibold">Boost Video TikTok</h2>
            </div>
            
            <form id="boostForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Video TikTok</label>
                    <input 
                        type="url" 
                        id="videoUrl" 
                        placeholder="https://vt.tiktok.com/ZSFxxx/" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                    >
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center gap-2 text-blue-800">
                        <i data-lucide="info" class="w-5 h-5"></i>
                        <span class="font-medium">Limit Harian</span>
                    </div>
                    <p class="text-blue-700 mt-1">
                        Anda telah menggunakan <?php echo $canBoostResult['boostsToday']; ?>/<?php echo DAILY_BOOST_LIMIT; ?> boost hari ini.
                        Sisa: <?php echo $canBoostResult['boostsRemaining']; ?> boost.
                    </p>
                </div>
                
                <button 
                    type="submit" 
                    id="submitBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2 disabled:bg-gray-400 disabled:cursor-not-allowed"
                    <?php echo !$canBoostResult['canBoost'] ? 'disabled' : ''; ?>
                >
                    <i data-lucide="zap" class="w-5 h-5"></i>
                    <span id="btnText">
                        <?php echo $canBoostResult['canBoost'] ? 'Boost Sekarang' : 'Limit Tercapai'; ?>
                    </span>
                </button>
            </form>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" class="hidden bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                <h2 class="text-xl font-semibold">Hasil Boost</h2>
            </div>
            <div id="resultsContent"></div>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm mt-8">
            <p><?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
            <p class="mt-1">Powered by N1Panel API</p>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        document.getElementById('boostForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const videoUrl = document.getElementById('videoUrl').value.trim();
            
            if (!videoUrl) {
                alert('Silakan masukkan URL video TikTok');
                return;
            }
            
            submitBtn.disabled = true;
            btnText.textContent = 'Memproses...';
            submitBtn.classList.add('loading');
            
            try {
                const response = await fetch('?action=boost', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ videoUrl })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showResults(data, videoUrl);
                    setTimeout(() => {
                        location.reload(); // Refresh stats
                    }, 2000);
                } else {
                    alert(data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                alert('Terjadi kesalahan jaringan: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Boost Sekarang';
                submitBtn.classList.remove('loading');
            }
        });
        
        function showResults(data, videoUrl) {
            const resultsSection = document.getElementById('resultsSection');
            const resultsContent = document.getElementById('resultsContent');
            
            const statusClass = data.success ? 'green' : 'red';
            const statusIcon = data.success ? 'check-circle' : 'x-circle';
            
            resultsContent.innerHTML = `
                <div class="space-y-4">
                    <div class="bg-${statusClass}-50 border border-${statusClass}-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-${statusClass}-800 mb-2">
                            <i data-lucide="${statusIcon}" class="w-5 h-5"></i>
                            <span class="font-medium">${data.success ? 'Boost Berhasil!' : 'Boost Gagal!'}</span>
                        </div>
                        <p class="text-${statusClass}-700">${data.message}</p>
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
                        <div class="font-mono text-sm break-all">${data.data.orderId}</div>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-sm text-blue-700">
                            <strong>Status:</strong> ${data.data.boostsToday}/${<?php echo DAILY_BOOST_LIMIT; ?>} boost digunakan hari ini
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button onclick="resetForm()" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                            Boost Video Lain
                        </button>
                        <button onclick="boostAgain('${videoUrl}')" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors">
                            Boost Lagi
                        </button>
                    </div>
                </div>
            `;
            
            resultsSection.classList.remove('hidden');
            resultsSection.scrollIntoView({ behavior: 'smooth' });
            lucide.createIcons();
        }
        
        function resetForm() {
            document.getElementById('videoUrl').value = '';
            document.getElementById('resultsSection').classList.add('hidden');
        }
        
        function boostAgain(url) {
            document.getElementById('videoUrl').value = url;
            document.getElementById('resultsSection').classList.add('hidden');
            document.getElementById('boostForm').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>