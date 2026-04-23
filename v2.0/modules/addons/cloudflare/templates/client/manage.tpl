<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="cf-container">
    <div class="cf-header">
        <div class="cf-title">
            <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare" style="height: 24px;">
            <span>Manager: {$domain}</span>
        </div>
        <div class="cf-badge-container">
            <span class="cf-badge {if $isPro}cf-badge-pro{else}cf-badge-managed{/if}">
                {if $isPro}PRO TIER{else}CORE MANAGED{/if}
            </span>
            <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}" style="display:inline;">
                <input type="hidden" name="op" value="purgeCache">
                <button type="submit" class="cf-btn-cache" title="Purge Everything">
                    <i class="fa fa-bolt"></i> Purge Cache
                </button>
            </form>
        </div>
    </div>

    {if $error}
        <div class="cf-alert cf-alert-danger" style="background: #fee2e2; color: #991b1b; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444;">
            <i class="fa fa-exclamation-triangle"></i> {$error}
        </div>
    {/if}

    {if $needsMigration}
        <div class="cf-migration-overlay">
            <div class="cf-migration-card">
                <div class="cf-migration-header">
                    <h3><i class="fa fa-exchange"></i> Domain Migration Required</h3>
                    <p>This domain is not yet managed by our Cloudflare infrastructure.</p>
                </div>
                <div class="cf-migration-body">
                    <div class="cf-step">
                        <div class="cf-step-num">1</div>
                        <div class="cf-step-text">
                            <strong>Remove from Existing Account</strong>
                            <p>Log in to your current Cloudflare account and remove <strong>{$domain}</strong> from the dashboard.</p>
                        </div>
                    </div>
                    <div class="cf-step">
                        <div class="cf-step-num">2</div>
                        <div class="cf-step-text">
                            <strong>Wait for Propagation</strong>
                            <p>Wait about 2-5 minutes for Cloudflare to release the domain from their global edge.</p>
                        </div>
                    </div>
                    <div class="cf-step">
                        <div class="cf-step-num">3</div>
                        <div class="cf-step-text">
                            <strong>Migrate to Our Account</strong>
                            <p>Click the button below to add the domain to our system and automatically configure nameservers.</p>
                        </div>
                    </div>
                </div>
                <div class="cf-migration-footer">
                    <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}">
                        <input type="hidden" name="op" value="migrate">
                        <button type="submit" class="cf-btn-migrate-action"><i class="fa fa-rocket"></i> Begin Migration</button>
                    </form>
                    {if !$isPro}
                        <div style="margin-top: 15px; font-size: 13px; color: #64748b;">
                            Don't want to migrate? <a href="{$proUpgradeUrl}" style="color: #f38020; font-weight: 600;">Upgrade to Pro</a> to use your own Cloudflare account (BYOT).
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    {else}
        <div class="cf-grid">
            <div class="cf-card cf-dns-card">
                <div class="cf-card-header">
                    <h4><i class="fa fa-list"></i> DNS Records</h4>
                    <button class="cf-btn-refresh" onclick="window.location.reload()"><i class="fa fa-refresh"></i></button>
                </div>
                <div class="cf-table-wrapper">
                    <table class="cf-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Content</th>
                                <th>Proxy</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$dnsRecords item=record}
                                <tr>
                                    <td><span class="cf-label-type">{$record.type}</span></td>
                                    <td class="cf-name-cell">{$record.name}</td>
                                    <td class="cf-content-cell">{$record.content|truncate:40:"..."}</td>
                                    <td>
                                        <div class="cf-proxy-indicator {if $record.proxied}active{/if}">
                                            <i class="fa fa-cloud"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cf-row-actions">
                                            <button class="cf-btn-icon-edit" onclick="toggleEdit('{$record.id}')" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}" style="display:inline;">
                                                <input type="hidden" name="op" value="deleteRecord">
                                                <input type="hidden" name="record_id" value="{$record.id}">
                                                <button type="submit" class="cf-btn-icon-delete" onclick="return confirm('Delete this record?')" title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="edit-row-{$record.id}" style="display:none; background: #f8fafc;">
                                    <td colspan="5">
                                        <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}" class="cf-edit-form">
                                            <input type="hidden" name="op" value="editRecord">
                                            <input type="hidden" name="record_id" value="{$record.id}">
                                            <div class="cf-edit-grid">
                                                <input type="text" name="name" value="{$record.name}" class="cf-input-sm">
                                                <input type="text" name="content" value="{$record.content}" class="cf-input-sm">
                                                <div class="cf-edit-actions">
                                                    <button type="submit" class="cf-btn-save-sm">Save</button>
                                                    <button type="button" class="cf-btn-cancel-sm" onclick="toggleEdit('{$record.id}')">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cf-sidebar">
                <div class="cf-card">
                    <div class="cf-card-header">
                        <h4><i class="fa fa-plus-circle"></i> Add Record</h4>
                    </div>
                    <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}" class="cf-form">
                        <input type="hidden" name="op" value="addRecord">
                        <div class="cf-form-group">
                            <select name="type" class="cf-input" required>
                                <option value="A">A</option>
                                <option value="CNAME">CNAME</option>
                                <option value="MX">MX</option>
                                <option value="TXT">TXT</option>
                            </select>
                        </div>
                        <div class="cf-form-group">
                            <input type="text" name="name" class="cf-input" placeholder="Name (@/sub)" required>
                        </div>
                        <div class="cf-form-group">
                            <input type="text" name="content" class="cf-input" placeholder="Content" required>
                        </div>
                        <button type="submit" class="cf-btn-primary">Add Record</button>
                    </form>
                </div>

                <div class="cf-card">
                    <div class="cf-card-header">
                        <h4><i class="fa fa-shield"></i> Quick Toggles</h4>
                    </div>
                    <div class="cf-controls-list">
                        <div class="cf-control-item">
                            <span>Under Attack Mode</span>
                            <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}">
                                <input type="hidden" name="op" value="toggleSecurity">
                                <button type="submit" class="cf-toggle {if $underAttack}active{/if}">
                                    <div class="cf-toggle-handle"></div>
                                </button>
                            </form>
                        </div>
                        <div class="cf-control-item">
                            <span>Pause Cloudflare</span>
                            <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}">
                                <input type="hidden" name="op" value="togglePause">
                                <button type="submit" class="cf-toggle {if $isPaused}active{/if}">
                                    <div class="cf-toggle-handle"></div>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
