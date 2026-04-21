<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<div class="cf-container">
    <div class="cf-header">
        <div class="cf-title">
            <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare" style="height: 24px;">
            <span>DNS Management</span>
        </div>
        <div class="cf-badge-container">
            <form method="post" action="clientarea.php?action=productdetails" style="display:inline;">
                <input type="hidden" name="id" value="{$serviceid}">
                <input type="hidden" name="modop" value="custom">
                <input type="hidden" name="a" value="purgeCache">
                <button type="submit" class="cf-btn-cache" title="Purge Everything">
                    <i class="fa fa-bolt"></i> Purge Cache
                </button>
            </form>
            <span class="cf-badge cf-badge-{$mode|strtolower}">Mode: {$mode}</span>
            {if $zoneStatus}
                <span class="cf-badge cf-badge-status-{$zoneStatus|strtolower}">Status: {$zoneStatus|ucfirst}</span>
            {/if}
        </div>
    </div>

    {if $error}
        <div class="cf-alert cf-alert-danger">
            <i class="fa fa-exclamation-triangle"></i> {$error}
        </div>
    {/if}

    <div class="cf-grid">
        <div class="cf-card cf-dns-card">
            <div class="cf-card-header">
                <h4><i class="fa fa-list"></i> DNS Records for {$domain}</h4>
                <button class="cf-btn-refresh" onclick="window.location.reload()"><i class="fa fa-refresh"></i></button>
            </div>
            <div class="cf-table-wrapper">
                <table class="cf-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Content</th>
                            <th>TTL</th>
                            <th>Proxy</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$dnsRecords item=record}
                            <tr class="cf-row-{$record.type|strtolower}">
                                <td><span class="cf-label-type">{$record.type}</span></td>
                                <td class="cf-name-cell">{$record.name}</td>
                                <td class="cf-content-cell">{$record.content|truncate:50:"..."}</td>
                                <td>{if $record.ttl == 1}Auto{else}{$record.ttl}{/if}</td>
                                <td>
                                    <div class="cf-proxy-indicator {if $record.proxied}active{/if}">
                                        <i class="fa fa-cloud"></i>
                                    </div>
                                </td>
                                <td>
                                    <form method="post" action="clientarea.php?action=productdetails">
                                        <input type="hidden" name="id" value="{$serviceid}">
                                        <input type="hidden" name="modop" value="custom">
                                        <input type="hidden" name="a" value="deleteRecord">
                                        <input type="hidden" name="record_id" value="{$record.id}">
                                        <button type="submit" class="cf-btn-delete" title="Delete Record">
                                            <i class="fa fa-trash-o"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="6" class="text-center">No DNS records found.</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>

        <div class="cf-sidebar">
            <div class="cf-card">
                <div class="cf-card-header">
                    <h4><i class="fa fa-plus-circle"></i> Quick Add</h4>
                </div>
                <form method="post" action="clientarea.php?action=productdetails" class="cf-form">
                    <input type="hidden" name="id" value="{$serviceid}">
                    <input type="hidden" name="modop" value="custom">
                    <input type="hidden" name="a" value="addRecord">
                    
                    <div class="cf-form-group">
                        <label>Record Type</label>
                        <select name="type" class="cf-input" required>
                            <option value="A">A Record</option>
                            <option value="CNAME">CNAME Record</option>
                            <option value="MX">MX Record</option>
                            <option value="TXT">TXT Record</option>
                        </select>
                    </div>
                    <div class="cf-form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="cf-input" placeholder="@ or subdomain" required>
                    </div>
                    <div class="cf-form-group">
                        <label>Content</label>
                        <input type="text" name="content" class="cf-input" placeholder="IP, Value, etc." required>
                    </div>
                    <div class="cf-form-group cf-checkbox-group">
                        <label class="cf-checkbox-label">
                            <input type="checkbox" name="proxied" checked>
                            <span>Proxy through Cloudflare</span>
                        </label>
                    </div>
                    <button type="submit" class="cf-btn-primary">Add Record</button>
                </form>
            </div>

            <div class="cf-card cf-security-card">
                <div class="cf-card-header">
                    <h4><i class="fa fa-shield"></i> Security Controls</h4>
                </div>
                <div class="cf-controls-list">
                    <div class="cf-control-item">
                        <div class="cf-control-info">
                            <strong>Under Attack Mode</strong>
                            <span>DDoS Protection</span>
                        </div>
                        <form method="post" action="clientarea.php?action=productdetails" id="uaForm">
                            <input type="hidden" name="id" value="{$serviceid}">
                            <input type="hidden" name="modop" value="custom">
                            <input type="hidden" name="a" value="toggleUnderAttack">
                            <input type="hidden" name="value" value="{if $securityLevel == 'under_attack'}off{else}on{/if}">
                            <button type="submit" class="cf-toggle {if $securityLevel == 'under_attack'}active{/if}">
                                <div class="cf-toggle-handle"></div>
                            </button>
                        </form>
                    </div>
                    <div class="cf-control-item">
                        <div class="cf-control-info">
                            <strong>Development Mode</strong>
                            <span>Bypass Cache</span>
                        </div>
                        <form method="post" action="clientarea.php?action=productdetails" id="devForm">
                            <input type="hidden" name="id" value="{$serviceid}">
                            <input type="hidden" name="modop" value="custom">
                            <input type="hidden" name="a" value="toggleDevMode">
                            <input type="hidden" name="value" value="{if $devMode == 'on'}off{else}on{/if}">
                            <button type="submit" class="cf-toggle {if $devMode == 'on'}active{/if}">
                                <div class="cf-toggle-handle"></div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="cf-card cf-ns-card">
                <div class="cf-card-header">
                    <h4><i class="fa fa-server"></i> Assigned Nameservers</h4>
                </div>
                <div class="cf-ns-list">
                    {if $nameservers}
                        {foreach from=$nameservers item=ns}
                            <div class="cf-ns-item">
                                <i class="fa fa-globe"></i>
                                <code>{$ns}</code>
                                <button class="cf-btn-copy" onclick="navigator.clipboard.writeText('{$ns}')"><i class="fa fa-copy"></i></button>
                            </div>
                        {/foreach}
                    {else}
                        <div class="cf-ns-empty">Not generated yet.</div>
                    {/if}
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
    --cf-dark: #2d333a;
    --cf-light: #f5f7fa;
    --cf-border: #e2e8f0;
    --cf-bg: #ffffff;
}

