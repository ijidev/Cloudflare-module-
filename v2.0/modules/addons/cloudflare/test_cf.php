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
    echo "<b>Test 1: Fetching Zone ID for 'everestserver.com' (Zone:Read)</b><br>";
    $zoneId = $api->getZoneId('everestserver.com');
    
    if ($zoneId) {
        echo "<span style='color:green'>SUCCESS: Found Zone ($zoneId)</span><br><br>";
        
        echo "<b>Test 2: Fetching DNS Records (DNS:Read)</b><br>";
        $dns = $api->getDNSRecords($zoneId);
        echo "<span style='color:green'>SUCCESS: Fetched " . count($dns['result']) . " DNS records.</span><br><br>";
    } else {
        echo "<span style='color:orange'>Failed to find Zone ID. The token works but has no access to everestserver.com.</span><br><br>";
    }
} catch (\Exception $e) {
    echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
}
