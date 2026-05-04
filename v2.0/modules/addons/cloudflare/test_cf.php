<?php
require_once __DIR__ . '/lib/API.php';
require_once __DIR__ . '/../../../init.php';

use WHMCS\Database\Capsule;

// Fetch settings from the first connected account
$account = Capsule::table('mod_cloudflare_user_accounts')->first();
if (!$account) {
    die("No connected Cloudflare accounts found in mod_cloudflare_user_accounts.");
}

$apiToken = $account->api_token;
$email = $account->email;

echo "<h2>Cloudflare API Diagnostic (Testing Account: {$account->name})</h2>";
echo "Token Length: " . strlen($apiToken) . "<br>";
echo "Email Set: " . (!empty($email) ? 'Yes' : 'No') . "<br><hr>";

$api = new \WHMCS\Module\Addon\Cloudflare\API($apiToken, $email);

try {
    echo "<b>Test 1: Fetching Zones (Zone:Read)</b><br>";
    $zones = $api->getZones();
    
    if ($zones) {
        echo "<span style='color:green'>SUCCESS: Fetched " . count($zones) . " zones.</span><br><br>";
        
        $zoneId = $zones[0]['id'];
        $domain = $zones[0]['name'];
        echo "<b>Test 2: Fetching DNS Records for $domain (DNS:Read)</b><br>";
        $dns = $api->getDNSRecords($zoneId);
        echo "<span style='color:green'>SUCCESS: Fetched " . count($dns['result']) . " DNS records.</span><br><br>";
    } else {
        echo "<span style='color:orange'>Failed to fetch zones, or the account has no zones.</span><br><br>";
    }
} catch (\Exception $e) {
    echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
}
