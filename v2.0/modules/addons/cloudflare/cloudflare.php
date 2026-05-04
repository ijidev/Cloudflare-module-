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
        'author' => 'everestserver.com',
        'language' => 'english',
        'version' => '2.2',
        'fields' => [
            'fetch_all_domains' => [
                'FriendlyName' => 'Fetch All Cloudflare Domains',
                'Type' => 'yesno',
                'Size' => '25',
                'Default' => '',
                'Description' => 'If enabled, the module will fetch and manage all domains found in the Cloudflare account. If disabled (default), it will only fetch domains associated with the WHMCS account.',
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

        return ['status' => 'success', 'description' => 'Activated successfully.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function cloudflare_deactivate() {
    return ['status' => 'success', 'description' => 'Deactivated.'];
}

function cloudflare_output($vars) {
    // Self-healing Database Schema (Aggressive Check)
    try {
        // mod_cloudflare_product_infra
        if (!Capsule::schema()->hasTable('mod_cloudflare_product_infra')) {
            Capsule::schema()->create('mod_cloudflare_product_infra', function ($table) {
                $table->integer('product_id')->primary();
                $table->integer('infra_id');
            });
        } elseif (!Capsule::schema()->hasColumn('mod_cloudflare_product_infra', 'infra_id')) {
            Capsule::schema()->table('mod_cloudflare_product_infra', function ($table) {
                $table->integer('infra_id')->after('product_id');
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
                $table->boolean('proxied')->default(true);
            });
        } elseif (!Capsule::schema()->hasColumn('mod_cloudflare_templates', 'infra_id')) {
            Capsule::schema()->table('mod_cloudflare_templates', function ($table) {
                $table->integer('infra_id')->after('id');
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
    } catch (\Exception $e) {
        // Silently log or display if admin
    }

    $action = $_REQUEST['action'] ?? 'infra';
    $modulelink = $vars['modulelink'];

    // AJAX Operations (Admin)
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        header('Content-Type: application/json');
        try {
            switch ($_POST['op']) {
                case 'add_template':
                    $id = Capsule::table('mod_cloudflare_templates')->insertGetId([
                        'infra_id' => (int)$_POST['infra_id'],
                        'type' => $_POST['type'],
                        'name' => $_POST['name'],
                        'content' => $_POST['content'],
                        'proxied' => $_POST['proxied'] == 'true' ? 1 : 0,
                    ]);
                    echo json_encode(['success' => true, 'id' => $id]); exit;

                case 'delete_template':
                    Capsule::table('mod_cloudflare_templates')->where('id', (int)$_POST['id'])->delete();
                    echo json_encode(['success' => true]); exit;

                case 'update_products':
                    $infraId = (int)$_POST['infra_id'];
                    Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $infraId)->delete();
                    if (isset($_POST['products']) && is_array($_POST['products'])) {
                        foreach ($_POST['products'] as $pid) {
                            Capsule::table('mod_cloudflare_product_infra')->insert(['product_id' => (int)$pid, 'infra_id' => $infraId]);
                        }
                    }
                    echo json_encode(['success' => true]); exit;
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
        }
    }

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
        
        if ($action == 'delete_infra') {
            $id = (int)$_POST['id'];
            Capsule::table('mod_cloudflare_infrastructure')->where('id', $id)->delete();
            Capsule::table('mod_cloudflare_templates')->where('infra_id', $id)->delete();
            header("Location: $modulelink&action=infra&success=infra_deleted"); exit;
        }

        if ($action == 'add_template') {
            Capsule::table('mod_cloudflare_templates')->insert([
                'infra_id' => (int)$_POST['infra_id'],
                'type' => $_POST['type'],
                'name' => $_POST['name'],
                'content' => $_POST['content'],
                'ttl' => (int)$_POST['ttl'],
                'proxied' => isset($_POST['proxied']) ? 1 : 0
            ]);
            header("Location: $modulelink&action=manage_infra&id=" . $_POST['infra_id'] . "&tab=templates&success=1"); exit;
        }

        if ($action == 'update_template') {
            Capsule::table('mod_cloudflare_templates')->where('id', (int)$_POST['id'])->update([
                'type' => $_POST['type'],
                'name' => $_POST['name'],
                'content' => $_POST['content'],
                'ttl' => (int)$_POST['ttl'],
                'proxied' => isset($_POST['proxied']) ? 1 : 0
            ]);
            header("Location: $modulelink&action=manage_infra&id=" . $_POST['infra_id'] . "&tab=templates&success=1"); exit;
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
                <tr>
                    <td><strong><?=$i->name?></strong></td>
                    <td><code><?=$i->ip?></code></td>
                    <td><span class="label label-info"><?=$tCount?> Records</span></td>
                    <td><span class="label label-warning"><?=$pCount?> Plans</span></td>
                    <td style="text-align:right;">
                        <a href="<?=$modulelink?>&action=manage_infra&id=<?=$i->id?>" class="btn btn-default btn-xs">Manage</a>
                        <form method="post" action="<?=$modulelink?>&action=delete_infra" style="display:inline;" onsubmit="return confirm('Delete this infrastructure and all its templates?')">
                            <input type="hidden" name="id" value="<?=$i->id?>">
                            <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                        </form>
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
                <form method="post" action="<?=$modulelink?>&action=mass_sync_infra" onsubmit="return confirm('This will force-update DNS records for ALL domains on this cluster using the current templates. Proceed?')">
                    <input type="hidden" name="infra_id" value="<?=$id?>">
                    <button type="submit" class="btn btn-warning btn-sm"><i class="fa fa-refresh"></i> Force Sync All Domains</button>
                </form>
                <a href="<?=$modulelink?>&action=infra" class="btn btn-default btn-sm">Back to Overview</a>
            </div>
        </div>

        <ul class="nav nav-tabs" style="margin-bottom: 20px; border-bottom: 1px solid #ddd;">
            <li class="<?=$subtab=='templates'?'active':''?>"><a href="<?=$modulelink?>&action=manage_infra&id=<?=$id?>&tab=templates">DNS Templates</a></li>
            <li class="<?=$subtab=='products'?'active':''?>"><a href="<?=$modulelink?>&action=manage_infra&id=<?=$id?>&tab=products">Linked Products</a></li>
        </ul>
        <?php if ($subtab == 'templates'): ?>
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
                        <td><input type="checkbox" id="new-proxied" checked></td>
                        <td style="text-align:right;">
                            <button type="button" id="btnAddTmpl" class="btn btn-success btn-sm" onclick="addTemplate()"><i class="fa fa-plus"></i> Add Record</button>
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
                    $('#edit-ttl').val(data.ttl); $('#edit-proxied').prop('checked', data.proxied == 1);
                    $('#editTmplModal').modal('show');
                }
                function saveTemplate() {
                    const btn = $('#btnSaveTmpl');
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                    const data = {
                        ajax: '1', op: 'update_template', id: $('#edit-id').val(), infra_id: '<?=$id?>',
                        type: $('#edit-type').val(), name: $('#edit-name').val(),
                        content: $('#edit-content').val(), ttl: $('#edit-ttl').val(),
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
        <?php else: 
            $linked = Capsule::table('mod_cloudflare_product_infra')->where('infra_id', $id)->pluck('product_id')->toArray();
            $products = Capsule::table('tblproducts')->orderBy('name', 'asc')->get();
        ?>
            <form id="productForm">
                <input type="hidden" name="infra_id" value="<?=$id?>">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="op" value="update_products">
                <div style="max-height: 500px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 20px; background: #fff;">
                    <p class="text-muted" style="margin-bottom: 20px;">Select the products that belong to this infrastructure cluster.</p>
                    <div class="row">
                        <?php foreach ($products as $p): ?>
                            <div class="col-md-4">
                                <div class="checkbox" style="padding: 10px; border: 1px solid #f1f5f9; border-radius: 6px; margin-bottom: 10px;">
                                    <label style="cursor:pointer; display:block; width:100%;">
                                        <input type="checkbox" name="products[]" value="<?=$p->id?>" <?=in_array($p->id, $linked)?'checked':''?>>
                                        <strong><?=$p->name?></strong>
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
                            $('#saveStatus').fadeIn().delay(2000).fadeOut();
                        }
                    });
                }
            </script>
        <?php endif; ?>
    </div>
<?php endif; ?>
    <?php
}

function cloudflare_clientarea($vars) {
    if (!isset($_SESSION['uid'])) return "Access Denied";
    $clientId = $_SESSION['uid'];

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
                'eligibleProducts' => $eligibleProducts
            ]
        ];
    }

    // 2. Data Aggregation (Multi-Account BYOT)
    require_once __DIR__ . '/lib/API.php';
    $userAccounts = Capsule::table('mod_cloudflare_user_accounts')->where('client_id', $clientId)->get();
    $proxiedDomains = [];
    $whmcsDomains = Capsule::table('tbldomains')->where('userid', $clientId)->get();
    $whmcsDomainNames = $whmcsDomains->pluck('domain')->toArray();
    $fetchAllDomains = isset($vars['fetch_all_domains']) && $vars['fetch_all_domains'] == 'on';

    foreach ($userAccounts as $acc) {
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
            $acc = Capsule::table('mod_cloudflare_user_accounts')->where('id', $accId)->where('client_id', $clientId)->first();
            if (!$acc) throw new Exception("Unauthorized account.");

            $api = new \WHMCS\Module\Addon\Cloudflare\API($acc->api_token, $acc->email);
            $zoneId = $api->getZoneId($domain);

            switch ($_POST['op']) {
                case 'deleteZone':
                    $api->deleteZone($zoneId);
                    echo json_encode(['success' => true]); exit;
                case 'addRecord':
                    $type = $_POST['type'];
                    $name = $_POST['name'];
                    $content = $_POST['content'];
                    $proxied = isset($_POST['proxied']) && $_POST['proxied'] == 'true';
                    $api->addDNSRecord($zoneId, $type, $name, $content, 1, $proxied);
                    echo json_encode(['success' => true]); exit;
                case 'deleteRecord':
                    $recordId = $_POST['record_id'];
                    $api->deleteDNSRecord($zoneId, $recordId);
                    echo json_encode(['success' => true]); exit;
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
        }
    }

    $action = $_GET['action'] ?? 'overview';

    if ($action == 'manage') {
        $domain = $_GET['domain'];
        $accId = (int)$_GET['acc'];
        $account = Capsule::table('mod_cloudflare_user_accounts')->where('id', $accId)->where('client_id', $clientId)->first();
        
        if (!$account) {
            header("Location: index.php?m=cloudflare&error=invalid_account"); exit;
        }

        // Fetch DNS records
        $dnsError = null;
        try {
            $api = new \WHMCS\Module\Addon\Cloudflare\API($account->api_token, $account->email);
            $zoneId = $api->getZoneId(trim($domain));
            if (!$zoneId) {
                throw new \Exception("Zone ID not found for domain: " . trim($domain));
            }
            $dnsRecordsResp = $api->getDNSRecords($zoneId);
            $dnsRecords = isset($dnsRecordsResp['result']) ? $dnsRecordsResp['result'] : [];
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
                'dnsError' => $dnsError
            ]
        ];
    }

    return [
        'templatefile' => 'templates/client/overview',
        'vars' => [
            'restricted' => false,
            'userAccounts' => $userAccounts,
            'proxiedDomains' => $proxiedDomains,
            'domains' => $whmcsDomains,
            'companyname' => $GLOBALS['companyname']
        ]
    ];
}
