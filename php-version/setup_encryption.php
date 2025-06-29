<?php
// Setup Enkripsi - Jalankan sekali untuk generate encrypted values
require_once 'config_secure.php';

echo "=== SETUP ENKRIPSI UNTUK HOSTING INDONESIA ===\n\n";

// Data asli yang akan dienkripsi
$originalData = [
    'database_host' => 'sql305.ezyro.com',
    'database_name' => 'ezyro_39270123_ahay', 
    'database_user' => 'ezyro_39270123',
    'database_pass' => 'bec86c42f5',
    'api_key' => '99417915b8b348b025ee348e678b7788'
];

echo "1. ENKRIPSI DATA DATABASE:\n";
echo "Host: " . SecureConfig::encrypt($originalData['database_host']) . "\n";
echo "Database: " . SecureConfig::encrypt($originalData['database_name']) . "\n";
echo "Username: " . SecureConfig::encrypt($originalData['database_user']) . "\n";  
echo "Password: " . SecureConfig::encrypt($originalData['database_pass']) . "\n\n";

echo "2. ENKRIPSI API KEY:\n";
echo "API Key: " . SecureConfig::encrypt($originalData['api_key']) . "\n\n";

echo "3. TEST DEKRIPSI:\n";
$testEncrypted = SecureConfig::encrypt('test_data_12345');
echo "Encrypted: " . $testEncrypted . "\n";
echo "Decrypted: " . SecureConfig::decrypt($testEncrypted) . "\n\n";

echo "4. TEST ENKRIPSI URL:\n";
$testUrl = 'https://vt.tiktok.com/ZSj8k9L2x/';
$encryptedUrl = SecureConfig::encryptUrl($testUrl);
echo "Original URL: " . $testUrl . "\n";
echo "Encrypted URL: " . $encryptedUrl . "\n";  
echo "Decrypted URL: " . SecureConfig::decryptUrl($encryptedUrl) . "\n\n";

echo "✅ Setup enkripsi selesai!\n";
echo "Sekarang copy encrypted values ke dalam config_secure.php\n";
?>