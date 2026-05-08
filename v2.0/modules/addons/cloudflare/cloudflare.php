<?php
/**
 * Cloudflare WHMCS Core Integration Addon
 *
 * @package    WHMCS
 * @author     ikenna julian
 * @copyright  Copyright (c) 2026
 * @license    MIT
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Helpers.php';
use WHMCS\Module\Addon\Cloudflare\Helpers;

// Self-healing Schema (Runs on every load to ensure DB integrity)
try {
    if (Capsule::schema()->hasTable('mod_cloudflare_user_accounts')) {
        if (!Capsule::schema()->hasColumn('mod_cloudflare_user_accounts', 'account_id')) {
            Capsule::schema()->table('mod_cloudflare_user_accounts', function($table) {
                $table->string('account_id', 255)->nullable()->after('api_token');
            });
        }
        // Force email to be nullable via raw SQL (more robust than ->change())
        Capsule::statement("ALTER TABLE mod_cloudflare_user_accounts MODIFY email VARCHAR(255) NULL");
    }
} catch (\Exception $e) {}

function cloudflare_config() {
    return [
        'name' => 'Cloudflare Manager',
        'description' => 'Strict Infrastructure-based Cloudflare management.',
        'author' => 'iheanyi ikenna (ijidev)',
        'language' => 'english',
        'version' => '2.2',
        'fields' => [
            'fetch_all_domains' => [
                'FriendlyName' => 'Legacy Settings (Deprecated)',
                'Type' => 'description',
                'Description' => 'Settings have moved to the Module Addon page for better control.',
            ]
        ]
    ];
}

function cloudflare_activate() {
    try {
        // Infrastructure table
        if (!Capsule::schema()->hasTable('mod_cloudflare_infrastructure')) {
            Capsule::schema()->create('mod_cloudflare_infrastructure', function ($table) {
                $table->increments('id');
                $table->integer('server_id')->default(0);
                $table->string('name', 255);
                $table->string('ip', 64);
                $table->text('description')->nullable();
            });
        }

        // DNS templates
        if (!Capsule::schema()->hasTable('mod_cloudflare_templates')) {
            Capsule::schema()->create('mod_cloudflare_templates', function ($table) {
                $table->increments('id');
                $table->integer('infra_id');
                $table->string('type', 10);
                $table->string('name', 255);
                $table->text('content');
                $table->integer('ttl')->default(1);
                $table->boolean('proxied')->default(true);
            });
        }

        // Product-Infrastructure Mapping
        if (!Capsule::schema()->hasTable('mod_cloudflare_product_infra')) {
            Capsule::schema()->create('mod_cloudflare_product_infra', function ($table) {
                $table->integer('product_id')->primary();
                $table->integer('infra_id');
            });
        }
        // Domain-Infrastructure Mapping (For detached domains or overrides)
        if (!Capsule::schema()->hasTable('mod_cloudflare_domain_infra')) {
            Capsule::schema()->create('mod_cloudflare_domain_infra', function ($table) {
                $table->string('domain', 255)->primary();
                $table->integer('infra_id');
            });
        }

        // BYOT Accounts
        if (!Capsule::schema()->hasTable('mod_cloudflare_user_accounts')) {
            Capsule::schema()->create('mod_cloudflare_user_accounts', function ($table) {
                $table->increments('id');
                $table->integer('client_id');
                $table->string('name', 255);
                $table->string('email', 255);
                $table->string('api_token', 255);
                $table->string('account_id', 255)->nullable();
                $table->timestamps();
            });
        }

        // Infrastructure IP History
        if (!Capsule::schema()->hasTable('mod_cloudflare_infrastructure_ips')) {
            Capsule::schema()->create('mod_cloudflare_infrastructure_ips', function ($table) {
                $table->increments('id');
                $table->integer('infra_id');
                $table->string('ip', 64);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Module Logs
        if (!Capsule::schema()->hasTable('mod_cloudflare_logs')) {
            Capsule::schema()->create('mod_cloudflare_logs', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->default(0);
                $table->string('domain', 255)->nullable();
                $table->string('action', 64);
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Default settings
        $defaults = [
            'fetch_all_domains' => 'off',
            'sync_without_product' => 'off',
            'verify_addon_domains' => 'on',
            'ip_retention_count' => '3'
        ];
        foreach ($defaults as $k => $v) {
            if (!Capsule::table('mod_cloudflare_settings')->where('setting', $k)->exists()) {
                Capsule::table('mod_cloudflare_settings')->insert(['setting' => $k, 'value' => $v]);
            }
        }

        return ['status' => 'success', 'description' => 'Activated successfully with IP History and Logs.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function cloudflare_deactivate() {
    return ['status' => 'success', 'description' => 'Deactivated.'];
}

function cloudflare_log($clientId, $domain, $action, $details) {
    Capsule::table('mod_cloudflare_logs')->insert([
        'client_id' => $clientId,
        'domain' => $domain,
        'action' => $action,
        'details' => is_array($details) ? json_encode($details) : $details,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

function cloudflare_output($vars) {
    // Self-healing Database Schema (Aggressive Check)
    try {
        // Infrastructure IP History
        if (!Capsule::schema()->hasTable('mod_cloudflare_infrastructure_ips')) {
            Capsule::schema()->create('mod_cloudflare_infrastructure_ips', function ($table) {
                $table->increments('id');
                $table->integer('infra_id');
                $table->string('ip', 64);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Module Logs
        if (!Capsule::schema()->hasTable('mod_cloudflare_logs')) {
            Capsule::schema()->create('mod_cloudflare_logs', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->default(0);
                $table->string('domain', 255)->nullable();
                $table->string('action', 64);
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // mod_cloudflare_templates
        if (!Capsule::schema()->hasTable('mod_cloudflare_templates')) {
            Capsule::schema()->create('mod_cloudflare_templates', function ($table) {
                $table->increments('id');
                $table->integer('infra_id');
                $table->string('type', 10);
                $table->string('name', 255);
                $table->text('content');
                $table->integer('ttl')->default(1);
                $table->integer('priority')->nullable();
                $table->boolean('proxied')->default(true);
            });
        } elseif (!Capsule::schema()->hasColumn('mod_cloudflare_templates', 'priority')) {
            Capsule::schema()->table('mod_cloudflare_templates', function ($table) {
                $table->integer('priority')->nullable()->after('ttl');
            });
        }

        // mod_cloudflare_infrastructure
        if (!Capsule::schema()->hasTable('mod_cloudflare_infrastructure')) {
            Capsule::schema()->create('mod_cloudflare_infrastructure', function ($table) {
                $table->increments('id');
                $table->integer('server_id')->default(0);
                $table->string('name', 255);
                $table->string('ip', 64);
                $table->text('description')->nullable();
            });
        }

        // mod_cloudflare_domain_infra
        if (!Capsule::schema()->hasTable('mod_cloudflare_domain_infra')) {
            Capsule::schema()->create('mod_cloudflare_domain_infra', function ($table) {
                $table->string('domain', 255)->primary();
                $table->integer('infra_id');
            });
        }
    } catch (\Exception $e) {
        // Silently log or display if admin
    }

    $action = $_REQUEST['action'] ?? 'infra';
    $modulelink = $vars['modulelink'];

    // Helper for self-healing (Advanced Scan & IP Migration)
    $repairInfra = function($infraId, $force = false) {
        $infra = Capsule::table('mod_cloudflare_infrastructure')->where('id', $infraId)->first();
        if (!$infra) return 0;
        
        $historicalIps = Capsule::table('mod_cloudflare_infrastructure_ips')->where('infra_id', $infraId)->pluck('ip')->toArray();
        $allTargetIps = array_unique(array_merge([$infra->ip], $historicalIps));
        $syncWithoutProduct = $force ?: (Capsule::table('mod_cloudflare_settings')->where('setting', 'sync_without_product')->value('value') == 'on');

        $repaired = 0;
        require_once __DIR__ . '/lib/API.php';

        // 1. Collect all WHMCS Domains
        $hostingDomains = Capsule::table('tblhosting')->where('domainstatus', 'Active')->select('domain', 'packageid', 'userid')->get();
        $otherDomains = Capsule::table('tbldomains')->where('status', 'Active')->select('domain', 'userid')->get();
        
        $allDomains = [];
        foreach ($hostingDomains as $h) { if(trim($h->domain)) $allDomains[trim($h->domain)] = ['type' => 'product', 'id' => $h->packageid, 'user' => $h->userid]; }
        foreach ($otherDomains as $d) { if(trim($d->domain) && !isset($allDomains[trim($d->domain)])) $allDomains[trim($d->domain)] = ['type' => 'domain', 'id' => null, 'user' => $d->userid]; }

        // Cache CF accounts to avoid repeated queries
        $cfAccounts = [];
        $accounts = Capsule::table('mod_cloudflare_user_accounts')->get();
        foreach ($accounts as $acc) {
            $cfAccounts[$acc->client_id][] = $acc;
        }

        foreach ($allDomains as $domain => $data) {
            $isProductLink = ($data['type'] == 'product');
            $existingProductLink = $isProductLink ? Capsule::table('mod_cloudflare_product_infra')->where('product_id', $data['id'])->first() : null;
            $existingDomainLink = Capsule::table('mod_cloudflare_domain_infra')->where('domain', $domain)->first();

            // Skip if already correctly linked to THIS cluster
            if ($existingProductLink && $existingProductLink->infra_id == $infraId) continue;
            if ($existingDomainLink && $existingDomainLink->infra_id == $infraId) continue;
            
            // If strictly checking products and it's not a product link, skip
            if (!$isProductLink && !$syncWithoutProduct) continue;

            $isMatch = false;

            // Check 1: Public DNS
            try {
                $publicDns = dns_get_record($domain, DNS_A);
                foreach ($publicDns as $rec) {
                    if (in_array($rec['ip'], $allTargetIps)) { $isMatch = true; break; }
                }
            } catch (\Exception $e) {}

            // Check 2: Deep Scan via CF API (if public DNS failed, e.g., proxied)
            if (!$isMatch && isset($cfAccounts[$data['user']])) {
                foreach ($cfAccounts[$data['user']] as $acc) {
                    try {
                        $api = new \WHMCS\Module\Addon\Cloudflare\API($acc->api_token, $acc->email);
                        $zoneId = $api->getZoneId($domain);
                        if ($zoneId) {
                            $records = $api->getDNSRecords($zoneId);
                            foreach (($records['result'] ?? []) as $r) {
                                if ($r['type'] === 'A' && in_array($r['content'], $allTargetIps)) {
                                    $isMatch = true; break 2;
                                }
                            }
                        }
                    } catch (\Exception $e) {}
                }
            }
            if ($isMatch) {
                if ($isProductLink) {
                    // Priority: If linked to a product, ensure any "detached" domain link is removed
                    Capsule::table('mod_cloudflare_domain_infra')->where('domain', $domain)->delete();
                    Capsule::table('mod_cloudflare_product_infra')->updateOrInsert(['product_id' => $data['id']], ['infra_id' => $infraId]);
                    $repaired++;
                } else {
                    // Only insert detached link if NO product link exists for ANY domain using this product
                    $productLinked = Capsule::table('tblhosting')->where('domain', $domain)->where('domainstatus', 'Active')->first();
                    if ($productLinked && Capsule::table('mod_cloudflare_product_infra')->where('product_id', $productLinked->packageid)->exists()) {
                        // Already covered by product link, skip detached insert
                    } else {
                        Capsule::table('mod_cloudflare_domain_infra')->updateOrInsert(['domain' => $domain], ['infra_id' => $infraId]);
                        $repaired++;
                        cloudflare_log($data['user'], $domain, 'AUTO_MAP', "Detached domain '{$domain}' matched to cluster IP. Linked successfully.");
                    }
                }
            }
        }

        // Aggressive Cleanup: Remove any entries from mod_cloudflare_domain_infra that are now covered by product links
        try {
            $productLinkedDomains = Capsule::table('tblhosting')
                ->join('mod_cloudflare_product_infra', 'tblhosting.packageid', '=', 'mod_cloudflare_product_infra.product_id')
                ->where('tblhosting.domainstatus', 'Active')
                ->pluck('tblhosting.domain')
                ->toArray();
            
            if (!empty($productLinkedDomains)) {
                Capsule::table('mod_cloudflare_domain_infra')
                    ->whereIn('domain', array_filter($productLinkedDomains))
                    ->delete();
            }
        } catch (\Exception $e) {}

        return $repaired;
    };

    // Helper for WHM Addon Verification
    $verifyAddonDomain = function($serviceId, $domain) {
        $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
        if (!$service) return false;
        $server = Capsule::table('tblservers')->where('id', $service->server)->first();
        if (!$server) return false;

        $user = $server->username;
        $pass = decrypt($server->password);
        $host = $server->ipaddress ?: $server->hostname;
        $port = 2087;

        $url = "https://$host:$port/json-api/listaddondomains?api.version=1&user=" . urlencode($service->username);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: WHM $user:" . str_replace("\r\n", "", $pass)]);
        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if (isset($data['data']['addon'])) {
            foreach ($data['data']['addon'] as $addon) {
                if ($addon['domain'] == $domain) return true;
            }
        }
        return false;
    };

    // AJAX Operations (Admin)
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        header('Content-Type: application/json');
        try {
            switch ($_POST['op']) {
                case 'add_template':
                    if (empty($_POST['name']) || empty($_POST['content'])) throw new Exception("Name and Content are required.");
                    $id = Capsule::table('mod_cloudflare_templates')->insertGetId([
                        'infra_id' => (int)$_POST['infra_id'],
                        'type' => $_POST['type'],
                        'name' => $_POST['name'],
                        'content' => $_POST['content'],
                        'ttl' => (int)$_POST['ttl'],
                        'priority' => $_POST['priority'] !== '' ? (int)$_POST['priority'] : null,
                        'proxied' => $_POST['proxied'] == 'true' ? 1 : 0,
                    ]);
                    echo json_encode(['success' => true, 'id' => $id]); exit;

                case 'delete_template':
                    Capsule::table('mod_cloudflare_templates')->where('id', (int)$_POST['id'])->delete();
                    echo json_encode(['success' => true]); exit;

                case 'update_template':
                    if (empty($_POST['name']) || empty($_POST['content'])) throw new Exception("Name and Content are required.");
                    Capsule::table('mod_cloudflare_templates')->where('id', (int)$_POST['id'])->update([
                        'type' => $_POST['type'],
                        'name' => $_POST['name'],
                        'content' => $_POST['content'],
                        'ttl' => (int)$_POST['ttl'],
                        'priority' => $_POST['priority'] !== '' ? (int)$_POST['priority'] : null,
                        'proxied' => $_POST['proxied'] == 'true' ? 1 : 0,
                    ]);
                    echo json_encode(['success' => true]); exit;

                case 'update_products':
                    $infraId = (int)$_POST['infra_id'];
                    $products = $_POST['products'] ?? [];
                    error_log("Cloudflare Debug: Saving products for Infra $infraId. Products: " . print_r($products, true));
                    try {
                        Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $infraId)->delete();
                        if (is_array($products)) {
                            foreach ($products as $pid) {
                                // Double check if product is linked elsewhere to avoid PK violation
                                Capsule::table('mod_cloudflare_product_infra')->where('product_id', (int)$pid)->delete();
                                Capsule::table('mod_cloudflare_product_infra')->insert(['product_id' => (int)$pid, 'infra_id' => $infraId]);
                            }
                        }
                        echo json_encode(['success' => true]); exit;
                    } catch (\Exception $e) {
                        error_log("Cloudflare Error: Failed to save products: " . $e->getMessage());
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
                    }

                case 'repair_infra':
                    $id = (int)$_POST['id'];
                    $count = $repairInfra($id, true); // Bypass setting
                    echo json_encode(['success' => true, 'repaired' => $count]); exit;

                case 'repair_all':
                    $infras = Capsule::table('mod_cloudflare_infrastructure')->get();
                    $total = 0;
                    foreach ($infras as $i) $total += $repairInfra($i->id, true); // Bypass setting
                    echo json_encode(['success' => true, 'repaired' => $total]); exit;
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
        }
    }

    if ($_POST) {
        if ($action == 'add_infra' || $action == 'update_infra') {
            $id = (int)$_POST['id'];
            $name = $_POST['name'];
            $ip = $_POST['ip'];
            if ($_POST['server_id']) {
                $server = Capsule::table('tblservers')->where('id', $_POST['server_id'])->first();
                if ($server) { $name = $server->name; $ip = $server->ipaddress; }
            }
            
            if ($action == 'update_infra') {
                $old = Capsule::table('mod_cloudflare_infrastructure')->where('id', $id)->first();
                if ($old && $old->ip != $ip) {
                    Capsule::table('mod_cloudflare_infrastructure_ips')->insert(['infra_id' => $id, 'ip' => $old->ip]);
                    // Limit retention
                    $limit = (int)Capsule::table('mod_cloudflare_settings')->where('setting', 'ip_retention_count')->value('value') ?: 3;
                    $history = Capsule::table('mod_cloudflare_infrastructure_ips')->where('infra_id', $id)->orderBy('id', 'desc')->get();
                    if ($history->count() > $limit) {
                        Capsule::table('mod_cloudflare_infrastructure_ips')->where('id', '<=', $history->last()->id)->where('infra_id', $id)->delete();
                    }
                }
                Capsule::table('mod_cloudflare_infrastructure')->where('id', $id)->update(['server_id' => (int)$_POST['server_id'], 'name' => $name, 'ip' => $ip, 'description' => $_POST['description']]);
                header("Location: $modulelink&action=manage_infra&id=$id&success=infra_updated"); exit;
            } else {
                Capsule::table('mod_cloudflare_infrastructure')->insert(['server_id' => (int)$_POST['server_id'], 'name' => $name, 'ip' => $ip, 'description' => $_POST['description']]);
                header("Location: $modulelink&action=infra&success=infra_added"); exit;
            }
        }

        if ($action == 'mass_sync_infra') {
            try {
                $infraId = (int)$_POST['infra_id'];
                if (!$infraId) throw new \Exception("Invalid Infrastructure ID.");
                
                $infra = Capsule::table('mod_cloudflare_infrastructure')->where('id', $infraId)->first();
                if (!$infra) throw new \Exception("Infrastructure cluster not found.");
                
                $templates = Capsule::table('mod_cloudflare_templates')->where('infra_id', $infraId)->get();
                $linkedProducts = Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $infraId)->pluck('product_id')->toArray();
                
                $domainsToSync = [];
                if (!empty($linkedProducts)) {
                    $hosting = Capsule::table('tblhosting')->whereIn('packageid', $linkedProducts)->where('domainstatus', 'Active')->select('domain', 'userid')->get();
                    foreach ($hosting as $h) { if (trim($h->domain)) $domainsToSync[trim($h->domain)] = $h->userid; }
                }
                
                $detached = Capsule::table('mod_cloudflare_domain_infra')->where('infra_id', $infraId)->get();
                foreach ($detached as $d) {
                    $whmcsDomain = Capsule::table('tbldomains')->where('domain', $d->domain)->where('status', 'Active')->first();
                    if ($whmcsDomain && !isset($domainsToSync[$d->domain])) $domainsToSync[$d->domain] = $whmcsDomain->userid;
                }

                require_once __DIR__ . '/lib/API.php';
                $count = 0;
                $globalErrors = [];
                
                foreach ($domainsToSync as $domain => $userid) {
                    try {
                        $acc = Capsule::table('mod_cloudflare_user_accounts')->where('client_id', $userid)->first();
                        if (!$acc) {
                            $globalErrors[] = "Skipped $domain: No Cloudflare account linked to client #$userid";
                            continue;
                        }
                        
                        $api = new \WHMCS\Module\Addon\Cloudflare\API($acc->api_token, $acc->email);
                        $zid = $api->getZoneId($domain);
                        if (!$zid) {
                            $globalErrors[] = "Skipped $domain: Zone not found in Cloudflare account.";
                            continue;
                        }

                        $existingRecords = $api->getDNSRecords($zid);
                        $appliedCount = 0;
                        $appliedLog = [];
                        
                        foreach ($templates as $t) {
                            $targetName = str_replace(['{domain}', '{ip}'], [$domain, $infra->ip], $t->name);
                            $targetContent = str_replace(['{domain}', '{ip}'], [$domain, $infra->ip], $t->content);
                            
                            $normalizedTarget = $targetName;
                            if ($targetName === '@') $normalizedTarget = $domain;
                            elseif (strpos($targetName, '.') === false) $normalizedTarget = $targetName . '.' . $domain;

                            $found = false;
                            foreach (($existingRecords['result'] ?? []) as $er) {
                                if ($er['type'] == $t->type && ($er['name'] == $normalizedTarget || $er['name'] == $targetName)) {
                                    $mismatch = (trim($er['content']) != trim($targetContent) || $er['proxied'] != $t->proxied);
                                    if ($t->type === 'MX' && isset($er['priority']) && $er['priority'] != $t->priority) $mismatch = true;
                                    
                                    if ($mismatch) {
                                        $api->updateDNSRecord($zid, $er['id'], $t->type, $targetName, $targetContent, $t->ttl, $t->proxied, $t->priority);
                                        $appliedLog[] = "Updated {$t->type} {$targetName} -> {$targetContent} (Prio: ".($t->priority??'0').")";
                                        $appliedCount++;
                                    } else {
                                        $appliedLog[] = "Matches {$t->type} {$targetName}";
                                    }
                                    $found = true; break;
                                }
                            }
                            
                            if (!$found) {
                                $api->addDNSRecord($zid, $t->type, $targetName, $targetContent, $t->ttl, $t->proxied, $t->priority);
                                $appliedLog[] = "Added {$t->type} {$targetName} -> {$targetContent} (Prio: ".($t->priority??'0').")";
                                $appliedCount++;
                            }
                        }
                        
                        if ($appliedCount > 0) {
                            $logMessage = "Mass Sync: Applied changes to $domain\n" . implode("\n", $appliedLog);
                            cloudflare_log($userid, $domain, 'SYNC_DNS', $logMessage);
                            $count++;
                        }
                    } catch (\Exception $de) {
                        $globalErrors[] = "Error syncing $domain: " . $de->getMessage();
                    }
                }
                
                if (!empty($globalErrors)) {
                    $errorSummary = "Partial success. Encounted errors:\n" . implode("\n", $globalErrors);
                    cloudflare_log(0, 'SYSTEM', 'SYNC_ERROR', $errorSummary);
                }
                
                header("Location: $modulelink&action=manage_infra&id=$infraId&success=mass_sync&count=$count" . (!empty($globalErrors) ? "&error_count=".count($globalErrors) : "")); exit;
            } catch (\Exception $ge) {
                header("Location: $modulelink&action=infra&error=" . urlencode($ge->getMessage())); exit;
            }
        }
        
        if ($action == 'delete_infra') {
            $id = (int)$_POST['id'];
            Capsule::table('mod_cloudflare_infrastructure')->where('id', $id)->delete();
            Capsule::table('mod_cloudflare_templates')->where('infra_id', $id)->delete();
            header("Location: $modulelink&action=infra&success=infra_deleted"); exit;
        }

        if ($action == 'delete_template') {
            $t = Capsule::table('mod_cloudflare_templates')->where('id', (int)$_POST['id'])->first();
            Capsule::table('mod_cloudflare_templates')->where('id', (int)$_POST['id'])->delete();
            header("Location: $modulelink&action=manage_infra&id=" . $t->infra_id . "&tab=templates&success=1"); exit;
        }

        if ($action == 'update_infra_products') {
            $infraId = (int)$_POST['infra_id'];
            Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $infraId)->delete();
            if (isset($_POST['products']) && is_array($_POST['products'])) {
                foreach ($_POST['products'] as $pid) {
                    Capsule::table('mod_cloudflare_product_infra')->insert(['product_id' => (int)$pid, 'infra_id' => $infraId]);
                }
            }
            header("Location: $modulelink&action=manage_infra&id=$infraId&tab=products&success=1"); exit;
        }

        if ($action == 'save_settings') {
            foreach ($_POST['settings'] as $key => $val) {
                Capsule::table('mod_cloudflare_settings')->updateOrInsert(['setting' => $key], ['value' => $val]);
            }
            header("Location: $modulelink&action=settings&success=1"); exit;
        }
    }

    // Admin UI
    ?>
    <style>
        .cf-admin-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
        .cf-admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .cf-tabs { margin-bottom: 20px; border-bottom: 1px solid #dee2e6; }
        .cf-tabs a { display: inline-block; padding: 10px 20px; text-decoration: none; color: #64748b; font-weight: 600; border-bottom: 3px solid transparent; }
        .cf-tabs a.active { color: #f38020; border-bottom-color: #f38020; }
        .cf-table-admin { width: 100%; border-collapse: collapse; }
        .cf-table-admin th { background: #f8fafc; padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b; }
        .cf-table-admin td { padding: 12px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
    </style>

    <div class="cf-tabs">
        <a href="<?=$modulelink?>&action=infra" class="<?=($action=='infra' || $action=='manage_infra')?'active':''?>">Infrastructure Overview</a>
        <a href="<?=$modulelink?>&action=sync" class="<?=$action=='sync'?'active':''?>">Sync Domain Assets</a>
        <a href="<?=$modulelink?>&action=logs" class="<?=$action=='logs'?'active':''?>">System Logs</a>
        <a href="<?=$modulelink?>&action=settings" class="<?=$action=='settings'?'active':''?>">General Settings</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] == 'mass_sync'): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <strong>Mass Sync Complete!</strong> Successfully synchronized <?=(int)$_GET['count']?> domains.
                <?php if (isset($_GET['error_count'])): ?>
                    <br><i class="fa fa-exclamation-triangle"></i> Encounted <?=(int)$_GET['error_count']?> errors. Check <b>System Logs</b> for details.
                <?php endif; ?>
            </div>
        <?php elseif ($_GET['success'] == 'infra_updated'): ?>
             <div class="alert alert-success">Cluster configuration updated successfully.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><i class="fa fa-times-circle"></i> <strong>Error:</strong> <?=htmlspecialchars($_GET['error'])?></div>
    <?php endif; ?>

    <script>
        function repairAll(btn) {
            const originalHtml = $(btn).html();
            $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            $.post('<?=$modulelink?>', { ajax: '1', op: 'repair_all' }, function(res) {
                $(btn).prop('disabled', false).html(originalHtml);
                if (res.success) {
                    alert('Global Sync complete. Repaired ' + res.repaired + ' assets.');
                    location.reload();
                } else alert('Error: ' + res.message);
            });
        }
        function repairInfra(btn, id) {
            const originalHtml = $(btn).html();
            $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Scanning...');
            $.post('<?=$modulelink?>', { ajax: '1', op: 'repair_infra', id: id }, function(res) {
                $(btn).prop('disabled', false).html(originalHtml);
                if (res.success) {
                    alert('Cluster Sync complete. Repaired ' + res.repaired + ' assets.');
                    location.reload();
                } else alert('Error: ' + res.message);
            });
        }
    </script>

    <?php if ($action == 'infra'): 
        $infras = Capsule::table('mod_cloudflare_infrastructure')->get();
        $whmcsServers = Capsule::table('tblservers')->orderBy('name', 'asc')->get();
    ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-server"></i> Active Infrastructure</h3>
            <div>
                <button class="btn btn-warning btn-sm" onclick="repairAll(this)"><i class="fa fa-magic"></i> Global Sync Hub</button>
                <button class="btn btn-primary btn-sm" onclick="$('#addInfraForm').toggle()"><i class="fa fa-plus"></i> New Cluster</button>
            </div>
        </div>
        </div>
        <div id="addInfraForm" style="display:none; margin-bottom: 20px; padding: 20px; background: #f8fafc; border-radius: 8px;">
            <form method="post" action="<?=$modulelink?>&action=add_infra">
                <div class="row">
                    <div class="col-md-4"><label>WHMCS Server</label><select name="server_id" class="form-control"><option value="">-- Manual --</option><?php foreach($whmcsServers as $s): ?><option value="<?=$s->id?>"><?=$s->name?> (<?=$s->ipaddress?>)</option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label>Name</label><input type="text" name="name" class="form-control"></div>
                    <div class="col-md-4"><label>IP</label><input type="text" name="ip" class="form-control"></div>
                </div>
                <button type="submit" class="btn btn-success" style="margin-top:15px;">Create Cluster</button>
            </form>
        </div>
        <table class="cf-table-admin">
            <thead><tr><th>Cluster Name</th><th>Primary IP</th><th>Templates</th><th>Linked Products</th><th>Active Assets</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($infras as $i): 
                    $tCount = Capsule::table('mod_cloudflare_templates')->where('infra_id', $i->id)->count();
                    $pCount = Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $i->id)->count();
                    
                    $linkedProducts = Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $i->id)->pluck('product_id')->toArray();
                    $aCount = !empty($linkedProducts) ? Capsule::table('tblhosting')->whereIn('packageid', $linkedProducts)->where('domainstatus', 'Active')->count() : 0;
                ?>
                <tr>
                    <td><strong><?=$i->name?></strong></td>
                    <td><code><?=$i->ip?></code></td>
                    <td><span class="label label-info"><?=$tCount?> Records</span></td>
                    <td><span class="label label-warning"><?=$pCount?> Plans</span></td>
                    <td><span class="label label-success"><?=$aCount?> Assets</span></td>
                    <td style="text-align:right;">
                        <div class="dropdown" style="display:inline-block;">
                            <button class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="<?=$modulelink?>&action=manage_infra&id=<?=$i->id?>&tab=settings"><i class="fa fa-edit"></i> Edit Cluster</a></li>
                                <li><a href="<?=$modulelink?>&action=repair_infra&id=<?=$i->id?>"><i class="fa fa-refresh"></i> Sync Products</a></li>
                                <li><a href="<?=$modulelink?>&action=mass_sync_infra&infra_id=<?=$i->id?>" onclick="return confirm('Sync DNS for all domains on this cluster?')"><i class="fa fa-cloud"></i> Sync DNS Hub</a></li>
                                <li class="divider"></li>
                                <li><a href="<?=$modulelink?>&action=delete_infra&id=<?=$i->id?>" onclick="return confirm('Delete cluster?')" style="color:red;"><i class="fa fa-trash"></i> Delete</a></li>
                            </ul>
                        </div>
                        <a href="<?=$modulelink?>&action=manage_infra&id=<?=$i->id?>" class="btn btn-primary btn-xs">Manage</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($action == 'manage_infra'): 
        $id = (int)$_GET['id']; 
        $infra = Capsule::table('mod_cloudflare_infrastructure')->where('id', $id)->first();
        $subtab = $_GET['tab'] ?? 'templates';
    ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-cogs"></i> Managing Cluster: <?=$infra->name?></h3>
            <div style="display:flex; gap:10px;">
                <a href="<?=$modulelink?>&action=infra" class="btn btn-default btn-sm">Back to Overview</a>
            </div>
        </div>

        <div class="cf-tabs" style="margin-bottom: 20px;">
            <a href="<?=$modulelink?>&action=manage_infra&id=<?=$id?>&tab=templates" class="<?=$subtab=='templates'?'active':''?>">DNS Templates</a>
            <a href="<?=$modulelink?>&action=manage_infra&id=<?=$id?>&tab=products" class="<?=$subtab=='products'?'active':''?>">Linked Products</a>
            <a href="<?=$modulelink?>&action=manage_infra&id=<?=$id?>&tab=assets" class="<?=$subtab=='assets'?'active':''?>">Linked Assets</a>
            <a href="<?=$modulelink?>&action=manage_infra&id=<?=$id?>&tab=settings" class="<?=$subtab=='settings'?'active':''?>">Cluster Settings</a>
        </div>
        
        <?php if ($subtab == 'settings'): ?>
            <form method="post" action="<?=$modulelink?>&action=update_infra">
                <input type="hidden" name="id" value="<?=$id?>">
                <div class="row">
                    <div class="col-md-4"><label>WHMCS Server</label><select name="server_id" class="form-control"><option value="">-- Manual --</option><?php foreach($whmcsServers as $s): ?><option value="<?=$s->id?>" <?=($infra->server_id==$s->id?'selected':'')?>><?=$s->name?> (<?=$s->ipaddress?>)</option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label>Cluster Name</label><input type="text" name="name" value="<?=$infra->name?>" class="form-control"></div>
                    <div class="col-md-4"><label>Cluster IP</label><input type="text" name="ip" value="<?=$infra->ip?>" class="form-control"></div>
                </div>
                <div class="form-group" style="margin-top:15px;"><label>Description</label><textarea name="description" class="form-control"><?=$infra->description?></textarea></div>
                <button type="submit" class="btn btn-primary">Update Cluster Configuration</button>
            </form>
        <?php elseif ($subtab == 'templates'): ?>
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> Use <code>{domain}</code> for the client domain and <code>{ip}</code> for the cluster IP (<?=$infra->ip?>).
            </div>
            <table class="cf-table-admin">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Content</th>
                        <th>TTL</th>
                        <th>Prio</th>
                        <th>Proxy</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tmpl-list">
                    <?php 
                    $templates = Capsule::table('mod_cloudflare_templates')->where('infra_id', $id)->get();
                    foreach ($templates as $t): ?>
                    <tr id="tmpl-<?=$t->id?>">
                        <td><span class="label label-info"><?=$t->type?></span></td>
                        <td><code><?=$t->name?></code></td>
                        <td><code><?=$t->content?></code></td>
                        <td><?=$t->ttl == 1 ? 'Auto' : $t->ttl?></td>
                        <td><?=$t->type == 'MX' ? $t->priority : '-'?></td>
                        <td><i class="fa fa-circle <?=$t->proxied?'text-success':'text-muted'?>"></i></td>
                        <td style="text-align:right;">
                            <button type="button" class="btn btn-default btn-xs" onclick="openEditModal(<?=htmlspecialchars(json_encode($t))?>)"><i class="fa fa-edit"></i> Edit</button>
                            <button type="button" class="btn btn-danger btn-xs" id="btn-del-<?=$t->id?>" onclick="deleteTemplate(<?=$t->id?>)"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: #fbfbfb; border-top: 2px solid #eee;">
                        <td><select id="new-type" class="form-control input-sm"><option value="A">A</option><option value="CNAME">CNAME</option><option value="MX">MX</option><option value="TXT">TXT</option></select></td>
                        <td><input type="text" id="new-name" class="form-control input-sm" placeholder="@"></td>
                        <td><input type="text" id="new-content" class="form-control input-sm" placeholder="{ip}"></td>
                        <td><input type="text" id="new-ttl" class="form-control input-sm" value="1"></td>
                        <td><input type="number" id="new-priority" class="form-control input-sm" placeholder="10" style="width:60px;"></td>
                        <td><input type="checkbox" id="new-proxied" checked></td>
                        <td style="text-align:right; display:flex; gap:10px; justify-content: flex-end; align-items: center;">
                            <button type="button" id="btnAddTmpl" class="btn btn-success btn-sm" onclick="addTemplate()"><i class="fa fa-plus"></i> Add Record</button>
                            <form method="post" action="<?=$modulelink?>&action=mass_sync_infra" onsubmit="return confirm('Force update ALL domains on this cluster to match these templates?')">
                                <input type="hidden" name="infra_id" value="<?=$id?>">
                                <button type="submit" class="btn btn-warning btn-sm"><i class="fa fa-refresh"></i> Force Sync All Domains</button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Edit Modal -->
            <div id="editTmplModal" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">Edit DNS Template</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="edit-id">
                            <div class="form-group"><label>Type</label><select id="edit-type" class="form-control"><option value="A">A</option><option value="CNAME">CNAME</option><option value="MX">MX</option><option value="TXT">TXT</option></select></div>
                            <div class="form-group"><label>Name</label><input type="text" id="edit-name" class="form-control"></div>
                            <div class="form-group"><label>Content</label><input type="text" id="edit-content" class="form-control"></div>
                            <div class="form-group"><label>TTL</label><input type="text" id="edit-ttl" class="form-control"></div>
                            <div class="form-group" id="edit-priority-wrapper"><label>Priority (MX only)</label><input type="number" id="edit-priority" class="form-control"></div>
                            <div class="form-group"><label><input type="checkbox" id="edit-proxied"> Cloudflare Proxy</label></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="btnSaveTmpl" class="btn btn-primary" onclick="saveTemplate()">Save Changes</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function addTemplate() {
                    const btn = $('#btnAddTmpl');
                    const originalHtml = btn.html();
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                    
                    const data = {
                        ajax: '1', op: 'add_template', infra_id: '<?=$id?>',
                        type: $('#new-type').val(), name: $('#new-name').val(),
                        content: $('#new-content').val(), ttl: $('#new-ttl').val(),
                        priority: $('#new-priority').val(),
                        proxied: $('#new-proxied').is(':checked')
                    };
                    $.post('<?=$modulelink?>', data, function(res) {
                        btn.prop('disabled', false).html(originalHtml);
                        if (res.success) location.reload();
                        else alert('Error: ' + res.message);
                    });
                }
                function openEditModal(data) {
                    $('#edit-id').val(data.id); $('#edit-type').val(data.type);
                    $('#edit-name').val(data.name); $('#edit-content').val(data.content);
                    $('#edit-ttl').val(data.ttl); $('#edit-priority').val(data.priority);
                    $('#edit-proxied').prop('checked', data.proxied == 1);
                    if (data.type === 'MX') $('#edit-priority-wrapper').show(); else $('#edit-priority-wrapper').hide();
                    $('#editTmplModal').modal('show');
                }
                function saveTemplate() {
                    const btn = $('#btnSaveTmpl');
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                    const data = {
                        ajax: '1', op: 'update_template', id: $('#edit-id').val(), infra_id: '<?=$id?>',
                        type: $('#edit-type').val(), name: $('#edit-name').val(),
                        content: $('#edit-content').val(), ttl: $('#edit-ttl').val(),
                        priority: $('#edit-priority').val(),
                        proxied: $('#edit-proxied').is(':checked')
                    };
                    $.post('<?=$modulelink?>', data, function(res) {
                        btn.prop('disabled', false).html('Save Changes');
                        if (res.success) location.reload();
                        else alert('Error: ' + res.message);
                    });
                }
                function deleteTemplate(id) {
                    if (!confirm('Delete this template?')) return;
                    const btn = $('#btn-del-' + id);
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                    $.post('<?=$modulelink?>', { ajax: '1', op: 'delete_template', id: id }, function(res) {
                        if (res.success) $('#tmpl-' + id).remove();
                    });
                }
            </script>
        <?php elseif ($subtab == 'products'): 
            $linked = Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $id)->pluck('product_id')->toArray();
            $products = Capsule::table('tblproducts')->orderBy('name', 'asc')->get();
            
            $owners = Capsule::table('mod_cloudflare_product_infra')
                ->join('mod_cloudflare_infrastructure', 'mod_cloudflare_product_infra.infra_id', '=', 'mod_cloudflare_infrastructure.id')
                ->pluck('mod_cloudflare_infrastructure.name', 'mod_cloudflare_product_infra.product_id')
                ->toArray();
            
            $infra = Capsule::table('mod_cloudflare_infrastructure')->where('id', $id)->first();
        ?>
            <form id="productForm">
                <input type="hidden" name="infra_id" value="<?=$id?>">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="op" value="update_products">
                <div style="max-height: 500px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 20px; background: #fff;">
                    <p class="text-muted" style="margin-bottom: 20px;">Select the products that belong to this infrastructure cluster. Products can only be linked to one cluster.</p>
                    <div class="row">
                        <?php foreach ($products as $p): 
                            $owner = $owners[$p->id] ?? null;
                            $isOwnedByThis = (in_array($p->id, $linked));
                            $isDisabled = ($owner && !$isOwnedByThis);
                        ?>
                            <div class="col-md-4">
                                <div class="checkbox" style="padding: 10px; border: 1px solid #f1f5f9; border-radius: 6px; margin-bottom: 10px; <?= $isDisabled ? 'opacity:0.6;' : '' ?>">
                                    <label style="cursor:<?= $isDisabled ? 'not-allowed' : 'pointer' ?>; display:block; width:100%;">
                                        <input type="checkbox" name="products[]" value="<?=$p->id?>" <?=$isOwnedByThis?'checked':''?> <?= $isDisabled ? 'disabled' : '' ?>>
                                        <strong><?=$p->name?></strong>
                                        <?php if ($owner && !$isOwnedByThis): ?>
                                            <br><small class="text-danger">Linked to: <b><?=$owner?></b></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <button type="button" id="btnSaveProducts" class="btn btn-primary" onclick="saveProducts()"><i class="fa fa-save"></i> Save Changes</button>
                    <span id="saveStatus" style="margin-left: 15px; display:none;"><i class="fa fa-check text-success"></i> Saved!</span>
                </div>
            </form>
            <script>
                function saveProducts() {
                    const btn = $('#btnSaveProducts');
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                    $.post('<?=$modulelink?>', $('#productForm').serialize(), function(res) {
                        btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Changes');
                        if (res.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + res.message);
                        }
                    });
                }
            </script>
        <?php elseif ($subtab == 'assets'): 
            $linkedProducts = Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $id)->pluck('product_id')->toArray();
            $assets = Capsule::table('tblhosting')
                ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
                ->join('tblclients', 'tblhosting.userid', '=', 'tblclients.id')
                ->whereIn('tblhosting.packageid', $linkedProducts)
                ->where('tblhosting.domainstatus', 'Active')
                ->select('tblhosting.id', 'tblhosting.domain', 'tblproducts.name as product_name', 'tblclients.firstname', 'tblclients.lastname', 'tblhosting.userid', Capsule::raw("'Product Link' as link_type"))
                ->get()->toArray();
            
            $detachedAssets = Capsule::table('mod_cloudflare_domain_infra')
                ->join('tbldomains', 'mod_cloudflare_domain_infra.domain', '=', 'tbldomains.domain')
                ->join('tblclients', 'tbldomains.userid', '=', 'tblclients.id')
                ->where('mod_cloudflare_domain_infra.infra_id', $id)
                ->select('tbldomains.id', 'tbldomains.domain', Capsule::raw("'Detached Domain' as product_name"), 'tblclients.firstname', 'tblclients.lastname', 'tbldomains.userid', Capsule::raw("'Direct IP Match' as link_type"))
                ->get()->toArray();
            
            $allAssets = [];
            $seenDomains = [];
            
            foreach ($assets as $a) {
                $allAssets[] = $a;
                $seenDomains[] = $a->domain;
            }
            
            foreach ($detachedAssets as $da) {
                if (!in_array($da->domain, $seenDomains)) {
                    $allAssets[] = $da;
                    $seenDomains[] = $da->domain;
                }
            }
        ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <p class="text-muted" style="margin:0;">These are active hosting services using products linked to this cluster.</p>
                <button class="btn btn-warning btn-sm" onclick="repairInfra(this, <?=$id?>)"><i class="fa fa-magic"></i> Scan & Repair Assets</button>
            </div>
            </div>
            <table class="cf-table-admin">
                <thead><tr><th>Domain</th><th>Product</th><th>Client</th><th style="text-align:right;">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($allAssets as $a): ?>
                        <tr>
                            <td><strong><?=$a->domain?></strong></td>
                            <td><?=$a->product_name?> <br><small class="text-muted"><?=$a->link_type?></small></td>
                            <td><a href="clientssummary.php?userid=<?=$a->userid?>"><?=$a->firstname?> <?=$a->lastname?></a></td>
                            <td style="text-align:right;">
                                <?php if($a->link_type == 'Product Link'): ?>
                                    <a href="clientshosting.php?userid=<?=$a->userid?>&id=<?=$a->id?>" class="btn btn-default btn-xs">View Service</a>
                                <?php else: ?>
                                    <a href="clientsdomains.php?userid=<?=$a->userid?>&id=<?=$a->id?>" class="btn btn-default btn-xs">View Domain</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; if(count($allAssets)==0): ?>
                        <tr><td colspan="4" class="text-center" style="padding:40px; color:#64748b;">No active assets linked to this cluster yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php elseif ($action == 'sync'): 
    $syncSettings = Capsule::table('mod_cloudflare_settings')->where('setting', 'sync_without_product')->value('value') == 'on';
    $logs = Capsule::table('mod_cloudflare_logs')->where('action', 'SYNC_DNS')->orderBy('id', 'desc')->limit(20)->get();
    $infra = Capsule::table('mod_cloudflare_infrastructure')->get();
    
    // Fetch all active global assets
    $productAssets = Capsule::table('tblhosting')
        ->join('mod_cloudflare_product_infra', 'tblhosting.packageid', '=', 'mod_cloudflare_product_infra.product_id')
        ->join('mod_cloudflare_infrastructure', 'mod_cloudflare_product_infra.infra_id', '=', 'mod_cloudflare_infrastructure.id')
        ->join('tblclients', 'tblhosting.userid', '=', 'tblclients.id')
        ->where('tblhosting.domainstatus', 'Active')
        ->select('tblhosting.domain', 'tblclients.firstname', 'tblclients.lastname', 'tblclients.id as userid', 'mod_cloudflare_infrastructure.name as cluster_name', Capsule::raw("'Product Mapping' as link_type"))
        ->get()->toArray();
        
    $domainAssets = Capsule::table('mod_cloudflare_domain_infra')
        ->join('tbldomains', 'mod_cloudflare_domain_infra.domain', '=', 'tbldomains.domain')
        ->join('mod_cloudflare_infrastructure', 'mod_cloudflare_domain_infra.infra_id', '=', 'mod_cloudflare_infrastructure.id')
        ->join('tblclients', 'tbldomains.userid', '=', 'tblclients.id')
        ->select('tbldomains.domain', 'tblclients.firstname', 'tblclients.lastname', 'tblclients.id as userid', 'mod_cloudflare_infrastructure.name as cluster_name', Capsule::raw("'Direct IP Match' as link_type"))
        ->get()->toArray();
        
    $allGlobalAssets = array_merge($productAssets, $domainAssets);
?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h4><i class="fa fa-refresh"></i> Infrastructure Sync Hub</h4>
            <button class="btn btn-warning btn-sm" onclick="repairAll(this)"><i class="fa fa-magic"></i> Force Global Re-Sync</button>
        </div>
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-4">
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <small class="text-muted">SYNC MODE</small>
                    <h4 style="margin:5px 0;"><?= $syncSettings ? 'IP-Based (Open)' : 'Product-Based (Strict)' ?></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <small class="text-muted">TOTAL CLUSTERS</small>
                    <h4 style="margin:5px 0;"><?= count($infra) ?> Active</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <small class="text-muted">GLOBAL ASSETS</small>
                    <h4 style="margin:5px 0;"><?= Capsule::table('mod_cloudflare_domain_infra')->count() + Capsule::table('mod_cloudflare_product_infra')->count() ?> Tracked</h4>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> The Sync Hub monitors global infrastructure alignment. Use <b>Force Global Re-Sync</b> to scan all accounts and repair mapping disparities.
        </div>
        
        <h5>Recent Global Sync Events</h5>
        <table class="cf-table-admin" style="margin-bottom: 30px;">
            <thead><tr><th>Time</th><th>Domain</th><th>Cluster Match</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><small><?= date('M j, H:i', strtotime($l->created_at)) ?></small></td>
                        <td><strong><?= $l->domain ?></strong></td>
                        <td><span class="label label-default">Automatic</span></td>
                        <td><span class="text-success"><i class="fa fa-check-circle"></i> Success</span></td>
                    </tr>
                <?php endforeach; if(count($logs)==0): ?>
                    <tr><td colspan="4" class="text-center" style="padding:20px;">No recent sync activity logged.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h5>Active Infrastructure Assets</h5>
        <table class="cf-table-admin">
            <thead><tr><th>Domain Name</th><th>Owner</th><th>Cluster</th><th>Link Type</th></tr></thead>
            <tbody>
                <?php foreach ($allGlobalAssets as $ga): ?>
                    <tr>
                        <td><strong><?= $ga->domain ?></strong></td>
                        <td><a href="clientssummary.php?userid=<?= $ga->userid ?>"><?= $ga->firstname ?> <?= $ga->lastname ?></a></td>
                        <td><span class="label label-info"><?= $ga->cluster_name ?></span></td>
                        <td><small class="text-muted"><?= $ga->link_type ?></small></td>
                    </tr>
                <?php endforeach; if(count($allGlobalAssets)==0): ?>
                    <tr><td colspan="4" class="text-center" style="padding:20px;">No global assets currently mapped.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php elseif ($action == 'logs'): 
        $logs = Capsule::table('mod_cloudflare_logs')->orderBy('id', 'desc')->limit(100)->get();
    ?>
        <div class="cf-admin-card">
            <h4>System Activity Logs</h4>
            <table class="cf-table-admin">
                <thead><tr><th>Date</th><th>Client</th><th>Domain</th><th>Action</th><th>Details</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><small><?= date('M j, H:i', strtotime($l->created_at)) ?></small></td>
                            <td>#<?= $l->client_id ?></td>
                            <td><strong><?= $l->domain ?></strong></td>
                            <td><span class="label label-info"><?= $l->action ?></span></td>
                            <td>
                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($l->details) ?>
                                </div>
                                <a href="javascript:void(0)" class="view-log-btn" data-domain="<?= htmlspecialchars($l->domain) ?>" data-action="<?= htmlspecialchars($l->action) ?>" data-details="<?= base64_encode($l->details) ?>" style="font-size:11px;">[View Full]</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="logModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
            <div style="background:#fff; padding:25px; border-radius:12px; width:90%; max-width:600px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                <h4 id="logModalTitle" style="margin-top:0;">Log Details</h4>
                <div id="logModalBody" style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0; font-family:monospace; font-size:13px; max-height:400px; overflow-y:auto; word-break:break-all; white-space:pre-wrap;"></div>
                <div style="margin-top:20px; text-align:right;">
                    <button class="btn btn-default" onclick="$('#logModal').hide()">Close</button>
                </div>
            </div>
        </div>

        <script>
            $('.view-log-btn').on('click', function() {
                const domain = $(this).data('domain');
                const action = $(this).data('action');
                const details = atob($(this).data('details'));
                $('#logModalTitle').text(action + ' - ' + domain);
                $('#logModalBody').text(details);
                $('#logModal').css('display', 'flex');
            });
        </script>

    <?php elseif ($action == 'settings'): 
        $settings = Capsule::table('mod_cloudflare_settings')->pluck('value', 'setting')->toArray();
    ?>
        <div class="cf-admin-card">
            <div class="cf-admin-header"><h4>Module Configuration</h4></div>
            <form method="post" action="<?=$modulelink?>&action=save_settings">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fetch All Cloudflare Domains</label><br>
                            <input type="radio" name="settings[fetch_all_domains]" value="on" <?=($settings['fetch_all_domains']=='on'?'checked':'')?>> Enabled
                            <input type="radio" name="settings[fetch_all_domains]" value="off" <?=($settings['fetch_all_domains']=='off'?'checked':'')?>> Disabled
                            <p class="help-block">Fetch domains even if not in WHMCS.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Sync Without Product Mapping</label><br>
                            <input type="radio" name="settings[sync_without_product]" value="on" <?=($settings['sync_without_product']=='on'?'checked':'')?>> Enabled
                            <input type="radio" name="settings[sync_without_product]" value="off" <?=($settings['sync_without_product']=='off'?'checked':'')?>> Disabled
                            <p class="help-block">Sync domains solely based on IP match.</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Verify Addon Domains (WHM)</label><br>
                            <input type="radio" name="settings[verify_addon_domains]" value="on" <?=($settings['verify_addon_domains']=='on'?'checked':'')?>> Enabled
                            <input type="radio" name="settings[verify_addon_domains]" value="off" <?=($settings['verify_addon_domains']=='off'?'checked':'')?>> Disabled
                            <p class="help-block">Confirm addon domain exists in WHM before mapping.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>IP Retention History Count</label>
                            <input type="number" name="settings[ip_retention_count]" value="<?=$settings['ip_retention_count']?>" class="form-control">
                            <p class="help-block">How many old IPs to track for migration.</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Client Walkthrough Video URL (YouTube/Vimeo Embed)</label>
                            <input type="text" name="settings[video_url]" value="<?=htmlspecialchars($settings['video_url'])?>" class="form-control" placeholder="https://www.youtube.com/embed/XXXXX">
                            <p class="help-block">This video will appear in the client area overview dashboard.</p>
                        </div>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary">Save Module Settings</button>
            </form>
        </div>
    <?php endif; ?>
    <?php
}

function cloudflare_clientarea($vars) {
    $ca = new \WHMCS\ClientArea();
    $clientId = (int)$ca->getUserID();
    if (!$clientId && isset($vars['userid'])) {
        $clientId = (int)$vars['userid'];
    }
    if (!$clientId) return [
        'templatefile' => 'templates/client/overview',
                'vars' => [
            'error' => 'You must be logged in to access this page.',
            'restricted' => false,
            'userAccounts' => [],
            'proxiedDomains' => [],
            'domains' => [],
            'validServices' => []
        ]
    ];

    // 1. Access Control Check
    $allowedProducts = Capsule::table('mod_cloudflare_product_infra')->pluck('infra_id', 'product_id')->toArray();
    $activeServices = Capsule::table('tblhosting')->where('userid', $clientId)->where('domainstatus', 'Active')->get();
    
    $hasAccess = false;
    $clientInfraIds = [];
    foreach ($activeServices as $service) {
        if (isset($allowedProducts[$service->packageid])) {
            $hasAccess = true;
            $clientInfraIds[] = $allowedProducts[$service->packageid];
        }
    }

    if (!$hasAccess) {
        $eligibleProductIds = array_keys($allowedProducts);
        $eligibleProducts = Capsule::table('tblproducts')->whereIn('id', $eligibleProductIds)->where('retired', 0)->get();
        return [
            'templatefile' => 'templates/client/overview',
                        'vars' => [
                'restricted' => true,
                'eligibleProducts' => $eligibleProducts,
                'userAccounts' => [],
                'proxiedDomains' => [],
                'domains' => [],
                'validServices' => []
            ]
        ];
    }

    // 2. Data Aggregation (Multi-Account BYOT)
    require_once __DIR__ . '/lib/API.php';
    $accounts = Capsule::table('mod_cloudflare_user_accounts')->where('client_id', $clientId)->get();
    $userAccounts = $accounts ? $accounts->toArray() : [];
    
    $proxiedDomains = [];
    $whmcsDomains = Capsule::table('tbldomains')->where('userid', $clientId)->get();
    $whmcsDomainNames = $whmcsDomains ? $whmcsDomains->pluck('domain')->toArray() : [];
    $fetchAllDomains = Capsule::table('mod_cloudflare_settings')->where('setting', 'fetch_all_domains')->value('value') == 'on';
    
    $validServices = [];
    foreach ($activeServices as $s) {
        if (isset($allowedProducts[$s->packageid])) {
            $validServices[] = [
                'id' => $s->id,
                'domain' => $s->domain ?: '(No Domain Linked)',
                'product_name' => Capsule::table('tblproducts')->where('id', $s->packageid)->value('name')
            ];
        }
    }

    foreach ($accounts as $acc) {
        try {
            $api = new \WHMCS\Module\Addon\Cloudflare\API($acc->api_token, $acc->email);
            $zones = $api->getZones();
            if ($zones) {
                foreach ($zones as $z) {
                    if (!$fetchAllDomains && !in_array($z['name'], $whmcsDomainNames)) {
                        continue;
                    }
                    $proxiedDomains[] = [
                        'name' => $z['name'],
                        'status' => $z['status'],
                        'account_id' => $acc->id,
                        'account_name' => $acc->name
                    ];
                }
            }
        } catch (\Exception $e) { }
    }

    // 3. Handle Form Actions (Add Account, Delete Account, etc)
    if ($_POST && !isset($_POST['ajax'])) {
        $postAction = $_POST['action'] ?? '';
        
        if ($postAction == 'addAccount') {
            $name = $_POST['name'];
            $email = $_POST['email'] ?? null;
            $authType = $_POST['auth_type'];
            $token = ($authType == 'token') ? $_POST['api_token'] : $_POST['global_key'];
            $accountId = $_POST['account_id'];
            
            if ($name && $token && $accountId) {
                Capsule::table('mod_cloudflare_user_accounts')->insert([
                    'client_id' => $clientId,
                    'name' => $name,
                    'email' => ($authType == 'global') ? $email : '',
                    'api_token' => $token,
                    'account_id' => $accountId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                header("Location: index.php?m=cloudflare&success=account_added"); exit;
            }
        }

        if ($postAction == 'deleteAccount') {
            $accId = (int)$_POST['id'];
            Capsule::table('mod_cloudflare_user_accounts')->where('id', $accId)->where('client_id', $clientId)->delete();
            header("Location: index.php?m=cloudflare&success=account_deleted"); exit;
        }
    }

    // 4. Handle AJAX Operations (Purge, Delete Zone, etc)
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        header('Content-Type: application/json');
        try {
            $accId = (int)$_POST['acc_id'];
            $domain = $_POST['domain'];
            error_log("Cloudflare Debug: AJAX Op {$_POST['op']} for Domain: $domain, Acc ID: $accId, Client ID: $clientId");
            
                        $acc = Capsule::table('mod_cloudflare_user_accounts')->where('id', $accId)->where('client_id', $clientId)->first();
            
            if (!$acc) {
                // Fallback check: Sometimes session ID might be different from $vars['userid']
                $ca = new \WHMCS\ClientArea();
                $clientId = (int)$ca->getUserID();
                $acc = Capsule::table('mod_cloudflare_user_accounts')->where('id', $accId)->where('client_id', $clientId)->first();
            }
            if (!$acc) {
                error_log("Cloudflare Error: Unauthorized access attempt for Acc ID $accId by Client $clientId");
                throw new Exception("Unauthorized account.");
            }

            $api = new \WHMCS\Module\Addon\Cloudflare\API($acc->api_token, $acc->email);
            $zoneId = $api->getZoneId($domain);
            
            error_log("Cloudflare Debug: Zone ID for $domain is " . ($zoneId ?: 'NOT FOUND'));

            if (!$zoneId && !in_array($_POST['op'], ['addRecord', 'syncDNS'])) {
                 throw new Exception("This domain is not active in Cloudflare. Please sync it first.");
            }

            switch ($_POST['op']) {
                case 'deleteZone':
                    $api->deleteZone($zoneId);
                    cloudflare_log($clientId, $domain, 'DELETE_ZONE', "Domain removed from Cloudflare");
                    echo json_encode(['success' => true]); exit;
                case 'addRecord':
                    $type = $_POST['type'];
                    $name = $_POST['name'];
                    $content = $_POST['content'];
                    $proxied = isset($_POST['proxied']) && $_POST['proxied'] == 'true';
                    $api->addDNSRecord($zoneId, $type, $name, $content, 1, $proxied);
                    cloudflare_log($clientId, $domain, 'ADD_RECORD', "Type: $type, Name: $name");
                    echo json_encode(['success' => true]); exit;
                case 'deleteRecord':
                    $recordId = $_POST['record_id'];
                    $logDetails = "Record ID: $recordId";
                    try {
                        $records = $api->getDNSRecords($zoneId);
                        foreach (($records['result'] ?? []) as $r) {
                            if ($r['id'] === $recordId) {
                                $logDetails = "Deleted {$r['type']} record: {$r['name']} -> {$r['content']}";
                                break;
                            }
                        }
                    } catch (\Exception $e) {}
                    
                    $api->deleteDNSRecord($zoneId, $recordId);
                    cloudflare_log($clientId, $domain, 'DELETE_RECORD', $logDetails);
                    echo json_encode(['success' => true]); exit;
                case 'editRecord':
                    $recordId = $_POST['record_id'];
                    $type = $_POST['type'];
                    $name = $_POST['name'];
                    $content = $_POST['content'];
                    $proxied = isset($_POST['proxied']) && $_POST['proxied'] == 'true';
                    $api->updateDNSRecord($zoneId, $recordId, $type, $name, $content, 1, $proxied);
                    cloudflare_log($clientId, $domain, 'EDIT_RECORD', "Type: $type, Name: $name");
                    echo json_encode(['success' => true]); exit;
                case 'purgeCache':
                    $api->purgeCache($zoneId);
                    cloudflare_log($clientId, $domain, 'PURGE_CACHE', "Cache purged successfully");
                    echo json_encode(['success' => true]); exit;
                case 'pauseZone':
                    $pause = $_POST['pause'] == 'true';
                    $api->pauseZone($zoneId, $pause);
                    cloudflare_log($clientId, $domain, 'PAUSE_ZONE', $pause ? "Cloudflare Paused" : "Cloudflare Resumed");
                    echo json_encode(['success' => true]); exit;
                case 'syncDNS':
                    try {
                        $api = new \WHMCS\Module\Addon\Cloudflare\API($acc->api_token, $acc->email);
                        $zoneId = $api->getZoneId($domain);
                        if (!$zoneId) throw new Exception("Zone ID not found for $domain.");

                        // 1. Identify the Cluster/Infrastructure
                        $infraId = null;
                        
                        // A. Check for direct product mapping first
                        $services = Capsule::table('tblhosting')->where('userid', $clientId)->where('domainstatus', 'Active')->get();
                        foreach ($services as $s) {
                            if ($s->domain === $domain) {
                                $infraId = Capsule::table('mod_cloudflare_product_infra')->where('product_id', $s->packageid)->value('infra_id');
                                break;
                            }
                        }

                        // B. Check for manual domain mapping
                        if (!$infraId) {
                            $infraId = Capsule::table('mod_cloudflare_domain_infra')->where('domain', $domain)->value('infra_id');
                        }

                        // C. If no manual match, fallback to IP-based detection (from Cloudflare A-record)
                        if (!$infraId) {
                            $dnsRecords = $api->getDNSRecords($zoneId);
                            foreach ($dnsRecords['result'] as $r) {
                                if ($r['type'] === 'A' && ($r['name'] === $domain || $r['name'] === 'www.'.$domain)) {
                                    $infraId = Capsule::table('mod_cloudflare_infrastructure')->where('ip', $r['content'])->value('id');
                                    break;
                                }
                            }
                        }

                        if (!$infraId) {
                            throw new Exception("Could not determine linked infrastructure. Ensure the domain is linked to a product OR pointing to a cluster IP.");
                        }

                        // 2. Fetch and apply templates
                        $infra = Capsule::table('mod_cloudflare_infrastructure')->where('id', $infraId)->first();
                        $templates = Capsule::table('mod_cloudflare_templates')->where('infra_id', $infraId)->get();
                        $count = 0;
                        
                        foreach ($templates as $t) {
                            try { 
                                $cleanDomain = trim($domain);
                                $finalName = str_replace(['{domain}', '{ip}'], [$cleanDomain, $infra->ip], $t->name);
                                $finalContent = str_replace(['{domain}', '{ip}'], [$cleanDomain, $infra->ip], $t->content);
                                
                                $api->addDNSRecord($zoneId, $t->type, $finalName, $finalContent, (int)$t->ttl, (bool)$t->proxied); 
                                $count++;
                                $appliedRecords[] = "Added {$t->type} $finalName";
                            } catch (\Exception $e) {
                                if (strpos($e->getMessage(), 'already exists') !== false) {
                                    $count++; 
                                    $appliedRecords[] = "Skipped (Exists) {$t->type} $finalName";
                                } else {
                                    $errors[] = $e->getMessage();
                                }
                            }
                        }
                        
                        if ($count == 0 && !empty($templates)) {
                            $errDetail = !empty($errors) ? " (Last Error: " . end($errors) . ")" : "";
                            throw new Exception("No templates could be applied to Cluster: " . ($infra->name ?? 'Unknown') . $errDetail);
                        }
                        
                        $logMessage = "Synchronized $count records.\n\nAPPLIED:\n • " . implode("\n • ", $appliedRecords);
                        if (!empty($errors)) $logMessage .= "\n\nERRORS:\n • " . implode("\n • ", $errors);
                        
                        cloudflare_log($clientId, $domain, 'SYNC_DNS', $logMessage);
                        echo json_encode(['success' => true, 'count' => $count]); exit;

                    } catch (\Exception $e) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
                    }
                case 'updateSecurity':
                    $setting = $_POST['setting'];
                    $value = $_POST['value'];
                    $api->updateZoneSetting($zoneId, $setting, $value);
                    echo json_encode(['success' => true]); exit;

                case 'mapDomain':
                    $serviceId = (int)$_POST['service_id'];
                    $isAddon = $_POST['type'] == 'addon';
                    $verify = Capsule::table('mod_cloudflare_settings')->where('setting', 'verify_addon_domains')->value('value') == 'on';
                    
                    if ($verify && $isAddon) {
                        if (!$verifyAddonDomain($serviceId, $domain)) {
                            throw new Exception("Verification failed: This domain is not found as an addon domain on the selected hosting account.");
                        }
                    }
                    
                    $infraId = Capsule::table('mod_cloudflare_product_infra')->where('product_id', Capsule::table('tblhosting')->where('id', $serviceId)->value('packageid'))->value('infra_id');
                    if (!$infraId) throw new Exception("The selected product is not linked to any Cloudflare infrastructure cluster.");
                    
                    // We don't have a specific table for per-domain mapping yet, we use the product-infra link.
                    // But to "remember" this domain's product, we'll store it in logs and ensure the sync logic can find it.
                    $logAction($clientId, $domain, 'DOMAIN_MAPPING', "Mapped to Service #$serviceId (Type: {$_POST['type']})");
                    echo json_encode(['success' => true]); exit;

                case 'initSync':
                    $domain = $_POST['domain'];
                    $accId = (int)$_POST['acc_id'];
                    $mapType = $_POST['map_type'];
                    $parentId = (int)$_POST['parent_id'];
                    
                    $account = Capsule::table('mod_cloudflare_user_accounts')->where('id', $accId)->where('client_id', $clientId)->first();
                    if (!$account) throw new Exception("Invalid Cloudflare account selected.");

                    // 1. Verification for Addons
                    if ($mapType === 'addon' && $parentId) {
                         if (!$verifyAddonDomain($parentId, $domain)) {
                             throw new Exception("Verification failed: This domain was not found as an addon or parked domain on the selected hosting account.");
                         }
                    } elseif ($mapType === 'primary') {
                        $isPrimary = Capsule::table('tblhosting')->where('userid', $clientId)->where('domain', $domain)->where('domainstatus', 'Active')->exists();
                        if (!$isPrimary) throw new Exception("Verification failed: This domain is not the primary domain for any of your active hosting services.");
                    }
                    
                    // 2. Identify Infrastructure Cluster
                    $infraId = null;
                    if ($mapType === 'primary') {
                        $service = Capsule::table('tblhosting')->where('userid', $clientId)->where('domain', $domain)->where('domainstatus', 'Active')->first();
                        if ($service) $infraId = Capsule::table('mod_cloudflare_product_infra')->where('product_id', $service->packageid)->value('infra_id');
                    } elseif ($mapType === 'addon' && $parentId) {
                        $service = Capsule::table('tblhosting')->where('id', $parentId)->first();
                        if ($service) $infraId = Capsule::table('mod_cloudflare_product_infra')->where('product_id', $service->packageid)->value('infra_id');
                    }
                    
                    $api = new \WHMCS\Module\Addon\Cloudflare\API($account->api_token, $account->email);
                    $zoneId = $api->getZoneId(trim($domain));
                    if (!$zoneId) {
                        $zoneResp = $api->createZone(trim($domain), $account->account_id);
                        $zoneId = $zoneResp['result']['id'] ?? null;
                    }
                    if (!$zoneId) throw new Exception("Could not create or find Cloudflare zone for $domain.");

                    // Fallback for IP-based
                    if (!$infraId) {
                        $dnsRecords = $api->getDNSRecords($zoneId);
                        foreach (($dnsRecords['result'] ?? []) as $r) {
                            if ($r['type'] === 'A' && ($r['name'] === $domain || $r['name'] === 'www.'.$domain)) {
                                $infraId = Capsule::table('mod_cloudflare_infrastructure')->where('ip', $r['content'])->value('id');
                                break;
                            }
                        }
                    }
                    
                    if ($infraId) {
                        $infra = Capsule::table('mod_cloudflare_infrastructure')->where('id', $infraId)->first();
                        $templates = Capsule::table('mod_cloudflare_templates')->where('infra_id', $infraId)->get();
                        foreach ($templates as $t) {
                            try {
                                $api->addDNSRecord($zoneId, $t->type, str_replace(['{domain}', '{ip}'], [$domain, $infra->ip], $t->name), str_replace(['{domain}', '{ip}'], [$domain, $infra->ip], $t->content), $t->ttl, $t->proxied);
                            } catch (\Exception $e) {}
                        }
                        
                        Capsule::table('mod_cloudflare_domain_infra')->updateOrInsert(['domain' => $domain], ['infra_id' => $infraId]);
                        cloudflare_log($clientId, $domain, 'DOMAIN_MAPPING', "Mapped as $mapType to Infra ID: $infraId");
                        echo json_encode(['success' => true, 'redirect' => "index.php?m=cloudflare&action=manage&domain=$domain&acc=$accId"]); exit;
                    } else {
                         if ($mapType === 'none') {
                             echo json_encode(['success' => true, 'redirect' => "index.php?m=cloudflare&action=manage&domain=$domain&acc=$accId&warn=no_infra"]); exit;
                         }
                         throw new Exception("Could not determine infrastructure cluster. Ensure your hosting uses a supported IP.");
                    }
                    exit;

                case 'checkPrimary':
                    $domain = $_POST['domain'];
                    $service = Capsule::table('tblhosting')
                        ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
                        ->where('tblhosting.userid', $clientId)
                        ->where('tblhosting.domain', $domain)
                        ->where('tblhosting.domainstatus', 'Active')
                        ->select('tblhosting.id', 'tblproducts.name')
                        ->first();
                    echo json_encode(['found' => (bool)$service, 'service' => $service]); exit;

                case 'editAccount':
                    $data = [
                        'name' => $_POST['name'],
                        'account_id' => $_POST['account_id'],
                        'email' => $_POST['email'] ?: '',
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    if (!empty($_POST['api_token'])) $data['api_token'] = $_POST['api_token'];
                    if (!empty($_POST['global_key'])) $data['api_token'] = $_POST['global_key'];

                    Capsule::table('mod_cloudflare_user_accounts')->where('id', $accId)->where('client_id', $clientId)->update($data);
                    echo json_encode(['success' => true]); exit;
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
        }
    }

    $action = $_GET['action'] ?? 'overview';

    // Helper: Verify Addon Domain via WHM API
    $verifyAddonDomain = function($serviceId, $domain) {
        try {
            $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
            if (!$service) return false;
            $server = Capsule::table('tblservers')->where('id', $service->server)->first();
            if (!$server) return false;

            $user = $service->username;
            $host = $server->ipaddress ?: $server->hostname;
            $apiToken = $server->api_token ?: decrypt($server->accesshash);
            if (!$apiToken) $apiToken = decrypt($server->password);

            $query = "https://{$host}:2087/json-api/get_userdata?api.version=1&user=" . urlencode($user);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $query);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: WHM {$server->username}:" . trim($apiToken)]);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            if (isset($data['data']['userdata']['addon_domains'])) {
                foreach ($data['data']['userdata']['addon_domains'] as $addon => $path) {
                    if (trim(strtolower($addon)) === trim(strtolower($domain))) return true;
                }
            }
            if (isset($data['data']['userdata']['main_domain']) && trim(strtolower($data['data']['userdata']['main_domain'])) === trim(strtolower($domain))) return true;
            if (isset($data['data']['userdata']['parked_domains'])) {
                foreach ($data['data']['userdata']['parked_domains'] as $parked) {
                    if (trim(strtolower($parked)) === trim(strtolower($domain))) return true;
                }
            }
        } catch (\Exception $e) { }
        return false;
    };

    if ($action == 'manage') {
        $domain = $_GET['domain'];
        $accId = (int)$_GET['acc'];
        $account = Capsule::table('mod_cloudflare_user_accounts')->where('id', $accId)->where('client_id', $clientId)->first();
        
        if (!$account) {
            header("Location: index.php?m=cloudflare&error=invalid_account"); exit;
        }

        $syncWithoutProduct = Capsule::table('mod_cloudflare_settings')->where('setting', 'sync_without_product')->value('value') == 'on';
        $isMapped = Capsule::table('tblhosting')->where('userid', $clientId)->where('domain', $domain)->exists();
        if (!$isMapped) {
            $isMapped = Capsule::table('mod_cloudflare_logs')->where('client_id', $clientId)->where('domain', $domain)->where('action', 'DOMAIN_MAPPING')->exists();
        }

        $mappingRequired = (!$isMapped && !$syncWithoutProduct);
        $mappingRequired = (!$isMapped && !$syncWithoutProduct);
        if (isset($_GET['trigger_sync']) && $_GET['trigger_sync'] == '1') {
            // Deprecated: Redirection-based sync is replaced by AJAX initSync.
            // Keeping for backward compatibility if needed, but UI now uses AJAX.
        }

        // Fetch DNS and Settings
        $dnsError = null;
        $cfSettings = [];
        try {
            $api = new \WHMCS\Module\Addon\Cloudflare\API($account->api_token, $account->email);
            $zoneId = $api->getZoneId(trim($domain));
            if (!$zoneId) throw new \Exception("Zone ID not found");
            
            $dnsRecordsResp = $api->getDNSRecords($zoneId);
            $dnsRecords = $dnsRecordsResp['result'] ?? [];
            
            // Fetch security settings
            $sResp = $api->getZoneSettings($zoneId);
            if (isset($sResp['result'])) {
                foreach ($sResp['result'] as $s) {
                    $cfSettings[$s['id']] = $s['value'];
                }
            }
        } catch (\Exception $e) {
            $dnsRecords = [];
            $dnsError = $e->getMessage();
        }

        return [
            'templatefile' => 'templates/client/manage',
            'vars' => [
                'domainName' => $domain,
                'account' => $account,
                'companyname' => $GLOBALS['companyname'],
                'dnsRecords' => $dnsRecords,
                'settings' => $cfSettings,
                'dnsError' => $dnsError,
                'mappingRequired' => $mappingRequired,
                'clientLogs' => Capsule::table('mod_cloudflare_logs')->where('client_id', $clientId)->where('domain', $domain)->orderBy('id', 'desc')->get()->toArray() ?: [],
                'activeServices' => $activeServices ? $activeServices->toArray() : []
            ]
        ];
    }

    return [
        'templatefile' => 'templates/client/overview',
        'vars' => [
            'restricted' => false,
            'userAccounts' => $userAccounts ?: [],
            'proxiedDomains' => $proxiedDomains ?: [],
            'domains' => $whmcsDomains ? $whmcsDomains->toArray() : [],
            'validServices' => $validServices ?: [],
            'companyname' => $GLOBALS['companyname'],
            'clientId' => $clientId,
            'videoUrl' => Capsule::table('mod_cloudflare_settings')->where('setting', 'video_url')->value('value')
        ]
    ];
}
