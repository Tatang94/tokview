<?php
session_start();

echo "<h2>Debug Information</h2>";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Full URL:</strong> " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";

if (isset($_GET['verify'])) {
    echo "<p><strong>Verify Parameter:</strong> " . htmlspecialchars($_GET['verify']) . "</p>";
    $code = $_GET['verify'];
    if (preg_match('/^TKB\d+$/', $code)) {
        echo "<p style='color: green;'>✅ Kode format valid</p>";
    } else {
        echo "<p style='color: red;'>❌ Kode format tidak valid</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Parameter verify tidak ditemukan</p>";
}

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>GET Parameters:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h3>Test Links:</h3>";
$testCode = "TKB" . time() . "123";
echo "<p><a href='?verify=" . $testCode . "'>Test Verification Link</a></p>";
echo "<p><a href='license.php?verify=" . $testCode . "'>Test License.php Link</a></p>";
?>