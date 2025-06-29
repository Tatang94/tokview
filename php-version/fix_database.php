<?php
// Script sederhana untuk memperbaiki database dan test koneksi
header('Content-Type: text/plain');

$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'atzpuls1_buka',
    'username' => 'atzpuls1_buka',
    'password' => 'Buka@100'
];

try {
    echo "Connecting to database...\n";
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4", 
        $dbConfig['username'], 
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected successfully\n\n";
    
    // Check table structure
    echo "Checking table structure...\n";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'boosts'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✓ Table 'boosts' exists\n";
        
        // Check columns
        $stmt = $pdo->prepare("DESCRIBE boosts");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nCurrent table structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check if service_type exists
        $hasServiceType = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'service_type') {
                $hasServiceType = true;
                break;
            }
        }
        
        if (!$hasServiceType) {
            echo "\n❌ Column 'service_type' is missing\n";
            echo "Attempting to add column...\n";
            
            try {
                $pdo->exec("ALTER TABLE boosts ADD COLUMN service_type ENUM('views', 'followers', 'likes') DEFAULT 'views' AFTER service_id");
                echo "✓ Column 'service_type' added successfully\n";
            } catch (Exception $e) {
                echo "❌ Failed to add column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Column 'service_type' exists\n";
        }
        
        // Test query
        echo "\nTesting stats query...\n";
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM boosts WHERE DATE(created_at) = CURDATE()");
            $stmt->execute();
            $result = $stmt->fetch();
            echo "✓ Stats query successful - Today's boosts: {$result['total']}\n";
        } catch (Exception $e) {
            echo "❌ Stats query failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Table 'boosts' does not exist\n";
        echo "Creating table...\n";
        
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
        
        try {
            $pdo->exec($createTable);
            echo "✓ Table 'boosts' created successfully\n";
        } catch (Exception $e) {
            echo "❌ Failed to create table: " . $e->getMessage() . "\n";
        }
    }
    
} catch(Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\nDatabase check completed.\n";
?>