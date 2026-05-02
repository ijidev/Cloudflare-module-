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
        'description' => 'Strict Infrastructure-based Cloudflare management.',
        'author' => 'everestserver.com',
        'language' => 'english',
        'version' => '2.2',
        'fields' => []
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

        // BYOT Accounts
        if (!Capsule::schema()->hasTable('mod_cloudflare_user_accounts')) {
            Capsule::schema()->create('mod_cloudflare_user_accounts', function ($table) {
                $table->increments('id');
                $table->integer('client_id');
                $table->string('name', 255);
                $table->string('email', 255);
                $table->text('api_token');
                $table->timestamps();
            });
        }

        return ['status' => 'success', 'description' => 'Activated successfully.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function cloudflare_deactivate() {
    return ['status' => 'success', 'description' => 'Deactivated.'];
}

function cloudflare_output($vars) {
    $action = $_REQUEST['action'] ?? 'infra';
    $modulelink = $vars['modulelink'];

    if ($_POST) {
        if ($action == 'add_infra') {
            $name = $_POST['name'];
            $ip = $_POST['ip'];
            if ($_POST['server_id']) {
                $server = Capsule::table('tblservers')->where('id', $_POST['server_id'])->first();
                if ($server) { $name = $server->name; $ip = $server->ipaddress; }
            }
            Capsule::table('mod_cloudflare_infrastructure')->insert(['server_id' => (int)$_POST['server_id'], 'name' => $name, 'ip' => $ip, 'description' => $_POST['description']]);
            header("Location: $modulelink&action=infra&success=infra_added"); exit;
        }

        if ($action == 'mass_sync_infra') {
            $infraId = (int)$_POST['infra_id'];
            $infra = Capsule::table('mod_cloudflare_infrastructure')->where('id', $infraId)->first();
            $templates = Capsule::table('mod_cloudflare_templates')->where('infra_id', $infraId)->get();
            $linkedProducts = Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $infraId)->pluck('product_id')->toArray();
            
            $domainsToSync = Capsule::table('tblhosting')->join('tbldomains', 'tblhosting.domain', '=', 'tbldomains.domain')->whereIn('tblhosting.packageid', $linkedProducts)->where('tblhosting.domainstatus', 'Active')->select('tbldomains.domain', 'tbldomains.userid')->get();

            require_once __DIR__ . '/lib/API.php';
            $count = 0;
            foreach ($domainsToSync as $d) {
                $acc = Capsule::table('mod_cloudflare_user_accounts')->where('client_id', $d->userid)->first();
                if (!$acc) continue;
                try {
                    $api = new \WHMCS\Module\Addon\Cloudflare\API($acc->api_token, $acc->email);
                    $zid = $api->getZoneId($d->domain);
                    if ($zid) {
                        foreach ($templates as $t) {
                            $api->addDNSRecord($zid, $t->type, str_replace(['{domain}', '{ip}'], [$d->domain, $infra->ip], $t->name), str_replace(['{domain}', '{ip}'], [$d->domain, $infra->ip], $t->content), $t->ttl, $t->proxied);
                        }
                        $count++;
                    }
                } catch (\Exception $e) { }
            }
            header("Location: $modulelink&action=manage_infra&id=$infraId&success=mass_sync&count=$count"); exit;
        }

        if ($action == 'update_infra_products') {
            $infraId = (int)$_POST['infra_id'];
            Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $infraId)->delete();
            if (isset($_POST['products'])) {
                foreach ($_POST['products'] as $pid) Capsule::table('mod_cloudflare_product_infra')->insert(['product_id' => (int)$pid, 'infra_id' => $infraId]);
            }
            header("Location: $modulelink&action=manage_infra&id=$infraId&tab=products&success=products_updated"); exit;
        }
        
        if ($action == 'add_template') {
            Capsule::table('mod_cloudflare_templates')->insert(['infra_id' => (int)$_POST['infra_id'], 'type' => $_POST['type'], 'name' => $_POST['name'], 'content' => $_POST['content'], 'proxied' => isset($_POST['proxied']) ? 1 : 0]);
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=2"); exit;
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
        <a href="<?=$modulelink?>&action=sync" class="<?=$action=='sync'?'active':''?>">Domain Sync Status</a>
    </div>

    <?php if ($action == 'infra'): 
        $infras = Capsule::table('mod_cloudflare_infrastructure')->get();
        $whmcsServers = Capsule::table('tblservers')->orderBy('name', 'asc')->get();
    ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3><i class="fa fa-server"></i> Active Infrastructure</h3>
            <button class="btn btn-primary btn-sm" onclick="$('#addInfraForm').toggle()"><i class="fa fa-plus"></i> New Cluster</button>
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
            <thead><tr><th>Cluster Name</th><th>Primary IP</th><th>Templates</th><th>Linked Products</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($infras as $i): 
                    $tCount = Capsule::table('mod_cloudflare_templates')->where('infra_id', $i->id)->count();
                    $pCount = Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $i->id)->count();
                ?>
                <tr><td><strong><?=$i->name?></strong></td><td><code><?=$i->ip?></code></td><td><span class="label label-info"><?=$tCount?> Records</span></td><td><span class="label label-warning"><?=$pCount?> Plans</span></td><td style="text-align:right;"><a href="<?=$modulelink?>&action=manage_infra&id=<?=$i->id?>" class="btn btn-default btn-xs">Manage</a></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($action == 'manage_infra'): 
        $id = (int)$_GET['id']; $infra = Capsule::table('mod_cloudflare_infrastructure')->where('id', $id)->first();
    ?>
    <div class="cf-admin-card">
        <div class="cf-admin-header">
            <h3>Cluster: <?=$infra->name?></h3>
            <form method="post" action="<?=$modulelink?>&action=mass_sync_infra"><input type="hidden" name="infra_id" value="<?=$id?>"><button type="submit" class="btn btn-warning btn-sm">Force Sync All Domains</button></form>
        </div>
        <!-- Templates & Products UI ... (Keep as is) -->
        <a href="<?=$modulelink?>&action=infra" class="btn btn-default">Back</a>
    </div>
    <?php endif; ?>
    <?php
}

function cloudflare_clientarea($vars) {
    if (!isset($_SESSION['uid'])) return "Access Denied";
    $clientId = $_SESSION['uid'];

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
        // Fetch eligible products to show the user
        $eligibleProductIds = array_keys($allowedProducts);
        $eligibleProducts = Capsule::table('tblproducts')->whereIn('id', $eligibleProductIds)->where('retired', 0)->get();
        
        return [
            'templatefile' => 'templates/client/overview',
            'vars' => [
                'restricted' => true,
                'eligibleProducts' => $eligibleProducts
            ]
        ];
    }

    // Normal logic follows...
    $action = $_REQUEST['action'] ?? 'center';
    require_once __DIR__ . '/lib/API.php';
    // ...
    return [
        'templatefile' => 'templates/client/overview',
        'vars' => [
            'restricted' => false,
            'domains' => Capsule::table('tbldomains')->where('userid', $clientId)->get(),
            'userAccounts' => Capsule::table('mod_cloudflare_user_accounts')->where('client_id', $clientId)->get(),
            // ...
        ]
    ];
}
