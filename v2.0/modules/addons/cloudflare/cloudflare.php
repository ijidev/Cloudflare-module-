<?php
/**
 * Cloudflare WHMCS Core Integration Addon
 *
 * @package    WHMCS
 * @author     Antigravity
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
        'author' => 'Antigravity',
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
            foreach (['master_api_token', 'master_account_id', 'pro_addon_id'] as $setting) {
                Capsule::table('mod_cloudflare_settings')
                    ->where('setting', $setting)
                    ->update(['value' => $_POST[$setting]]);
            }
            header("Location: $modulelink&success=1");
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
        $msgs = [1 => 'Settings saved.', 2 => 'Template record added.', 3 => 'Template record deleted.'];
        echo '<div class="alert alert-success">' . ($msgs[$_GET['success']] ?? 'Success') . '</div>';
    }

    ?>
    <style>
        .cf-admin-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
        .cf-admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .cf-admin-header h3 { margin: 0; color: #2d333a; font-weight: 700; }
        .cf-table-admin { width: 100%; border-collapse: collapse; }
        .cf-table-admin th { background: #f8fafc; padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 13px; color: #64748b; }
        .cf-table-admin td { padding: 12px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        .cf-btn-save { background: #f38020; color: #fff; border: none; padding: 10px 25px; border-radius: 5px; font-weight: 600; cursor: pointer; }
        .cf-btn-add { background: #0051c3; color: #fff; border: none; padding: 8px 15px; border-radius: 4px; font-size: 13px; }
        .cf-btn-del { color: #e53e3e; background: none; border: none; font-size: 18px; cursor: pointer; }
        .cf-input-sm { padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; }
        .badge-proxy { background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
        .badge-proxy.active { background: #ecfdf5; color: #059669; }
    </style>

    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-cog"></i> Global Configuration</h3>
        </div>
        <form method="post" action="<?=$modulelink?>&action=save_settings">
            <div class="row">
                <div class="col-md-4">
                    <label>Master API Token</label>
                    <input type="password" name="master_api_token" class="form-control" value="<?=$settings['master_api_token']?>" placeholder="Cloudflare API Token">
                </div>
                <div class="col-md-4">
                    <label>Master Account ID</label>
                    <input type="text" name="master_account_id" class="form-control" value="<?=$settings['master_account_id']?>" placeholder="Found in dashboard URL">
                </div>
                <div class="col-md-4">
                    <label>Pro Addon ID (Unlocks Paid Tier)</label>
                    <input type="number" name="pro_addon_id" class="form-control" value="<?=$settings['pro_addon_id']?>" placeholder="WHMCS Addon ID">
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button type="submit" class="cf-btn-save">Save Settings</button>
            </div>
        </form>
    </div>

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
                {foreach from=$templates item=t}
                <tr>
                    <td><span class="label label-info"><?=$t->type?></span></td>
                    <td><?=$t->name?></td>
                    <td><code><?=$t->content?></code></td>
                    <td>
                        <span class="badge-proxy <?=$t->proxied ? 'active' : ''?>">
                            <?=$t->proxied ? 'ON' : 'OFF'?>
                        </span>
                    </td>
                    <td>
                        <form method="post" action="<?=$modulelink?>&action=delete_template" onsubmit="return confirm('Delete this record?')">
                            <input type="hidden" name="id" value="<?=$t->id?>">
                            <button type="submit" class="cf-btn-del"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                {/foreach}
                <tr style="background: #fbfbfb;">
                    <form method="post" action="<?=$modulelink?>&action=add_template">
                        <td>
                            <select name="type" class="cf-input-sm">
                                <option value="A">A</option>
                                <option value="CNAME">CNAME</option>
                                <option value="MX">MX</option>
                                <option value="TXT">TXT</option>
                            </select>
                        </td>
                        <td><input type="text" name="name" class="cf-input-sm" placeholder="@ or subdomain" required></td>
                        <td><input type="text" name="content" class="cf-input-sm" placeholder="e.g. {ip} or {domain}" required></td>
                        <td><input type="checkbox" name="proxied" checked></td>
                        <td><button type="submit" class="cf-btn-add"><i class="fa fa-plus"></i> Add</button></td>
                    </form>
                </tr>
            </tbody>
        </table>
        <div class="alert alert-warning" style="margin-top: 15px; font-size: 12px;">
            <i class="fa fa-info-circle"></i> Use <code>{ip}</code> for the server IP and <code>{domain}</code> for the client domain. These records will be created automatically for <strong>Free Managed</strong> domains.
        </div>
    </div>
    <?php
}

function cloudflare_clientarea($vars) {
    if (!isset($_SESSION['uid'])) return "Access Denied";

    $action = $_REQUEST['action'] ?? 'center';
    $clientId = $_SESSION['uid'];

    // Load API helper and settings
    require_once __DIR__ . '/lib/API.php';
    $dbSettings = Capsule::table('mod_cloudflare_settings')->pluck('value', 'setting');
    $api = new \WHMCS\Module\Addon\Cloudflare\API($dbSettings['master_api_token']);

    if ($action == 'manage') {
        $id = (int)$_REQUEST['id'];
        $domainData = Capsule::table('tbldomains')->where('id', $id)->where('userid', $clientId)->first();
        
        if (!$domainData) return "Domain not found.";

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
            } catch (\Exception $e) { return '<div class="alert alert-danger">Provisioning Error: '.$e->getMessage().'</div>'; }
        }

        // Tier Check
        $isPro = false;
        if ($dbSettings['pro_addon_id'] > 0) {
            $isPro = Capsule::table('tblhostingaddon')->join('tblhosting', 'tblhosting.id', '=', 'tblhostingaddon.hostingid')->where('tblhosting.userid', $clientId)->where('tblhostingaddon.addonid', $dbSettings['pro_addon_id'])->where('tblhostingaddon.status', 'Active')->exists();
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
                }
                header("Location: index.php?m=cloudflare&action=manage&id=$id&success=1");
                exit;
            } catch (\Exception $e) { $error = $e->getMessage(); }
        }

        return [
            'pagetitle' => 'Cloudflare Manager - ' . $domain,
            'templatefile' => 'templates/client/manage',
            'vars' => [
                'domain' => $domain,
                'cf_domain_id' => $id,
                'dnsRecords' => $api->getDNSRecords($zoneId)['result'] ?? [],
                'isPro' => $isPro,
                'error' => $error,
            ],
        ];
    }

    // Default: Center Dashboard
    $domains = Capsule::table('tbldomains')->where('userid', $clientId)->where('status', 'Active')->get();
    return [
        'pagetitle' => 'Cloudflare Center',
        'templatefile' => 'templates/client/center',
        'vars' => ['domains' => $domains],
    ];
}
