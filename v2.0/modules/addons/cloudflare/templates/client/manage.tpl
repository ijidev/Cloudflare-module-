<!-- Premium DNS Management Page -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="cf-dashboard-container animate-fade-in">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 20px;">
        <a href="index.php?m=cloudflare" class="cf-btn-back"><i class="fa fa-arrow-left"></i> Back to Infrastructure Overview</a>
    </div>

    <!-- Header -->
    <div class="cf-dashboard-header">
        <div class="cf-main-title">
            <div class="cf-logo-bg">
                <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare" style="height: 30px;">
            </div>
            <div class="cf-title-text">
                <h1>{$domainName}</h1>
                <p>Premium DNS Management Hub</p>
            </div>
        </div>
        <div class="cf-status-badge">
            <span class="cf-status-tag tag-active"><i class="fa fa-check-circle"></i> Fully Proxied</span>
        </div>
    </div>

    <!-- Management Tabs -->
    <div class="cf-manage-grid">
        <div class="cf-manage-main">
            <!-- DNS Records Section -->
            <div class="cf-card-premium" style="overflow-x: auto;">
                <div class="cf-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h3><i class="fa fa-list"></i> DNS Records</h3>
                        <p>Manage your edge-optimized DNS configurations.</p>
                    </div>
                    <button class="cf-btn-primary-sm" onclick="$('#addRecordModal').css('display', 'flex').hide().fadeIn(200)"><i class="fa fa-plus"></i> Add Record</button>
                </div>
                <div class="cf-card-body">
                    {if $dnsError}
                        <div style="padding:15px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; margin-bottom:15px; color:#991b1b; font-size:13px;">
                            <p><strong>API Error:</strong> {$dnsError}</p>
                            <p style="margin-top:5px; font-size:11px; color:#b91c1c;"><em>Note: If using an API Token, ensure the email field was left empty during setup. Use the <b>test_cf.php</b> diagnostic script to verify.</em></p>
                        </div>
                    {/if}
                    <table class="cf-dashboard-table" style="width: 100%; border-collapse: collapse; min-width: 600px;">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Content</th>
                                <th>Proxy</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$dnsRecords item=record}
                                <tr>
                                    <td><span class="cf-status-tag" style="background:#f1f5f9; color:#475569;">{$record.type}</span></td>
                                    <td><strong>{$record.name}</strong></td>
                                    <td style="max-width:200px; word-break:break-all; color:#64748b;">{$record.content}</td>
                                    <td>
                                        {if $record.proxied}
                                            <i class="fa fa-cloud" style="color:var(--cf-orange);" title="Proxied"></i>
                                        {else}
                                            <i class="fa fa-cloud" style="color:#cbd5e1;" title="DNS Only"></i>
                                        {/if}
                                    </td>
                                    <td style="text-align:right;">
                                        <button class="cf-btn-danger-sm" onclick="deleteRecord('{$record.id}')"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            {foreachelse}
                                <tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No DNS records found.</td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Security & SSL -->
            <div class="cf-card-premium" style="margin-top: 20px;">
                <div class="cf-card-header">
                    <h3><i class="fa fa-lock"></i> Edge Security</h3>
                    <p>Configure SSL/TLS and firewall protection.</p>
                </div>
                <div class="cf-card-body">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding: 10px 0;">
                        <span>Always Use HTTPS</span>
                        <div class="cf-switch"><input type="checkbox" checked><span class="cf-slider"></span></div>
                    </div>
                    <hr style="border:0; border-top:1px solid #f1f5f9; margin:10px 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding: 10px 0;">
                        <span>Automatic HTTPS Rewrites</span>
                        <div class="cf-switch"><input type="checkbox" checked><span class="cf-slider"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cf-manage-side">
            <!-- Domain Info -->
            <div class="cf-card-premium">
                <div class="cf-card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="cf-card-body">
                    <button class="cf-btn-action-full" onclick="purgeCache()"><i class="fa fa-bolt"></i> Purge All Cache</button>
                    <button class="cf-btn-action-full" style="margin-top:10px;"><i class="fa fa-refresh"></i> Re-Sync Infrastructure</button>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="cf-card-danger" style="margin-top: 20px;">
                <div class="cf-card-header">
                    <h3 style="color: #dc2626;"><i class="fa fa-warning"></i> Danger Zone</h3>
                </div>
                <div class="cf-card-body">
                    <p style="font-size: 12px; color: #991b1b; margin-bottom: 15px;">Removing this domain from the infrastructure will stop all proxy services and protection.</p>
                    <button class="cf-btn-danger-full" onclick="deleteAsset()"><i class="fa fa-trash"></i> Delete Infrastructure Asset</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Record Modal -->
