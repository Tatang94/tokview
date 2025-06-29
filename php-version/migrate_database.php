<?php
// Database migration script untuk menambahkan kolom service_type
// Jalankan file ini sekali untuk mengupdate database yang sudah ada

header('Content-Type: application/json');

class SecureMigrationConfig {
    private static $encryptionKey = 'TikTokBooster2025SecureKey!@#$%^&*()';
    
    public static function getDatabaseConfig() {
        return [
            'host' => 'localhost',
            'dbname' => 'atzpuls1_buka',
            'username' => 'atzpuls1_buka',
            'password' => 'Buka@100'
        ];
    }
}

$dbConfig = SecureMigrationConfig::getDatabaseConfig();

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4", 
        $dbConfig['username'], 
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode(['status' => 'Database connected successfully']) . "\n";
    
    // Check if boosts table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'boosts'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create new table with all columns
        $createTable = "CREATE TABLE boosts (
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
        
        $pdo->exec($createTable);
        echo json_encode(['status' => 'New table created with all columns']) . "\n";
    } else {
        // Check if service_type column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM boosts LIKE 'service_type'");
        $stmt->execute();
        $hasServiceType = $stmt->rowCount() > 0;
        
        if (!$hasServiceType) {
            // Add service_type column
            $alterTable = "ALTER TABLE boosts ADD COLUMN service_type ENUM('views', 'followers', 'likes') DEFAULT 'views' AFTER service_id";
            $pdo->exec($alterTable);
            echo json_encode(['status' => 'service_type column added successfully']) . "\n";
        } else {
            echo json_encode(['status' => 'service_type column already exists']) . "\n";
        }
    }
    
    // Show final table structure
    $stmt = $pdo->prepare("DESCRIBE boosts");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'Migration completed successfully',
        'table_structure' => $columns
    ]) . "\n";
    
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'Migration failed',
        'error' => $e->getMessage()
    ]) . "\n";
}
?>