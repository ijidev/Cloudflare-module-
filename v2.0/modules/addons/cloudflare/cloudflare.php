<?php
/**
 * Cloudflare WHMCS Core Integration Addon
 *
 * @package    WHMCS
 * @author     everestserver.com
 * @copyright  Copyright (c) 2026
 * @license    MIT
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Helpers.php';
use WHMCS\Module\Addon\Cloudflare\Helpers;

function cloudflare_config() {
    return [
        'name' => 'Cloudflare Manager',
        'description' => 'Integrated Cloudflare management with Managed, BYOT, and Dedicated modes.',
        'author' => 'everestserver.com',
        'language' => 'english',
        'version' => '2.0',
        'fields' => []
    ];
}

function cloudflare_activate() {
    try {
        // Create settings table
        if (!Capsule::schema()->hasTable('mod_cloudflare_settings')) {
            Capsule::schema()->create('mod_cloudflare_settings', function ($table) {
                $table->string('setting')->primary();
                $table->text('value')->nullable();
            });
            
            // Insert default empty settings
            Capsule::table('mod_cloudflare_settings')->insert([
                ['setting' => 'master_api_token', 'value' => ''],
                ['setting' => 'master_email', 'value' => ''],
                ['setting' => 'master_account_id', 'value' => ''],
                ['setting' => 'pro_price', 'value' => '20.00'],
                ['setting' => 'pro_billing_cycle', 'value' => 'One Time'],
            ]);
        }

        // Create DNS templates table
        if (!Capsule::schema()->hasTable('mod_cloudflare_templates')) {
            Capsule::schema()->create('mod_cloudflare_templates', function ($table) {
                $table->increments('id');
                $table->string('type', 10);
                $table->string('name', 255);
                $table->text('content');
                $table->integer('ttl')->default(1);
                $table->boolean('proxied')->default(true);
            });
        }

        // Create Sync Status table (Legacy cleanup if needed)
        if (!Capsule::schema()->hasTable('mod_cloudflare_sync_status')) {
            Capsule::schema()->create('mod_cloudflare_sync_status', function ($table) {
                $table->integer('domain_id')->primary();
                $table->enum('status', ['enabled', 'disabled'])->default('enabled');
            });
        } elseif (Capsule::schema()->hasColumn('mod_cloudflare_sync_status', 'is_pro')) {
            Capsule::schema()->table('mod_cloudflare_sync_status', function ($table) {
                $table->dropColumn('is_pro');
            });
        }

        // Create Client Status table (Pro Tier, BYOT, Account Types)
        if (!Capsule::schema()->hasTable('mod_cloudflare_client_status')) {
            Capsule::schema()->create('mod_cloudflare_client_status', function ($table) {
                $table->integer('client_id')->primary();
                $table->boolean('is_pro')->default(false);
                $table->enum('account_type', ['managed', 'dedicated', 'byot'])->default('managed');
                $table->text('api_token')->nullable();
                $table->string('email')->nullable();
            });
        }

        return [
            'status' => 'success',
            'description' => 'Cloudflare Manager activated successfully. Database tables created.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Could not activate Cloudflare Manager: ' . $e->getMessage(),
        ];
    }
}

function cloudflare_deactivate() {
    // Optional: Drop tables on deactivation? Usually safer not to.
    return [
        'status' => 'success',
        'description' => 'Cloudflare Manager deactivated.',
    ];
}

function cloudflare_output($vars) {
    // Ensure tables exist (Self-healing on update)
    if (!Capsule::schema()->hasTable('mod_cloudflare_client_status')) {
        cloudflare_activate();
    }

    $action = $_REQUEST['action'] ?? 'settings';
    $modulelink = $vars['modulelink'];

    // Handle Actions
    if ($_POST) {
        if ($action == 'save_settings') {
            foreach (['master_api_token', 'master_email', 'master_account_id', 'pro_price', 'pro_billing_cycle'] as $setting) {
                $exists = Capsule::table('mod_cloudflare_settings')->where('setting', $setting)->count();
                if ($exists) {
                    Capsule::table('mod_cloudflare_settings')->where('setting', $setting)->update(['value' => $_POST[$setting]]);
                } else {
                    Capsule::table('mod_cloudflare_settings')->insert(['setting' => $setting, 'value' => $_POST[$setting]]);
                }
            }
            header("Location: $modulelink&success=1");
            exit;
        }

        if ($action == 'toggle_sync') {
            $domainId = (int)$_POST['domain_id'];
            $status = $_POST['status'] == 'enabled' ? 'enabled' : 'disabled';
            Capsule::table('mod_cloudflare_sync_status')->updateOrInsert(['domain_id' => $domainId], ['status' => $status]);
            header("Location: $modulelink&action=sync&success=4");
            exit;
        }

        if ($action == 'toggle_pro') {
            $clientId = (int)$_POST['client_id'];
            $isPro = (int)$_POST['is_pro'];
            Capsule::table('mod_cloudflare_client_status')->updateOrInsert(['client_id' => $clientId], ['is_pro' => $isPro]);
            header("Location: $modulelink&action=clients&success=1");
            exit;
        }

        if ($action == 'update_client_type') {
            $clientId = (int)$_POST['client_id'];
            Capsule::table('mod_cloudflare_client_status')->updateOrInsert(['client_id' => $clientId], [
                'account_type' => $_POST['account_type'],
                'api_token' => $_POST['api_token'],
                'email' => $_POST['email']
            ]);
            header("Location: $modulelink&action=clients&success=1");
            exit;
        }

        if ($action == 'add_template') {
            Capsule::table('mod_cloudflare_templates')->insert([
                'type' => $_POST['type'],
                'name' => $_POST['name'],
                'content' => $_POST['content'],
                'proxied' => isset($_POST['proxied']) ? 1 : 0,
            ]);
            header("Location: $modulelink&success=2");
            exit;
        }

        if ($action == 'delete_template') {
            Capsule::table('mod_cloudflare_templates')->where('id', $_POST['id'])->delete();
            header("Location: $modulelink&success=3");
            exit;
        }
    }

    // Fetch Data
    $settings = Capsule::table('mod_cloudflare_settings')->pluck('value', 'setting');
    $templates = Capsule::table('mod_cloudflare_templates')->get();

    // Render UI
    if (isset($_GET['success'])) {
        $msgs = [1 => 'Settings saved.', 2 => 'Template record added.', 3 => 'Template record deleted.', 4 => 'Domain sync status updated.'];
        echo '<div class="alert alert-success">' . ($msgs[$_GET['success']] ?? 'Success') . '</div>';
    }

    ?>
    <style>
        .cf-admin-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
        .cf-admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .cf-admin-header h3 { margin: 0; color: #2d333a; font-weight: 700; }
        .cf-tabs { margin-bottom: 20px; border-bottom: 1px solid #dee2e6; }
        .cf-tabs a { display: inline-block; padding: 10px 20px; text-decoration: none; color: #64748b; font-weight: 600; border-bottom: 3px solid transparent; }
        .cf-tabs a.active { color: #f38020; border-bottom-color: #f38020; }
        .cf-table-admin { width: 100%; border-collapse: collapse; }
        .cf-table-admin th { background: #f8fafc; padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b; }
        .cf-table-admin td { padding: 12px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        .cf-btn-save { background: #f38020; color: #fff; border: none; padding: 10px 25px; border-radius: 5px; font-weight: 600; cursor: pointer; }
    </style>

    <div class="cf-tabs">
        <a href="<?=$modulelink?>&action=settings" class="<?=$action=='settings'?'active':''?>">Settings</a>
        <a href="<?=$modulelink?>&action=templates" class="<?=$action=='templates'?'active':''?>">DNS Templates</a>
        <a href="<?=$modulelink?>&action=sync" class="<?=$action=='sync'?'active':''?>">Domain Sync</a>
        <a href="<?=$modulelink?>&action=clients" class="<?=$action=='clients'?'active':''?>">Client Manager</a>
    </div>

    <?php if ($action == 'settings'): ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-cog"></i> Global Configuration</h3>
        </div>
        <form method="post" action="<?=$modulelink?>&action=save_settings">
            <div class="row">
                <div class="col-md-4">
                    <label>Master API Token / Global Key</label>
                    <input type="password" name="master_api_token" class="form-control" value="<?=$settings['master_api_token']?>" placeholder="API Token or Global Key">
                </div>
                <div class="col-md-4">
                    <label>Cloudflare Email (Required for Global Key)</label>
                    <input type="text" name="master_email" class="form-control" value="<?=$settings['master_email']?>" placeholder="e.g. user@example.com">
                </div>
                <div class="col-md-4">
                    <label>Master Account ID</label>
                    <input type="text" name="master_account_id" class="form-control" value="<?=$settings['master_account_id']?>" placeholder="Found in dashboard URL">
                </div>
            </div>
            <div class="row" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                <div class="col-md-12">
                    <h4 style="margin-top:0; color:#f38020;"><i class="fa fa-shopping-cart"></i> Standalone Pro Manager</h4>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">Configure the pricing for the automated Pro Tier upgrade invoice generated when a client clicks "Upgrade Now".</p>
                </div>
                <div class="col-md-4">
                    <label>Pro Upgrade Price</label>
                    <input type="number" step="0.01" name="pro_price" class="form-control" value="<?=$settings['pro_price']?>" placeholder="e.g. 20.00">
                </div>
                <div class="col-md-4">
                    <label>Billing Cycle Description</label>
                    <select name="pro_billing_cycle" class="form-control">
                        <option value="One Time" <?=$settings['pro_billing_cycle']=='One Time'?'selected':''?>>One Time</option>
                        <option value="Monthly" <?=$settings['pro_billing_cycle']=='Monthly'?'selected':''?>>Monthly</option>
                        <option value="Annually" <?=$settings['pro_billing_cycle']=='Annually'?'selected':''?>>Annually</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button type="submit" class="cf-btn-save">Save Settings</button>
            </div>
        </form>
    </div>
    <?php elseif ($action == 'templates'): ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-list"></i> DNS Template Manager</h3>
        </div>
        <table class="cf-table-admin">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Content</th>
                    <th>Proxied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $t): ?>
                <tr>
                    <td><span class="label label-info"><?=$t->type?></span></td>
                    <td><?=$t->name?></td>
                    <td><code><?=$t->content?></code></td>
                    <td><span class="badge-proxy <?=$t->proxied?'active':''?>"><?=$t->proxied?'ON':'OFF'?></span></td>
                    <td>
                        <form method="post" action="<?=$modulelink?>&action=delete_template" onsubmit="return confirm('Delete this record?')">
                            <input type="hidden" name="id" value="<?=$t->id?>">
                            <button type="submit" class="cf-btn-del"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr style="background: #fbfbfb;">
                    <form method="post" action="<?=$modulelink?>&action=add_template">
                        <td><select name="type" class="cf-input-sm"><option value="A">A</option><option value="CNAME">CNAME</option><option value="MX">MX</option><option value="TXT">TXT</option></select></td>
                        <td><input type="text" name="name" class="cf-input-sm" placeholder="@ or subdomain" required></td>
                        <td><input type="text" name="content" class="cf-input-sm" placeholder="e.g. {ip} or {domain}" required></td>
                        <td><input type="checkbox" name="proxied" checked></td>
                        <td><button type="submit" class="cf-btn-add"><i class="fa fa-plus"></i> Add</button></td>
                    </form>
                </tr>
            </tbody>
        </table>
    </div>
    <?php elseif ($action == 'sync'): 
        $domains = Capsule::table('tbldomains')->orderBy('domain', 'asc')->get();
    ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-refresh"></i> Domain Synchronization</h3>
        </div>
        <table class="cf-table-admin">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Sync Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($domains as $d): 
                    $status = Capsule::table('mod_cloudflare_sync_status')->where('domain_id', $d->id)->value('status') ?? 'enabled';
                ?>
                <tr>
                    <td><strong><?=$d->domain?></strong></td>
                    <td><span class="label label-<?=$status=='enabled'?'success':'default'?>"><?=$status?></span></td>
                    <td>
                        <form method="post" action="<?=$modulelink?>&action=toggle_sync">
                            <input type="hidden" name="domain_id" value="<?=$d->id?>">
                            <input type="hidden" name="status" value="<?=$status=='enabled'?'disabled':'enabled'?>">
                            <button type="submit" class="btn btn-xs btn-<?=$status=='enabled'?'default':'success'?>">
                                <i class="fa fa-refresh"></i> <?=$status=='enabled'?'Disable Sync':'Enable Sync'?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($action == 'clients'): 
        $clients = Capsule::table('tblclients')->orderBy('firstname', 'asc')->get();
        $proClients = Capsule::table('mod_cloudflare_client_status')->get()->keyBy('client_id');
    ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-users"></i> Client Pro Manager</h3>
        </div>
        <table class="cf-table-admin">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Email</th>
                    <th>Tier</th>
                    <th>Account Mode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): 
                    $isPro = $proClients[$c->id]->is_pro ?? false;
                    $mode = $proClients[$c->id]->account_type ?? 'managed';
                ?>
                <tr>
                    <td><strong><?=$c->firstname?> <?=$c->lastname?></strong></td>
                    <td><?=$c->email?></td>
                    <td><span class="label label-<?=$isPro?'warning':'info'?>"><?=$isPro?'PRO':'FREE'?></span></td>
                    <td><span class="label label-default"><?=strtoupper($mode)?></span></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <form method="post" action="<?=$modulelink?>&action=toggle_pro">
                                <input type="hidden" name="client_id" value="<?=$c->id?>">
                                <input type="hidden" name="is_pro" value="<?=$isPro?'0':'1'?>">
                                <button type="submit" class="btn btn-xs btn-<?=$isPro?'default':'warning'?>">
                                    <?=$isPro?'Revoke Pro':'Grant Pro'?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php
}

function cloudflare_clientarea($vars) {
    if (!isset($_SESSION['uid'])) return "Access Denied";

    // Ensure tables exist
    if (!Capsule::schema()->hasTable('mod_cloudflare_client_status')) {
        cloudflare_activate();
    }

    $action = $_REQUEST['action'] ?? 'center';
    $clientId = $_SESSION['uid'];

    // Standalone Pro Purchase Logic (Dedicated only)
    if ($action == 'buyPro') {
        $price = $dbSettings['pro_price'] ?? '20.00';
        $cycle = $dbSettings['pro_billing_cycle'] ?? 'One Time';
        
        $results = localAPI('CreateInvoice', [
            'userid' => $clientId,
            'date' => date('Y-m-d'),
            'duedate' => date('Y-m-d'),
            'paymentmethod' => '', // Default
            'sendinvoice' => true,
            'itemdescription1' => "Cloudflare Dedicated Tier Upgrade - {$cycle}",
            'itemamount1' => $price,
            'itemtaxed1' => false,
        ]);

        if ($results['result'] == 'success') {
            header("Location: viewinvoice.php?id=" . $results['invoiceid']);
            exit;
        } else {
            return "Error creating invoice: " . $results['message'];
        }
    }

    // Load API helper and settings
    require_once __DIR__ . '/lib/API.php';
    $dbSettings = Capsule::table('mod_cloudflare_settings')->pluck('value', 'setting');
    $api = new \WHMCS\Module\Addon\Cloudflare\API($dbSettings['master_api_token'], $dbSettings['master_email']);

    // Detect Dedicated Availability
    $dedicatedAvailable = false;
    try {
        $accounts = $api->getAccounts();
        foreach ($accounts['result'] as $acc) {
            if (isset($acc['type']) && in_array(strtolower($acc['type']), ['enterprise', 'partner'])) {
                $dedicatedAvailable = true;
                break;
            }
        }
    } catch (\Exception $e) {}

    // Handle AJAX Requests
    if (isset($_POST['ajax']) && $_POST['ajax']) {
        header('Content-Type: application/json');
        try {
            $id = (int)$_REQUEST['id'];
            $domainData = Capsule::table('tbldomains')->where('id', $id)->where('userid', $clientId)->first();
            
            if (!$domainData) {
                throw new \Exception("Domain not found in WHMCS. (Searching for Domain ID: $id for Client ID: $clientId). Check if the domain is correctly assigned to your account.");
            }
            
            $domain = $domainData->domain;
            
            $clientStatus = Capsule::table('mod_cloudflare_client_status')->where('client_id', $clientId)->first();
            if ($clientStatus && $clientStatus->account_type == 'byot' && $clientStatus->api_token) {
                $api = new \WHMCS\Module\Addon\Cloudflare\API($clientStatus->api_token, $clientStatus->email);
            }
            
            $zoneId = $api->getZoneId($domain);
            
            // Check if domain initialization is needed for this operation
            if (!$zoneId && !in_array($_POST['op'], ['sync', 'migrate'])) {
                throw new \Exception("Domain '$domain' is not yet active on Cloudflare (Zone ID not found). Please initialize it first using the Sync tool.");
            }

            switch ($_POST['op']) {
                case 'addRecord':
                    $api->addDNSRecord($zoneId, $_POST['type'], $_POST['name'], $_POST['content'], 1, isset($_POST['proxied']));
                    break;
                case 'editRecord':
                    $api->updateDNSRecord($zoneId, $_POST['record_id'], $_POST['type'] ?: 'A', $_POST['name'], $_POST['content'], 1, isset($_POST['proxied']));
                    break;
                case 'deleteRecord':
                    $api->deleteDNSRecord($zoneId, $_POST['record_id']);
                    break;
                case 'purgeCache':
                    $api->purgeCache($zoneId);
                    break;
                case 'toggleSecurity':
                    $settings = $api->getZoneSettings($zoneId)['result'];
                    $current = 'medium';
                    foreach ($settings as $s) { if ($s['id'] == 'security_level') $current = $s['value']; }
                    $api->updateZoneSetting($zoneId, 'security_level', ($current == 'under_attack' ? 'medium' : 'under_attack'));
                    break;
                case 'togglePause':
                    $details = $api->getZoneDetails($zoneId)['result'];
                    $api->pauseZone($zoneId, !$details['paused']);
                    break;
                case 'migrate':
                case 'sync':
                    $serverIp = Helpers::getServerIp($domain, $clientId);
                    if (!$zoneId) {
                        $targetAccountId = ($clientStatus && $clientStatus->account_type == 'byot') ? null : $dbSettings['master_account_id'];
                        $resp = $api->createZone($domain, $targetAccountId);
                        $zoneId = $resp['result']['id'];
                        $ns = $resp['result']['name_servers'] ?? [];
                        if (count($ns) >= 2) {
                            localAPI('DomainUpdateNameservers', ['domainid' => $id, 'ns1' => $ns[0], 'ns2' => $ns[1]]);
                        }
                        $templates = Capsule::table('mod_cloudflare_templates')->get();
                        foreach ($templates as $t) {
                            try {
                                $expectedName = str_replace(['{domain}', '{ip}'], [$domain, $serverIp], $t->name);
                                $content = str_replace(['{domain}', '{ip}'], [$domain, $serverIp], $t->content);
                                $api->addDNSRecord($zoneId, $t->type, $expectedName, $content, $t->ttl, $t->proxied);
                            } catch (\Exception $e) { /* ignore template errors */ }
                        }
                        echo json_encode(['success' => true, 'message' => "Domain connected and initialized successfully! DNS templates applied."]);
                    } else {
                        // Check if nameservers need updating
                        $details = $api->getZoneDetails($zoneId)['result'];
                        $ns = $details['name_servers'] ?? [];
                        if (count($ns) >= 2) {
                            localAPI('DomainUpdateNameservers', ['domainid' => $id, 'ns1' => $ns[0], 'ns2' => $ns[1]]);
                        }
                        
                        // Sync missing template records
                        $existingRecords = $api->getDNSRecords($zoneId)['result'] ?? [];
                        $templates = Capsule::table('mod_cloudflare_templates')->get();
                        $addedCount = 0;
                        
                        foreach ($templates as $t) {
                            $expectedName = str_replace(['{domain}', '{ip}'], [$domain, $serverIp], $t->name);
                            $expectedContent = str_replace(['{domain}', '{ip}'], [$domain, $serverIp], $t->content);
                            
                            $exists = false;
                            foreach ($existingRecords as $er) {
                                if ($er['type'] == $t->type && ($er['name'] == $expectedName || $er['name'] == $expectedName . '.' . $domain)) {
                                    $exists = true;
                                    break;
                                }
                            }
                            
                            if (!$exists) {
                                try {
                                    $api->addDNSRecord($zoneId, $t->type, $expectedName, $expectedContent, $t->ttl, $t->proxied);
                                    $addedCount++;
                                } catch (\Exception $e) {}
                            }
                        }
                        
                        $msg = "Domain is active. Infrastructure synced.";
                        if ($addedCount > 0) $msg .= " Added {$addedCount} missing records.";
                        echo json_encode(['success' => true, 'message' => $msg]);
                    }
                    exit;
            }
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action == 'updateProSettings') {
        $clientStatus = Capsule::table('mod_cloudflare_client_status')->where('client_id', $clientId)->first();
        if ($clientStatus && $clientStatus->is_pro) {
            $oldType = $clientStatus->account_type;
            $newType = $_POST['account_type'];
            
            if ($oldType == 'managed' && $newType == 'byot' && !empty($_POST['api_token'])) {
                $domainData = Capsule::table('tbldomains')->where('userid', $clientId)->get();
                $byotApi = new \WHMCS\Module\Addon\Cloudflare\API($_POST['api_token'], $_POST['email']);
                foreach ($domainData as $d) {
                    try {
                        $zId = $api->getZoneId($d->domain);
                        if ($zId) {
                            $records = $api->getDNSRecords($zId)['result'] ?? [];
                            $nZone = $byotApi->createZone($d->domain);
                            $nZoneId = $nZone['result']['id'];
                            foreach ($records as $r) {
                                try { $byotApi->addDNSRecord($nZoneId, $r['type'], $r['name'], $r['content'], $r['ttl'], $r['proxied']); } catch (\Exception $e) {}
                            }
                        }
                    } catch (\Exception $e) {}
                }
            }

            Capsule::table('mod_cloudflare_client_status')->where('client_id', $clientId)->update([
                'account_type' => $newType, 'api_token' => $_POST['api_token'], 'email' => $_POST['email']
            ]);
        }
        header("Location: index.php?m=cloudflare&success=settings");
        exit;
    }

    if ($action == 'manage') {
        $id = (int)$_REQUEST['id'];
        $domainData = Capsule::table('tbldomains')->where('id', $id)->where('userid', $clientId)->first();
        
        if (!$domainData) return "Domain not found.";

        // Check Sync Status
        $syncStatus = Capsule::table('mod_cloudflare_sync_status')->where('domain_id', $id)->value('status');
        if ($syncStatus == 'disabled') {
            return '<div class="alert alert-warning">Cloudflare management is disabled for this domain by the administrator.</div>';
        }

        $domain = $domainData->domain;
        $needsMigration = false;
        $zoneId = null;
        
        try {
            $zoneId = $api->getZoneId($domain);
        } catch (\Exception $e) {
            // If API fails, we might need migration or just have a bad token
        }

        if (!$zoneId) {
            $needsMigration = true;
        }

        // Tier Check (Core Integrated Client-Level)
        $clientStatus = Capsule::table('mod_cloudflare_client_status')->where('client_id', $clientId)->first();
        $isPro = (bool)($clientStatus->is_pro ?? false);

        // Fallback check for active Pro services (by name)
        if (!$isPro) {
            $isPro = Capsule::table('tblhosting')
                ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
                ->where('tblhosting.userid', $clientId)
                ->where('tblhosting.domainstatus', 'Active')
                ->where('tblproducts.name', 'LIKE', '%Cloudflare Pro%')
                ->exists() || 
                Capsule::table('tblhostingaddons')
                ->join('tbladdons', 'tbladdons.id', '=', 'tblhostingaddons.addonid')
                ->where('tblhostingaddons.status', 'Active')
                ->where('tbladdons.name', 'LIKE', '%Cloudflare Pro%')
                ->exists();
        }

        $proUpgradeUrl = $dbSettings['pro_upgrade_url'] ?? 'cart.php?gid=addons';
        $proUpgradeUrl = str_replace('{domain}', $domain, $proUpgradeUrl);

        // Handle Operations
        if (isset($_POST['op']) && $_POST['op']) {
            try {
                if ($_POST['op'] == 'migrate') {
                    $targetApi = $api;
                    $targetAccountId = $dbSettings['master_account_id'];

                    if ($clientStatus && $clientStatus->account_type == 'byot' && $clientStatus->api_token) {
                        $targetApi = new \WHMCS\Module\Addon\Cloudflare\API($clientStatus->api_token, $clientStatus->email);
                        // Get BYOT account ID
                        $byotAccounts = $targetApi->getAccounts();
                        $targetAccountId = $byotAccounts['result'][0]['id'] ?? null;
                    }

                    // Check if domain exists elsewhere
                    try {
                        $response = $targetApi->createZone($domain, $targetAccountId);
                        $zoneId = $response['result']['id'];
                        $ns = $response['result']['name_servers'] ?? [];
                    } catch (\Exception $e) {
                        if (strpos($e->getMessage(), '1061') !== false) {
                            throw new \Exception("This domain is already active in another Cloudflare account. Please remove it from your personal Cloudflare dashboard before initializing managed setup.");
                        }
                        throw $e;
                    }

                    // Apply Templates if any (Managed only)
                    if ($targetApi === $api) {
                        $templates = Capsule::table('mod_cloudflare_templates')->get();
                        foreach ($templates as $t) {
                            $content = str_replace(['{domain}', '{ip}'], [$domain, $_SERVER['SERVER_ADDR']], $t->content);
                            $api->addDNSRecord($zoneId, $t->type, $t->name, $content, $t->ttl, $t->proxied);
                        }
                    }

                    // Set nameservers automatically
                    if (count($ns) >= 2) {
                        localAPI('DomainUpdateNameservers', [
                            'domainid' => $id,
                            'ns1' => $ns[0],
                            'ns2' => $ns[1]
                        ]);
                    }

                    header("Location: index.php?m=cloudflare&action=manage&id=$id&success=migration");
                    exit;
                }

                switch ($_POST['op']) {
                    case 'addRecord':
                        $api->addDNSRecord($zoneId, $_POST['type'], $_POST['name'], $_POST['content']);
                        break;
                    case 'editRecord':
                        $api->updateDNSRecord($zoneId, $_POST['record_id'], $_POST['type'] ?: 'A', $_POST['name'], $_POST['content']);
                        break;
                    case 'deleteRecord':
                        $api->deleteDNSRecord($zoneId, $_POST['record_id']);
                        break;
                    case 'purgeCache':
                        $api->purgeCache($zoneId);
                        break;
                    case 'toggleSecurity':
                        $current = 'medium';
                        $settings = $api->getZoneSettings($zoneId)['result'];
                        foreach ($settings as $s) { if ($s['id'] == 'security_level') $current = $s['value']; }
                        $api->updateZoneSetting($zoneId, 'security_level', ($current == 'under_attack' ? 'medium' : 'under_attack'));
                        break;
                    case 'togglePause':
                        $details = $api->getZoneDetails($zoneId)['result'];
                        $isPaused = $details['paused'];
                        $api->pauseZone($zoneId, !$isPaused);
                        break;
                }
                header("Location: index.php?m=cloudflare&action=manage&id=$id&success=1");
                exit;
            } catch (\Exception $e) { $error = $e->getMessage(); }
        }

        // Fetch DNS and Zone Status
        $zoneDetails = [];
        $dnsRecords = [];
        if (!isset($error)) $error = '';

        $underAttack = false;
        $nameservers = [];
        if (!$needsMigration) {
            try {
                $zoneDetails = $api->getZoneDetails($zoneId)['result'] ?? [];
                $dnsRecords = $api->getDNSRecords($zoneId)['result'] ?? [];
                $settings = $api->getZoneSettings($zoneId)['result'] ?? [];
                $nameservers = $zoneDetails['name_servers'] ?? [];
                foreach ($settings as $s) {
                    if ($s['id'] == 'security_level' && $s['value'] == 'under_attack') {
                        $underAttack = true;
                    }
                }
            } catch (\Exception $e) {
                $error = "API Error while fetching domain data. Please check your token permissions (ensure DNS:Edit and Zone:Edit are granted). Details: " . $e->getMessage();
            }
        }

        return [
            'pagetitle' => 'Cloudflare Manager - ' . $domain,
            'templatefile' => 'templates/client/manage',
            'vars' => [
                'domain' => $domain,
                'cf_domain_id' => $id,
                'dnsRecords' => $dnsRecords,
                'isPro' => $isPro,
                'isPaused' => $zoneDetails['paused'] ?? false,
                'error' => $error,
                'proUpgradeUrl' => $proUpgradeUrl,
                'needsMigration' => $needsMigration,
                'underAttack' => $underAttack,
                'nameservers' => $nameservers,
            ],
        ];
    }
    
    // Overview Page Logic
    $domains = Capsule::table('tbldomains')->where('userid', $clientId)->get();
    $totalDomains = count($domains);
    $managedCount = 0;
    $syncData = Capsule::table('mod_cloudflare_sync_status')->pluck('status', 'domain_id');

    foreach ($domains as $d) {
        if (($syncData[$d->id] ?? 'enabled') == 'enabled') $managedCount++;
    }

    $clientStatus = Capsule::table('mod_cloudflare_client_status')->where('client_id', $clientId)->first();
    $isPro = (bool)($clientStatus->is_pro ?? false);
    $proUpgradeUrl = $dbSettings['pro_upgrade_url'] ?? 'cart.php?gid=addons';

    return [
        'pagetitle' => 'Cloudflare Manager Overview',
        'templatefile' => 'templates/client/overview',
        'vars' => [
            'totalDomains' => $totalDomains,
            'managedCount' => $managedCount,
            'isPro' => $isPro,
            'accountType' => $clientStatus->account_type ?? 'managed',
            'apiToken' => $clientStatus->api_token ?? '',
            'email' => $clientStatus->email ?? '',
            'proUpgradeUrl' => $proUpgradeUrl,
            'domains' => $domains,
            'dedicatedAvailable' => $dedicatedAvailable,
        ],
    ];
}
