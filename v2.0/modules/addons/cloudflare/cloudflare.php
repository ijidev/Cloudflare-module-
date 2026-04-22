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
                ['setting' => 'pro_addon_id', 'value' => '0'],
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

        // Create Sync Status table
        if (!Capsule::schema()->hasTable('mod_cloudflare_sync_status')) {
            Capsule::schema()->create('mod_cloudflare_sync_status', function ($table) {
                $table->integer('domain_id')->primary();
                $table->enum('status', ['enabled', 'disabled'])->default('enabled');
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
    $action = $_REQUEST['action'] ?? 'settings';
    $modulelink = $vars['modulelink'];

    // Handle Actions
    if ($_POST) {
        if ($action == 'save_settings') {
            foreach (['master_api_token', 'master_email', 'master_account_id', 'pro_addon_id'] as $setting) {
                Capsule::table('mod_cloudflare_settings')
                    ->where('setting', $setting)
                    ->update(['value' => $_POST[$setting]]);
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
        <a href="<?=$modulelink?>&action=sync" class="<?=$action=='sync'?'active':''?>">Domain Sync Tool</a>
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
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-4">
                    <label>Pro Addon ID (Unlocks Paid Tier)</label>
                    <input type="number" name="pro_addon_id" class="form-control" value="<?=$settings['pro_addon_id']?>" placeholder="WHMCS Addon ID or Product ID">
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
        $syncStatuses = Capsule::table('mod_cloudflare_sync_status')->pluck('status', 'domain_id');
    ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-refresh"></i> Domain Synchronization Tool</h3>
        </div>
        <table class="cf-table-admin">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Current Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($domains as $d): 
                    $status = $syncStatuses[$d->id] ?? 'enabled'; // Default to enabled for active domains unless disabled
                ?>
                <tr>
                    <td><strong><?=$d->domain?></strong></td>
                    <td><span class="label label-<?=$status=='enabled'?'success':'default'?>"><?=$status?></span></td>
                    <td>
                        <form method="post" action="<?=$modulelink?>&action=toggle_sync">
                            <input type="hidden" name="domain_id" value="<?=$d->id?>">
                            <input type="hidden" name="status" value="<?=$status=='enabled'?'disabled':'enabled'?>">
                            <button type="submit" class="btn btn-xs btn-<?=$status=='enabled'?'danger':'success'?>">
                                <?=$status=='enabled'?'Disable Sync':'Enable Sync'?>
                            </button>
                        </form>
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

    $action = $_REQUEST['action'] ?? 'center';
    $clientId = $_SESSION['uid'];

    // Load API helper and settings
    require_once __DIR__ . '/lib/API.php';
    $dbSettings = Capsule::table('mod_cloudflare_settings')->pluck('value', 'setting');
    $api = new \WHMCS\Module\Addon\Cloudflare\API($dbSettings['master_api_token'], $dbSettings['master_email']);

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
        $zoneId = $api->getZoneId($domain);

        // Provision if needed
        if (!$zoneId) {
            try {
                $response = $api->createZone($domain, $dbSettings['master_account_id']);
                $zoneId = $response['result']['id'];
                
                $templates = Capsule::table('mod_cloudflare_templates')->get();
                $serverIp = Capsule::table('tblhosting')->join('tblservers', 'tblservers.id', '=', 'tblhosting.server')->where('tblhosting.domain', $domain)->value('tblservers.ipaddress');

                foreach ($templates as $t) {
                    $content = str_replace(['{ip}', '{domain}'], [$serverIp, $domain], $t->content);
                    $api->addDNSRecord($zoneId, $t->type, $t->name, $content, $t->ttl, $t->proxied);
                }

                if (count($ns) >= 2) {
                    localAPI('DomainUpdateNameservers', [
                        'domainid' => $id,
                        'ns1' => $ns[0],
                        'ns2' => $ns[1],
                    ]);
                }
            } catch (\Exception $e) { 
                $msg = $e->getMessage();
                if (strpos($msg, '1061') !== false || strpos(strtolower($msg), 'already exists') !== false) {
                    return '<div class="alert alert-info">
                        <h4><i class="fa fa-info-circle"></i> Domain Already on Cloudflare</h4>
                        <p>This domain is already active in another Cloudflare account. To manage it here, you have two options:</p>
                        <ul>
                            <li><strong>Option A (Migration):</strong> Delete the domain from your current Cloudflare account. Then, return here and click Manage again to add it to our system.</li>
                            <li><strong>Option B (BYOT):</strong> If you have a Pro subscription with us, you can simply enter your <strong>Cloudflare API Token</strong> in the domain settings to manage it directly without migrating.</li>
                        </ul>
                    </div>';
                }
                return '<div class="alert alert-danger">Provisioning Error: '. $msg .'</div>'; 
            }
        }

        // Tier Check (Supports both Product Addons and Standalone Products)
        $isPro = false;
        if ($dbSettings['pro_addon_id'] > 0) {
            $proId = (int)$dbSettings['pro_addon_id'];
            
            // Check if it's an active Product Addon
            $hasAddon = Capsule::table('tblhostingaddon')
                ->join('tblhosting', 'tblhosting.id', '=', 'tblhostingaddon.hostingid')
                ->where('tblhosting.userid', $clientId)
                ->where('tblhostingaddon.addonid', $proId)
                ->where('tblhostingaddon.status', 'Active')
                ->exists();

            // Check if it's a standalone Product (Package)
            $hasProduct = Capsule::table('tblhosting')
                ->where('userid', $clientId)
                ->where('packageid', $proId)
                ->where('status', 'Active')
                ->exists();

            $isPro = ($hasAddon || $hasProduct);
        }

        // Handle Operations
        if ($_POST['op']) {
            try {
                switch ($_POST['op']) {
                    case 'addRecord':
                        $api->addDNSRecord($zoneId, $_POST['type'], $_POST['name'], $_POST['content']);
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

        try {
            $zoneDetails = $api->getZoneDetails($zoneId)['result'] ?? [];
            $dnsRecords = $api->getDNSRecords($zoneId)['result'] ?? [];
        } catch (\Exception $e) {
            $error = "API Error while fetching domain data. Please check your token permissions (ensure DNS:Edit and Zone:Edit are granted). Details: " . $e->getMessage();
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
            ],
        ];
    }

    // Default: Center Dashboard
    $domains = Capsule::table('tbldomains')
        ->leftJoin('mod_cloudflare_sync_status', 'tbldomains.id', '=', 'mod_cloudflare_sync_status.domain_id')
        ->where('tbldomains.userid', $clientId)
        ->where('tbldomains.status', 'Active')
        ->where(function($query) {
            $query->where('mod_cloudflare_sync_status.status', 'enabled')
                  ->orWhereNull('mod_cloudflare_sync_status.status');
        })
        ->get();
    return [
        'pagetitle' => 'Cloudflare Center',
        'templatefile' => 'templates/client/center',
        'vars' => ['domains' => $domains],
    ];
}
