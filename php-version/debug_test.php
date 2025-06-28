<?php
session_start();
require_once 'config_hosting.php';

// Enable all error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Test untuk TikTok Booster PHP</h2>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p style='color: green;'>✓ Database connection: SUCCESS</p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection: FAILED - " . $e->getMessage() . "</p>";
    exit;
}

// Check tables
$boostsTable = TABLE_PREFIX . 'tiktok_boosts';
$usersTable = TABLE_PREFIX . 'users';

try {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$boostsTable}'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Table {$boostsTable}: EXISTS</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE {$boostsTable}");
        $columns = $stmt->fetchAll();
        echo "<p>Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$boostsTable}");
        $count = $stmt->fetch();
        echo "<p>Total records: {$count['total']}</p>";
        
        // Test today's stats
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$boostsTable} WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $todayCount = $stmt->fetch();
        echo "<p>Today's records: {$todayCount['count']}</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Table {$boostsTable}: NOT EXISTS</p>";
        
        // Try to create table
        echo "<p>Attempting to create table...</p>";
        $createTable = "
        CREATE TABLE {$boostsTable} (
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
        
        try {
            $pdo->exec($createTable);
            echo "<p style='color: green;'>✓ Table created successfully</p>";
        } catch(PDOException $e) {
            echo "<p style='color: red;'>✗ Failed to create table: " . $e->getMessage() . "</p>";
        }
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Error checking tables: " . $e->getMessage() . "</p>";
}

// Test insert
echo "<h3>Testing Insert Operation</h3>";
try {
    $testUrl = 'https://vt.tiktok.com/test123';
    $testIP = '127.0.0.1';
    
    $stmt = $pdo->prepare("INSERT INTO {$boostsTable} (video_url, service_id, ip_address) VALUES (?, ?, ?)");
    $result = $stmt->execute([$testUrl, TIKTOK_SERVICE_ID, $testIP]);
    $testId = $pdo->lastInsertId();
    
    if ($result && $testId) {
        echo "<p style='color: green;'>✓ Test insert: SUCCESS (ID: {$testId})</p>";
        
        // Test update
        $stmt = $pdo->prepare("UPDATE {$boostsTable} SET 
            status = 'completed', 
            views_added = 1500, 
            order_id = 'TEST123',
            processing_time = '100 ms',
            video_title = 'Test Video'
            WHERE id = ?");
        $updateResult = $stmt->execute([$testId]);
        
        if ($updateResult) {
            echo "<p style='color: green;'>✓ Test update: SUCCESS</p>";
        } else {
            echo "<p style='color: red;'>✗ Test update: FAILED</p>";
        }
        
        // Clean up test record
        $stmt = $pdo->prepare("DELETE FROM {$boostsTable} WHERE id = ?");
        $stmt->execute([$testId]);
        echo "<p>Test record cleaned up</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Test insert: FAILED</p>";
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Test operations failed: " . $e->getMessage() . "</p>";
}

// Test API configuration
echo "<h3>API Configuration</h3>";
$apiKey = $_ENV['N1PANEL_API_KEY'] ?? (defined('N1PANEL_API_KEY') ? N1PANEL_API_KEY : null);
if ($apiKey && $apiKey !== 'your_api_key_here') {
    echo "<p style='color: green;'>✓ API Key: CONFIGURED</p>";
    echo "<p>API URL: " . N1PANEL_API_URL . "</p>";
    echo "<p>Service ID: " . TIKTOK_SERVICE_ID . "</p>";
} else {
    echo "<p style='color: orange;'>⚠ API Key: NOT CONFIGURED (will use demo mode)</p>";
}

echo "<p><a href='index_hosting.php'>← Back to Main App</a></p>";
?>