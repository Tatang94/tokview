<?php
// Debug script untuk test koneksi database dan API
header('Content-Type: application/json');

try {
    // Test database connection
    $dbConfig = [
        'host' => 'localhost',
        'dbname' => 'atzpuls1_buka',
        'username' => 'atzpuls1_buka',
        'password' => 'Buka@100'
    ];
    
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4", 
        $dbConfig['username'], 
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test table
    $stmt = $pdo->query("SELECT COUNT(*) FROM boosts");
    $count = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'database' => 'connected',
        'boost_count' => $count,
        'message' => 'Database connection successful'
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Database connection failed'
    ]);
}
?>