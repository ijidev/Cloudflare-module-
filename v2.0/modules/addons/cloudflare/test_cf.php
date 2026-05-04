<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/lib/API.php';

use WHMCS\Database\Capsule;

// Fetch settings from all connected accounts
$accounts = Capsule::table('mod_cloudflare_user_accounts')->get();

if ($accounts->isEmpty()) {
    die("<h2>No connected Cloudflare accounts found.</h2><p>Please add an account in the module first.</p>");
}

echo "<h2>Cloudflare API Diagnostic</h2>";

foreach ($accounts as $account) {
    echo "<h3>Testing Account: {$account->name} (ID: {$account->id})</h3>";
    $apiToken = $account->api_token;
    $email = trim($account->email);
    
    echo "<ul>";
    echo "<li>Token Prefix: " . substr($apiToken, 0, 4) . " (Length: " . strlen($apiToken) . ")</li>";
    echo "<li>Email: " . (!empty($email) ? "<code>$email</code> (Auth Mode: Global Key)" : "<i>EMPTY</i> (Auth Mode: API Token)") . "</li>";
    echo "</ul>";

    $api = new \WHMCS\Module\Addon\Cloudflare\API($apiToken, $email);

    try {
        echo "<b>Test 1: Fetching Zones...</b><br>";
        $zones = $api->getZones();
        
        if ($zones) {
            echo "<span style='color:green'>SUCCESS: Fetched " . count($zones) . " zones.</span><br>";
            
            $zoneId = $zones[0]['id'];
            $domain = $zones[0]['name'];
            echo "<b>Test 2: Fetching DNS Records for $domain...</b><br>";
            $dns = $api->getDNSRecords($zoneId);
            echo "<span style='color:green'>SUCCESS: Fetched " . count($dns['result']) . " DNS records.</span><br>";
        } else {
            echo "<span style='color:orange'>WARNING: API call succeeded but returned 0 zones. Check account permissions.</span><br>";
        }
    } catch (\Exception $e) {
        echo "<div style='padding:10px; background:#fee2e2; border:1px solid #ef4444; border-radius:6px; margin-top:5px;'>";
        echo "<b style='color:#b91c1c;'>FAILED:</b> " . $e->getMessage();
        echo "</div>";
    }
    echo "<hr>";
}
