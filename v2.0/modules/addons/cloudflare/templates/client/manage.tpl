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

    {if !$isPro}
        <div class="cf-promo-banner">
            <div class="cf-promo-content">
                <i class="fa fa-star"></i>
                <span>Upgrade to <strong>Cloudflare Pro</strong> to unlock BYOT and Dedicated Account Isolation!</span>
            </div>
            <a href="cart.php?gid=addons" class="cf-btn-upgrade">Upgrade Now</a>
        </div>
    {/if}

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
                            <th style="width: 50px;"></th>
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
                                    <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}">
                                        <input type="hidden" name="op" value="deleteRecord">
                                        <input type="hidden" name="record_id" value="{$record.id}">
                                        <button type="submit" class="cf-btn-delete" title="Delete Record">
                                            <i class="fa fa-trash-o"></i>
                                        </button>
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
                    <div class="cf-control-item {if !$isPro}locked{/if}">
                        <span>Development Mode {if !$isPro}<i class="fa fa-lock"></i>{/if}</span>
                        <form method="post" action="index.php?m=cloudflare&action=manage&id={$cf_domain_id}">
                            <input type="hidden" name="op" value="toggleDev">
                            <button type="submit" class="cf-toggle {if $devMode}active{/if}" {if !$isPro}disabled{/if}>
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
</div>

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
.cf-badge-pro { background: #fef3c7; color: #92400e; box-shadow: 0 2px 5px rgba(243,128,32,0.2); }
.cf-promo-banner { background: linear-gradient(90deg, #f38020 0%, #fa6200 100%); color: white; padding: 15px 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.cf-btn-upgrade { background: white; color: #f38020; padding: 6px 15px; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 13px; }
.cf-grid { display: grid; grid-template-columns: 1fr 280px; gap: 24px; }
@media (max-width: 768px) { .cf-grid { grid-template-columns: 1fr; } }
.cf-card { border: 1px solid var(--cf-border); border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
.cf-card-header { background: #f8fafc; padding: 12px 16px; border-bottom: 1px solid var(--cf-border); display: flex; justify-content: space-between; align-items: center; }
.cf-card-header h4 { margin: 0; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.cf-table { width: 100%; border-collapse: collapse; }
.cf-table th { text-align: left; padding: 12px; font-size: 12px; color: #64748b; border-bottom: 1px solid var(--cf-border); }
.cf-table td { padding: 12px; border-bottom: 1px solid #f8fafc; font-size: 13px; }
.cf-label-type { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 10px; }
.cf-proxy-indicator.active { color: var(--cf-orange); }
.cf-btn-delete { background: none; border: none; color: #ef4444; cursor: pointer; }
.cf-form { padding: 15px; }
.cf-form-group { margin-bottom: 10px; }
.cf-input { width: 100%; padding: 8px; border: 1px solid var(--cf-border); border-radius: 6px; }
.cf-btn-primary { width: 100%; background: var(--cf-blue); color: white; border: none; padding: 10px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.cf-controls-list { padding: 15px; display: flex; flex-direction: column; gap: 15px; }
.cf-control-item { display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 500; }
.cf-control-item.locked { color: #94a3b8; }
.cf-toggle { width: 40px; height: 20px; background: #e2e8f0; border-radius: 20px; border: none; position: relative; cursor: pointer; }
.cf-toggle.active { background: var(--cf-green); }
.cf-toggle-handle { width: 16px; height: 16px; background: white; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: 0.3s; }
.cf-toggle.active .cf-toggle-handle { transform: translateX(20px); }
.cf-btn-cache { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; cursor: pointer; }
</style>