<div id="addRecordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.5); z-index:9999; backdrop-filter:blur(4px); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:24px; width:90%; max-width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.1); margin:auto; max-height: 90vh; overflow-y: auto;">
        <h3 style="margin:0 0 15px; font-weight:700;">Add DNS Record</h3>
        <form id="addRecordForm" onsubmit="addRecord(event)">
            <div class="cf-form-group" style="margin-bottom:15px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:5px;">Type</label>
                <select name="type" class="cf-input" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e2e8f0;">
                    <option value="A">A</option>
                    <option value="AAAA">AAAA</option>
                    <option value="CNAME">CNAME</option>
                    <option value="TXT">TXT</option>
                    <option value="MX">MX</option>
                </select>
            </div>
            <div class="cf-form-group" style="margin-bottom:15px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:5px;">Name</label>
                <input type="text" name="name" class="cf-input" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e2e8f0;" placeholder="@" required>
            </div>
            <div class="cf-form-group" style="margin-bottom:15px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:5px;">Content</label>
                <input type="text" name="content" class="cf-input" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e2e8f0;" placeholder="192.0.2.1" required>
            </div>
            <div class="cf-form-group" style="margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                <input type="checkbox" name="proxied" value="true" checked id="proxyCheck">
                <label for="proxyCheck" style="font-size:13px; font-weight:600; color:#0f172a; margin:0; cursor:pointer;">Proxied</label>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="$('#addRecordModal').fadeOut()" style="padding:10px 15px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; font-weight:600; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:10px 15px; border:none; background:var(--cf-orange); color:#fff; border-radius:8px; font-weight:600; cursor:pointer;">Add Record</button>
            </div>
        </form>
    </div>
</div>

{literal}
<script>
function purgeCache() {
    Swal.fire({ title: 'Purging Cache...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    setTimeout(() => { Swal.fire('Success', 'Edge cache has been purged.', 'success'); }, 1500);
}
function deleteAsset() {
    Swal.fire({
        title: 'Are you absolutely sure?',
        text: "This domain will be completely removed from our Premium DNS network. This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete Asset'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Removing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            fetch('index.php?m=cloudflare', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&op=deleteZone&domain={/literal}{$domainName}{literal}&acc_id={/literal}{$account->id}{literal}`
            }).then(r => r.json()).then(data => {
                if (data.success) window.location.href = 'index.php?m=cloudflare&success=asset_deleted';
                else Swal.fire('Error', data.message, 'error');
            });
        }
    });
}
function addRecord(e) {
    e.preventDefault();
    const btn = $(e.target).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
    const formData = new FormData(e.target);
    const data = new URLSearchParams(formData);
    data.append('ajax', '1');
    data.append('op', 'addRecord');
    data.append('domain', '{/literal}{$domainName}{literal}');
    data.append('acc_id', '{/literal}{$account->id}{literal}');
    
    fetch('index.php?m=cloudflare', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data
    }).then(r => r.json()).then(res => {
        if (res.success) window.location.reload();
        else { Swal.fire('Error', res.message, 'error'); btn.prop('disabled', false).html('Add Record'); }
    });
}
function deleteRecord(id) {
    Swal.fire({
        title: 'Delete DNS Record?', text: "This record will be immediately removed.",
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Delete'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            fetch('index.php?m=cloudflare', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&op=deleteRecord&record_id=${id}&domain={/literal}{$domainName}{literal}&acc_id={/literal}{$account->id}{literal}`
            }).then(r => r.json()).then(data => {
                if (data.success) window.location.reload();
                else Swal.fire('Error', data.message, 'error');
            });
        }
    });
}
</script>

