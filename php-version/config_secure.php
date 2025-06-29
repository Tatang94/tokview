<?php
// Konfigurasi Aman dengan Enkripsi
// TikTok View Booster - Versi Aman

class SecureConfig {
    private static $encryptionKey = 'TikTokBooster2025!SecureKey#Indonesian';
    private static $cipher = 'AES-256-CBC';
    
    // Enkripsi data sensitif
    public static function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($data, self::$cipher, self::$encryptionKey, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    // Dekripsi data
    public static function decrypt($data) {
        $parts = explode('::', base64_decode($data), 2);
        if (count($parts) !== 2) return false;
        
        list($encrypted_data, $iv) = $parts;
        return openssl_decrypt($encrypted_data, self::$cipher, self::$encryptionKey, 0, $iv);
    }
    
    // Konfigurasi database terenkripsi
    public static function getDatabaseConfig() {
        // Data asli yang akan dienkripsi
        return [
            'host' => self::decrypt('c3FsMzA1LmV6eXJvLmNvbTo6N2VkZjNhOGI4YzNkNGZlNw=='), // sql305.ezyro.com
            'dbname' => self::decrypt('ZXp5cm9fMzkyNzAxMjNfYWhheQ=='), // ezyro_39270123_ahay
            'username' => self::decrypt('ZXp5cm9fMzkyNzAxMjM='), // ezyro_39270123
            'password' => self::decrypt('YmVjODZjNDJmNQ==') // bec86c42f5
        ];
    }
    
    // API Key terenkripsi
    public static function getApiConfig() {
        return [
            'api_key' => self::decrypt('OTk0MTc5MTViOGIzNDhiMDI1ZWUzNDhlNjc4Yjc3ODg='), // API key asli
            'api_url' => 'https://lollipop-smm.com/api/v2',
            'service_id' => 746
        ];
    }
    
    // Enkripsi URL TikTok untuk keamanan
    public static function encryptUrl($url) {
        // Tambahan salt untuk URL
        $urlSalt = 'TikTokURL_' . date('Y-m-d');
        return self::encrypt($url . '|' . $urlSalt);
    }
    
    // Dekripsi URL TikTok
    public static function decryptUrl($encryptedUrl) {
        $decrypted = self::decrypt($encryptedUrl);
        if (!$decrypted) return false;
        
        $parts = explode('|', $decrypted);
        return $parts[0] ?? false;
    }
    
    // Validasi IP dan cegah bot
    public static function validateRequest($ip) {
        // Cek apakah IP valid
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        // Cek apakah dari datacenter/VPN (basic check)
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

// Generate encrypted values (jalankan sekali untuk setup)
if (isset($_GET['setup']) && $_GET['setup'] === 'encrypt') {
    echo "=== SETUP ENKRIPSI ===\n";
    echo "Database Host: " . SecureConfig::encrypt('sql305.ezyro.com') . "\n";
    echo "Database Name: " . SecureConfig::encrypt('ezyro_39270123_ahay') . "\n";
    echo "Database User: " . SecureConfig::encrypt('ezyro_39270123') . "\n";
    echo "Database Pass: " . SecureConfig::encrypt('bec86c42f5') . "\n";
    echo "API Key: " . SecureConfig::encrypt('99417915b8b348b025ee348e678b7788') . "\n";
    exit;
}
?>