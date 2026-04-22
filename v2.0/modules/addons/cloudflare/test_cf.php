<?php
require_once __DIR__ . '/lib/API.php';
require_once __DIR__ . '/../../../init.php';

use WHMCS\Database\Capsule;

// Fetch settings
$dbSettings = Capsule::table('mod_cloudflare_settings')->pluck('value', 'setting');
$apiToken = $dbSettings['master_api_token'];
$email = $dbSettings['master_email'];

echo "<h2>Cloudflare API Diagnostic</h2>";
echo "Token Length: " . strlen($apiToken) . "<br>";
echo "Email Set: " . (!empty($email) ? 'Yes' : 'No') . "<br><hr>";

$api = new \WHMCS\Module\Addon\Cloudflare\API($apiToken, $email);

try {
    echo "<b>Test 1: Fetching Zones (Zone:Read)</b><br>";
    $response = $api->request('zones');
    $zoneId = $response['result'][0]['id'] ?? null;
    $domain = $response['result'][0]['name'] ?? null;
    echo "<span style='color:green'>SUCCESS: Found Zone '$domain' ($zoneId)</span><br><br>";
    
    if ($zoneId) {
        echo "<b>Test 2: Fetching DNS Records for $domain (DNS:Read)</b><br>";
        $dns = $api->request("zones/$zoneId/dns_records");
        echo "<span style='color:green'>SUCCESS: Fetched " . count($dns['result']) . " DNS records.</span><br><br>";
    }
} catch (\Exception $e) {
    echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
}