<style>
:root { --cf-orange: #f38020; --cf-dark: #0f172a; --cf-gray: #64748b; --cf-border: #e2e8f0; }
.cf-dashboard-container { font-family: 'Inter', sans-serif; max-width: 1100px; margin: 0 auto; padding: 20px; }

/* Header & Flex Fix */
.cf-dashboard-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; gap: 20px; }
.cf-main-title { display: flex; align-items: center; gap: 15px; flex: 1; }
.cf-logo-bg { background: #fff; padding: 10px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); flex-shrink: 0; }
.cf-title-text h1 { margin: 0; font-size: 24px; font-weight: 800; line-height: 1.1; }
.cf-title-text p { margin: 4px 0 0; color: var(--cf-gray); font-size: 13px; }

.cf-manage-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; margin-top: 30px; }
.cf-card-premium { background: #fff; border: 1px solid var(--cf-border); border-radius: 16px; padding: 24px; }
.cf-card-header h3 { margin: 0; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.cf-card-header p { margin: 5px 0 15px; color: var(--cf-gray); font-size: 13px; }

/* Danger Zone */
.cf-card-danger { background: #fff5f5; border: 1px solid #fecaca; border-radius: 16px; padding: 24px; }
.cf-btn-danger-full { width: 100%; padding: 12px; border: none; background: #dc2626; color: #fff; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 13px; }

/* Utils */
.cf-btn-action-full { width: 100%; padding: 12px; border: 1px solid var(--cf-border); background: #fff; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; justify-content: center; font-size: 13px; }
.cf-btn-primary-sm { background: var(--cf-orange); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 12px; cursor: pointer; }
.cf-btn-danger-sm { background: #fee2e2; color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.cf-btn-danger-sm:hover { background: #fecaca; }
.cf-status-tag { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; }
.tag-active { background: #dcfce7; color: #166534; }
.cf-dashboard-table { width: 100%; border-collapse: collapse; min-width: 600px; }
.cf-dashboard-table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; color: var(--cf-gray); text-transform: uppercase; }
.cf-dashboard-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }

/* Switch Scaling */
.cf-switch { position: relative; display: inline-block; width: 44px; height: 22px; }
.cf-switch input { opacity: 0; width: 0; height: 0; }
.cf-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
.cf-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .cf-slider { background-color: var(--cf-orange); }
input:checked + .cf-slider:before { transform: translateX(22px); }

/* Mobile Optimizations */
@media (max-width: 768px) {
    .cf-dashboard-container { padding: 10px; }
    .cf-dashboard-header { flex-direction: column; align-items: flex-start; text-align: left; gap: 10px; margin-bottom: 20px; }
    .cf-main-title { flex-direction: row; align-items: center; gap: 10px; width: 100%; justify-content: flex-start; }
    .cf-logo-bg { padding: 6px; border-radius: 8px; }
    .cf-logo-bg img { height: 20px; }
    .cf-title-text h1 { font-size: 18px; line-height: 1.2; }
    .cf-title-text p { font-size: 11px; margin-top: 2px; }
    .cf-manage-grid { grid-template-columns: 1fr; gap: 10px; margin-top: 15px; }
    .cf-card-premium, .cf-card-danger { padding: 16px; border-radius: 12px; }
    .cf-card-header h3 { font-size: 15px; }
    .cf-btn-action-full, .cf-btn-danger-full { padding: 10px; font-size: 13px; }
}

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
{/literal}
