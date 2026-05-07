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

// Section: Specific Domain Check
echo "<div class='card'>";
echo "<h3>2. Targeted Domain Diagnostic</h3>";
$targetDomain = $_GET['domain'] ?: "gottaexchange.org";
$targetAcc = Capsule::table('mod_cloudflare_user_accounts')->where('client_id', function($query) use ($targetDomain) {
    $query->select('userid')->from('tbldomains')->where('domain', $targetDomain);
})->first();

if (!$targetAcc) $targetAcc = Capsule::table('mod_cloudflare_user_accounts')->first();

if ($targetAcc) {
    try {
        $api = new API($targetAcc->api_token, $targetAcc->email);
        $zoneId = $api->getZoneId($targetDomain);
        
        if ($zoneId) {
            echo "Zone ID for $targetDomain: <span class='success'>$zoneId</span><br>";
            $records = $api->getDNSRecords($zoneId);
            
            echo "<h4>Current DNS Records on Cloudflare:</h4><table><thead><tr><th>Type</th><th>Name</th><th>Content</th><th>Proxy</th></tr></thead><tbody>";
            foreach ($records['result'] as $r) {
                echo "<tr><td>{$r['type']}</td><td>{$r['name']}</td><td>" . substr($r['content'], 0, 50) . "</td><td>" . ($r['proxied'] ? '✅' : '❌') . "</td></tr>";
            }
            echo "</tbody></table>";

            // Find matching cluster
            $aRecord = null;
            foreach ($records['result'] as $r) {
                if ($r['type'] === 'A' && ($r['name'] === $targetDomain || $r['name'] === 'www.'.$targetDomain)) {
                    $aRecord = $r['content'];
                    break;
                }
            }

            if ($aRecord) {
                $infra = Capsule::table('mod_cloudflare_infrastructure')->where('ip', $aRecord)->first();
                if ($infra) {
                    echo "<br>Cluster Match: <span class='success'>FOUND (Cluster: {$infra->name}, ID: {$infra->id})</span>";
                    
                    // Simulation Section
                    echo "<h4>3. Template Sync Simulation (Against Cluster: {$infra->name})</h4>";
                    $templates = Capsule::table('mod_cloudflare_templates')->where('infra_id', $infra->id)->get();
                    echo "<table><thead><tr><th>Template Record</th><th>Status</th><th>Action Required</th></tr></thead><tbody>";
                    foreach ($templates as $t) {
                        $targetName = str_replace(['{domain}', '{ip}'], [$targetDomain, $infra->ip], $t->name);
                        $targetContent = str_replace(['{domain}', '{ip}'], [$targetDomain, $infra->ip], $t->content);
                        
                        $normalizedTarget = $targetName === '@' ? $targetDomain : (strpos($targetName, '.') === false ? $targetName . '.' . $targetDomain : $targetName);
                        
                        $foundMatch = false;
                        $needsUpdate = false;
                        foreach ($records['result'] as $er) {
                            if ($er['type'] == $t->type && ($er['name'] == $normalizedTarget || $er['name'] == $targetName)) {
                                $foundMatch = true;
                                if (trim($er['content']) != trim($targetContent) || $er['proxied'] != $t->proxied) {
                                    $needsUpdate = true;
                                }
                                break;
                            }
                        }

                        echo "<tr><td>{$t->type} {$targetName}</td>";
                        if ($foundMatch) {
                            if ($needsUpdate) echo "<td><span class='warning'>Mismatched</span></td><td><span class='info'>UPDATE</span></td>";
                            else echo "<td><span class='success'>Synced</span></td><td><span>None</span></td>";
                        } else {
                            echo "<td><span class='error'>Missing</span></td><td><span class='success'>ADD</span></td>";
                        }
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<br>Cluster Match: <span class='warning'>NONE (A record points to {$aRecord} which is not a registered infrastructure IP)</span>";
                }
            }
        } else {
            echo "Zone Check: <span class='error'>Domain not found in the connected Cloudflare accounts</span><br>";
        }
    } catch (\Exception $e) {
        echo "API Error: <span class='error'>" . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>No Cloudflare accounts found.</span>";
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

// Section: Live Sync Simulation (koorav.com)
echo "<div class='card'>";
echo "<h3>7. Full Sync Simulation (koorav.com)</h3>";
$testDomain = "koorav.com";
echo "Starting simulation for <b>$testDomain</b>...<br>";

try {
    $targetAcc = Capsule::table('mod_cloudflare_user_accounts')->where('client_id', $clientId)->first();
    if (!$targetAcc) throw new Exception("No accounts linked to your client ID.");

    $api = new API($targetAcc->api_token, $targetAcc->email);
    $zoneId = $api->getZoneId($testDomain);
    echo "1. Zone ID for $testDomain: <span class='info'>$zoneId</span><br>";

    // IP Detection
    $infraId = null;
    $dnsRecords = $api->getDNSRecords($zoneId);
    foreach ($dnsRecords['result'] as $r) {
        if ($r['type'] === 'A' && ($r['name'] === $testDomain || $r['name'] === 'www.'.$testDomain)) {
            $foundIp = $r['content'];
            $infraId = Capsule::table('mod_cloudflare_infrastructure')->where('ip', $foundIp)->value('id');
            echo "2. Detected A-Record IP: <code>$foundIp</code> -> Cluster ID: <span class='info'>$infraId</span><br>";
            break;
        }
    }

    if ($infraId) {
        $templates = Capsule::table('mod_cloudflare_templates')->where('infra_id', $infraId)->get();
        echo "3. Templates found in DB for ID $infraId: <b class='success'>" . $templates->count() . "</b><br>";
        
        foreach ($templates as $t) {
            $finalName = str_replace(['{domain}', '{ip}'], [$testDomain, $foundIp], $t->name);
            $finalContent = str_replace(['{domain}', '{ip}'], [$testDomain, $foundIp], $t->content);
            echo "- WOULD SYNC: <code>$finalName</code> ({$t->type}) -> <code>$finalContent</code><br>";
        }
    } else {
        echo "2. <span class='error'>FAIL: No cluster IP match found for $testDomain</span><br>";
    }

} catch (\Exception $e) {
    echo "Simulation Failed: <span class='error'>" . $e->getMessage() . "</span>";
}
echo "</div>";