.cf-btn-cache {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    color: #92400e;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    margin-right: 8px;
    transition: all 0.2s;
    text-transform: uppercase;
}

.cf-btn-cache:hover { background: #fde68a; box-shadow: 0 2px 8px rgba(243, 128, 32, 0.2); }

.cf-container {
    font-family: 'Inter', sans-serif;
    color: var(--cf-dark);
    background: var(--cf-bg);
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 24px;
    margin-bottom: 30px;
}

.cf-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--cf-border);
}

.cf-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 700;
}

.cf-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.cf-badge-managed { background: #e0f2fe; color: #0369a1; }
.cf-badge-dedicated { background: #fef3c7; color: #92400e; }
.cf-badge-byot { background: #f3e8ff; color: #7e22ce; }
.cf-badge-status-active { background: #d1fae5; color: #065f46; }

.cf-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
}

@media (max-width: 992px) {
    .cf-grid { grid-template-columns: 1fr; }
}

.cf-card {
    background: var(--cf-bg);
    border: 1px solid var(--cf-border);
    border-radius: 10px;
    overflow: hidden;
}

.cf-card-header {
    padding: 16px;
    background: var(--cf-light);
    border-bottom: 1px solid var(--cf-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cf-card-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cf-table-wrapper {
    overflow-x: auto;
}

.cf-table {
    width: 100%;
    border-collapse: collapse;
}

.cf-table th {
    text-align: left;
    padding: 12px 16px;
    background: #f8fafc;
    font-size: 13px;
    color: #64748b;
    border-bottom: 1px solid var(--cf-border);
}

.cf-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--cf-border);
    font-size: 14px;
}

.cf-label-type {
    background: var(--cf-light);
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 11px;
}

.cf-proxy-indicator {
    color: #cbd5e1;
    font-size: 18px;
}

.cf-proxy-indicator.active {
    color: var(--cf-orange);
}

.cf-btn-delete {
    background: transparent;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 16px;
    padding: 4px;
    transition: transform 0.2s;
}

.cf-btn-delete:hover { transform: scale(1.2); }

.cf-form { padding: 16px; }
.cf-form-group { margin-bottom: 16px; }
.cf-form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
.cf-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--cf-border);
    border-radius: 6px;
    font-size: 14px;
}

.cf-btn-primary {
    width: 100%;
    background: var(--cf-blue);
    color: white;
    border: none;
    padding: 10px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.cf-btn-primary:hover { opacity: 0.9; }

.cf-controls-list { padding: 16px; display: flex; flex-direction: column; gap: 16px; }
.cf-control-item { display: flex; justify-content: space-between; align-items: center; }
.cf-control-info { display: flex; flex-direction: column; }
.cf-control-info strong { font-size: 13px; margin-bottom: 2px; }
.cf-control-info span { font-size: 11px; color: #64748b; }

.cf-toggle {
    width: 44px;
    height: 22px;
    background: #cbd5e1;
    border-radius: 20px;
    position: relative;
    border: none;
    cursor: pointer;
    transition: background 0.3s;
    padding: 0;
}

.cf-toggle.active { background: var(--cf-green); }
.cf-toggle-handle {
    width: 18px;
    height: 18px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    transition: transform 0.3s;
}
.cf-toggle.active .cf-toggle-handle { transform: translateX(22px); }

.cf-ns-list { padding: 12px; display: flex; flex-direction: column; gap: 8px; }
.cf-ns-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: var(--cf-light);
    border-radius: 6px;
    font-size: 12px;
}

.cf-ns-item code { flex: 1; color: var(--cf-blue); }
.cf-btn-copy { background: transparent; border: none; color: #94a3b8; cursor: pointer; }
.cf-btn-copy:hover { color: var(--cf-blue); }

.cf-alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.cf-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
</style>
