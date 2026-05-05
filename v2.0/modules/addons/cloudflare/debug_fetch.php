<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/lib/API.php';
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Cloudflare\API;

// 1. Get Identity
$ca = new WHMCS\ClientArea();
$clientId = (int)$ca->getUserID();

echo "<style>
body { font-family: 'Inter', sans-serif; line-height: 1.6; padding: 20px; background: #f1f5f9; color: #334155; }
.card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); margin-bottom: 24px; border: 1px solid #e2e8f0; }
h2, h3 { color: #1e293b; margin-top: 0; }
.success { color: #15803d; font-weight: 600; }
.error { color: #b91c1c; font-weight: 600; }
.warning { color: #a16207; font-weight: 600; }
.info { color: #1d4ed8; font-weight: 600; }
code { background: #f1f5f9; padding: 2px 4px; border-radius: 4px; font-weight: 600; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; }
th { background: #f8fafc; font-size: 12px; text-transform: uppercase; color: #64748b; }
pre { background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 8px; font-size: 12px; overflow-x: auto; }
</style>";

echo "<h2>Cloudflare Advanced Diagnostic Suite</h2>";

// Section: Session & Database
echo "<div class='card'>";
echo "<h3>1. Core Environment</h3>";
echo "Active Client ID: <b>" . ($clientId ?: '<span class="error">GUEST (LOG IN TO TEST CLIENT DATA)</span>') . "</b><br>";
try {
    $count = Capsule::table('mod_cloudflare_user_accounts')->count();
    echo "Database Connectivity: <span class='success'>CONNECTED ($count accounts found)</span><br>";
} catch (\Exception $e) {
    echo "Database Error: <span class='error'>" . $e->getMessage() . "</span><br>";
}
echo "</div>";

// Section: Specific Domain Check (gottaexchange.org)
echo "<div class='card'>";
echo "<h3>2. Targeted Domain Check: <code>gottaexchange.org</code></h3>";
$targetDomain = "gottaexchange.org";
$targetAcc = Capsule::table('mod_cloudflare_user_accounts')->first(); // Try to find any account to query with

if ($targetAcc) {
    try {
        $api = new API($targetAcc->api_token, $targetAcc->email);
        $zoneId = $api->getZoneId($targetDomain);
        
        if ($zoneId) {
            echo "Zone ID for $targetDomain: <span class='success'>$zoneId</span><br>";
            $records = $api->getDNSRecords($zoneId);
            $aRecord = null;
            foreach ($records['result'] as $r) {
                if ($r['type'] === 'A' && ($r['name'] === $targetDomain || $r['name'] === 'www.'.$targetDomain)) {
                    $aRecord = $r['content'];
                    break;
                }
            }

            if ($aRecord) {
                echo "A Record Content: <span class='info'>$aRecord</span><br>";
                // Check if this matches any infrastructure IP
                $infra = Capsule::table('mod_cloudflare_infrastructure')->where('ip', $aRecord)->first();
                if ($infra) {
                    echo "Cluster Match: <span class='success'>FOUND (Cluster: {$infra->name}, ID: {$infra->id})</span><br>";
                } else {
                    echo "Cluster Match: <span class='warning'>NONE (This IP does not match any registered infrastructure)</span><br>";
                }
            } else {
                echo "A Record: <span class='error'>NOT FOUND in Cloudflare Zone</span><br>";
            }
        } else {
            echo "Zone Check: <span class='error'>Domain not found in the connected Cloudflare accounts</span><br>";
        }
    } catch (\Exception $e) {
        echo "API Error: <span class='error'>" . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>No Cloudflare accounts connected to perform API lookups.</span>";
}
echo "</div>";

// Section: Infrastructure Reverse Check
echo "<div class='card'>";
echo "<h3>3. Infrastructure IP Mapping</h3>";
$clusters = Capsule::table('mod_cloudflare_infrastructure')->get();
if ($clusters->count() > 0) {
    echo "<table><thead><tr><th>Cluster Name</th><th>IP Address</th><th>Status</th></tr></thead><tbody>";
    foreach ($clusters as $c) {
        echo "<tr><td>{$c->name}</td><td><code>{$c->ip}</code></td><td>";
        // Check if our target domain uses this IP
        if (isset($aRecord) && $aRecord === $c->ip) {
            echo "<span class='success'>MATCHES $targetDomain</span>";
        } else {
            echo "<span class='warning'>No Active Match</span>";
        }
        echo "</td></tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<span class='error'>No infrastructure clusters defined in WHMCS.</span>";
}
echo "</div>";

// Section: AJAX Authentication Simulation
echo "<div class='card'>";
echo "<h3>5. AJAX Security Simulation</h3>";
$simAccId = 2; // From your screenshot
echo "Simulating request for Account ID: <code>$simAccId</code> and Client ID: <code>$clientId</code>...<br>";

$testAcc = Capsule::table('mod_cloudflare_user_accounts')->where('id', $simAccId)->where('client_id', $clientId)->first();
if ($testAcc) {
    echo "Query Result: <span class='success'>MATCH FOUND</span> (Name: {$testAcc->name})<br>";
    echo "This request SHOULD succeed in the main module.<br>";
} else {
    echo "Query Result: <span class='error'>NOT FOUND</span><br>";
    echo "Possible Reason: <br>";
    $realAcc = Capsule::table('mod_cloudflare_user_accounts')->where('id', $simAccId)->first();
    if ($realAcc) {
        echo "- Account #$simAccId exists, but it belongs to Client ID: <b class='error'>{$realAcc->client_id}</b> (not $clientId)<br>";
    } else {
        echo "- Account #$simAccId does not exist in the database at all.<br>";
    }
}
echo "</div>";