</div>

<script>
function toggleEdit(id) {
    var row = document.getElementById('edit-row-' + id);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

<style>
:root {
    --cf-orange: #f38020;
    --cf-blue: #0051c3;
    --cf-green: #058a5e;
    --cf-dark: #1e293b;
    --cf-border: #e2e8f0;
}
.cf-container { font-family: 'Inter', sans-serif; color: var(--cf-dark); background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 25px rgba(0,0,0,0.05); }
.cf-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--cf-border); padding-bottom: 15px; }
.cf-title { display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 700; }
.cf-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.cf-badge-managed { background: #f1f5f9; color: #64748b; }
.cf-badge-pro { background: #fef3c7; color: #92400e; }

/* Migration Styles */
.cf-migration-overlay { padding: 40px 0; text-align: center; }
.cf-migration-card { max-width: 600px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
.cf-migration-header h3 { margin: 0 0 10px 0; font-size: 22px; font-weight: 700; }
.cf-migration-header p { color: #64748b; margin-bottom: 30px; }
.cf-migration-body { text-align: left; margin-bottom: 30px; }
.cf-step { display: flex; gap: 20px; margin-bottom: 20px; align-items: flex-start; }
.cf-step-num { background: #f1f5f9; color: #475569; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
.cf-step-text strong { display: block; margin-bottom: 4px; color: #1e293b; }
.cf-step-text p { margin: 0; font-size: 13px; color: #64748b; }
.cf-btn-migrate-action { background: var(--cf-orange); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 16px; transition: all 0.2s; }
.cf-btn-migrate-action:hover { background: #fa6200; transform: translateY(-1px); }

/* DNS & Row Actions */
.cf-row-actions { display: flex; gap: 10px; align-items: center; }
.cf-btn-icon-edit { background: none; border: none; color: #64748b; cursor: pointer; padding: 5px; border-radius: 4px; transition: all 0.2s; }
.cf-btn-icon-edit:hover { background: #f1f5f9; color: #0051c3; }
.cf-btn-icon-delete { background: none; border: none; color: #ef4444; cursor: pointer; padding: 5px; border-radius: 4px; transition: all 0.2s; opacity: 0.7; }
.cf-btn-icon-delete:hover { background: #fee2e2; opacity: 1; }

.cf-edit-grid { display: grid; grid-template-columns: 1fr 1fr 150px; gap: 10px; padding: 10px; }
.cf-input-sm { width: 100%; padding: 6px 10px; border: 1px solid var(--cf-border); border-radius: 4px; font-size: 13px; }
.cf-btn-save-sm { background: #058a5e; color: white; border: none; padding: 5px 12px; border-radius: 4px; font-weight: 600; cursor: pointer; }
.cf-btn-cancel-sm { background: #e2e8f0; color: #475569; border: none; padding: 5px 12px; border-radius: 4px; font-weight: 600; cursor: pointer; }

.cf-grid { display: grid; grid-template-columns: 1fr 280px; gap: 24px; }
.cf-card { border: 1px solid var(--cf-border); border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
.cf-card-header { background: #f8fafc; padding: 12px 16px; border-bottom: 1px solid var(--cf-border); display: flex; justify-content: space-between; align-items: center; }
.cf-card-header h4 { margin: 0; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.cf-table { width: 100%; border-collapse: collapse; }
.cf-table th { text-align: left; padding: 12px; font-size: 12px; color: #64748b; border-bottom: 1px solid var(--cf-border); }
.cf-table td { padding: 12px; border-bottom: 1px solid #f8fafc; font-size: 13px; vertical-align: middle; }
.cf-toggle { width: 40px; height: 20px; background: #e2e8f0; border-radius: 20px; border: none; position: relative; cursor: pointer; }
.cf-toggle.active { background: var(--cf-green); }
.cf-toggle-handle { width: 16px; height: 16px; background: white; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: all 0.2s; }
.cf-toggle.active .cf-toggle-handle { left: 22px; }
.cf-btn-cache { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; cursor: pointer; }
.cf-btn-cache:hover { background: #fef3c7; }
</style>
