<?php
// Tool untuk Encode/Decode Base64
// Gunakan untuk mengubah URL API atau data lain

echo "=== TOOL ENCODER/DECODER BASE64 ===\n\n";

// Contoh penggunaan
$apiUrl = 'https://lollipop-smm.com/api/v2';
$apiKey = '99417915b8b348b025ee348e678b7788';

echo "CONTOH ENCODING:\n";
echo "URL Asli: " . $apiUrl . "\n";
echo "URL Encoded: " . base64_encode($apiUrl) . "\n\n";

echo "API Key Asli: " . $apiKey . "\n";
echo "API Key Encoded: " . base64_encode($apiKey) . "\n\n";

echo "CONTOH DECODING:\n";
echo "Encoded: aHR0cHM6Ly9sb2xsaXBvcC1zbW0uY29tL2FwaS92Mg==\n";
echo "Decoded: " . base64_decode('aHR0cHM6Ly9sb2xsaXBvcC1zbW0uY29tL2FwaS92Mg==') . "\n\n";

// Jika ada parameter di URL
if (isset($_GET['encode'])) {
    $text = $_GET['encode'];
    echo "INPUT: " . $text . "\n";
    echo "ENCODED: " . base64_encode($text) . "\n";
}

if (isset($_GET['decode'])) {
    $encoded = $_GET['decode'];
    echo "INPUT: " . $encoded . "\n";
    echo "DECODED: " . base64_decode($encoded) . "\n";
}

echo "\n=== CARA PENGGUNAAN ===\n";
echo "1. Encode: encoder.php?encode=https://api-baru.com/v1\n";
echo "2. Decode: encoder.php?decode=aHR0cHM6Ly9hcGktYmFydS5jb20vdjE=\n\n";

echo "=== UNTUK GANTI URL API ===\n";
echo "1. Encode URL baru dengan tool ini\n";
echo "2. Copy hasil encoded\n";
echo "3. Ganti di index.php bagian \$encryptedApiUrl\n";
echo "4. Hapus file encoder.php setelah selesai\n";
?>