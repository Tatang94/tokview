<?php
// Debug test untuk N1Panel API - Test lengkap
header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG TEST N1PANEL API ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "===============================\n\n";

$apiKey = '4dab7086d758c1f5ab89cf4a34cd2201';
$apiUrl = 'https://n1panel.com/api/v2';

function makeApiCall($action, $params = array()) {
    global $apiKey, $apiUrl;
    
    $data = array_merge(array('key' => $apiKey, 'action' => $action), $params);
    
    echo "Request Data: " . json_encode($data) . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if($curlError) {
        echo "cURL Error: $curlError\n";
    }
    echo "Response: $response\n";
    echo "Response Length: " . strlen($response) . " bytes\n";
    
    $decoded = json_decode($response, true);
    if($decoded) {
        echo "Decoded JSON: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "JSON Decode Error: " . json_last_error_msg() . "\n";
    }
    
    echo "Connection Info:\n";
    echo "- URL: " . $curlInfo['url'] . "\n";
    echo "- Connect Time: " . $curlInfo['connect_time'] . "s\n";
    echo "- Total Time: " . $curlInfo['total_time'] . "s\n";
    echo "- Size Download: " . $curlInfo['size_download'] . " bytes\n\n";
    
    return array('response' => $response, 'httpCode' => $httpCode, 'decoded' => $decoded);
}

// Test 1: Check Balance
echo "1. CHECKING BALANCE\n";
echo "-------------------\n";
$balanceResult = makeApiCall('balance');

// Test 2: Check Services
echo "2. CHECKING SERVICES\n";
echo "--------------------\n";
$servicesResult = makeApiCall('services');

// Test 3: Check specific service 838
echo "3. CHECKING SERVICE 838 (TikTok Views)\n";
echo "--------------------------------------\n";
if($servicesResult['decoded'] && isset($servicesResult['decoded']['services'])) {
    $service838 = null;
    foreach($servicesResult['decoded']['services'] as $service) {
        if($service['service'] == 838) {
            $service838 = $service;
            break;
        }
    }
    if($service838) {
        echo "Service 838 found:\n";
        echo json_encode($service838, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "Service 838 NOT FOUND in services list!\n\n";
    }
} else {
    echo "Could not retrieve services list\n\n";
}

// Test 4: Add Test Order
echo "4. ADDING TEST ORDER\n";
echo "--------------------\n";
$testUrl = 'https://vt.tiktok.com/ZSFAbCdEf/';
$orderResult = makeApiCall('add', array(
    'service' => 838,
    'link' => $testUrl,
    'quantity' => 1000
));

// Test 5: Check Orders
echo "5. CHECKING ORDERS\n";
echo "------------------\n";
$ordersResult = makeApiCall('orders');

// Summary
echo "=== SUMMARY ===\n";
echo "Balance API: " . ($balanceResult['httpCode'] == 200 ? 'OK' : 'FAILED') . "\n";
echo "Services API: " . ($servicesResult['httpCode'] == 200 ? 'OK' : 'FAILED') . "\n";
echo "Add Order API: " . ($orderResult['httpCode'] == 200 ? 'OK' : 'FAILED') . "\n";
echo "Orders API: " . ($ordersResult['httpCode'] == 200 ? 'OK' : 'FAILED') . "\n";

if($orderResult['decoded']) {
    if(isset($orderResult['decoded']['order'])) {
        echo "Order Created: " . $orderResult['decoded']['order'] . "\n";
    } elseif(isset($orderResult['decoded']['error'])) {
        echo "Order Error: " . $orderResult['decoded']['error'] . "\n";
    }
}

echo "\n=== END DEBUG ===\n";
?>