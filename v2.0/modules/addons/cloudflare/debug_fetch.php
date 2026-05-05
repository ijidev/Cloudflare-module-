<?php
require_once __DIR__ . '/../../../init.php';
use WHMCS\Database\Capsule;

// 1. Get Logged in User ID
$ca = new WHMCS\ClientArea();
$clientId = (int)$ca->getUserID();

echo "<style>body { font-family: sans-serif; line-height: 1.5; padding: 20px; background: #f8fafc; } .card { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; } h2 { color: #1e293b; } .success { color: #166534; font-weight: bold; } .error { color: #991b1b; font-weight: bold; } .warning { color: #854d0e; font-weight: bold; }</style>";

echo "<h2>Cloudflare Module Debugger</h2>";

echo "<div class='card'>";
echo "<h3>Session & Identity</h3>";
echo "Current Logged-in Client ID: <b>" . ($clientId ?: '<span class="error">NOT LOGGED IN</span>') . "</b><br>";
if (!$clientId) {
    echo "<p class='warning'>Note: To test client-specific fetching, you must be logged into the client area.</p>";
}
echo "</div>";

echo "<div class='card'>";
echo "<h3>Database Connectivity Check</h3>";
try {
    $totalAccounts = Capsule::table('mod_cloudflare_user_accounts')->count();
    echo "Total records in <code>mod_cloudflare_user_accounts</code>: <b class='success'>$totalAccounts</b><br>";
} catch (\Exception $e) {
    echo "Error accessing table: <b class='error'>" . $e->getMessage() . "</b><br>";
}
echo "</div>";

if ($clientId) {
    echo "<div class='card'>";
    echo "<h3>Client Data Fetching (ID: $clientId)</h3>";
    
    // Check Accounts
    $userAccounts = Capsule::table('mod_cloudflare_user_accounts')->where('client_id', $clientId)->get();
    echo "Accounts linked to this Client: <b class='success'>" . $userAccounts->count() . "</b><br>";
    if ($userAccounts->count() > 0) {
        echo "<ul>";
        foreach ($userAccounts as $acc) {
            echo "<li>ID: {$acc->id} | Name: {$acc->name} | Token: " . substr($acc->api_token, 0, 5) . "...</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>FAIL: No accounts found for this Client ID.</p>";
    }

    // Check Active Services
    $activeServices = Capsule::table('tblhosting')->where('userid', $clientId)->where('domainstatus', 'Active')->get();
    echo "<br>Active Hosting Services: <b class='success'>" . $activeServices->count() . "</b><br>";
    if ($activeServices->count() > 0) {
        echo "<ul>";
        foreach ($activeServices as $s) {
            $hasInfra = Capsule::table('mod_cloudflare_product_infra')->where('product_id', $s->packageid)->exists();
            echo "<li>{$s->domain} (PID: {$s->packageid}) - Linked to Infra: " . ($hasInfra ? '<span class="success">YES</span>' : '<span class="error">NO (Restricted Access)</span>') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>FAIL: No active hosting services found for this client.</p>";
    }
    echo "</div>";
}

echo "<div class='card'>";
echo "<h3>Global Table Dump (Admin View)</h3>";
echo "<p>This shows ALL records in the system to check if they belong to the right clients.</p>";
$allAccounts = Capsule::table('mod_cloudflare_user_accounts')->get();
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%;'>";
echo "<tr><th>ID</th><th>Client ID</th><th>Name</th><th>Email</th></tr>";
foreach ($allAccounts as $acc) {
    $highlight = ($acc->client_id == $clientId) ? "style='background:#dcfce7'" : "";
    echo "<tr $highlight><td>{$acc->id}</td><td>{$acc->client_id}</td><td>{$acc->name}</td><td>{$acc->email}</td></tr>";
}
echo "</table>";
echo "</div>";
