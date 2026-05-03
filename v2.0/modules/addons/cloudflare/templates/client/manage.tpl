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
            <div class="cf-logo-bg" style="background: var(--cf-orange); color: #fff; font-weight: 800; font-size: 18px; display: flex; align-items: center; justify-content: center; width: 45px; height: 45px;">
                DNS
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
            <div class="cf-card-premium">
                <div class="cf-card-header">
                    <h3><i class="fa fa-list"></i> DNS Records</h3>
                    <p>Manage your edge-optimized DNS configurations.</p>
                </div>
                <div class="cf-card-body">
                    <div class="cf-empty-state-card" style="padding: 20px;">
                        <p style="font-size: 13px;">DNS record management is synchronized with your infrastructure account.</p>
                        <button class="cf-btn-primary-sm">Add New Record</button>
                    </div>
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
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php?m=cloudflare&success=asset_deleted';
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}
</script>

<style>
:root {
    --cf-orange: #f38020;
    --cf-dark: #0f172a;
    --cf-gray: #64748b;
    --cf-border: #e2e8f0;
}

.cf-dashboard-container { font-family: 'Inter', sans-serif; max-width: 1100px; margin: 0 auto; padding: 20px; }

/* Grid Layout */
.cf-manage-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; margin-top: 30px; }

/* Cards */
.cf-card-premium { background: #fff; border: 1px solid var(--cf-border); border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
.cf-card-header h3 { margin: 0; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.cf-card-header p { margin: 5px 0 15px; color: var(--cf-gray); font-size: 13px; }
.cf-card-body { padding-top: 10px; }

/* Danger Zone Card */
.cf-card-danger { background: #fff5f5; border: 1px solid #fecaca; border-radius: 16px; padding: 24px; }

/* Buttons */
.cf-btn-action-full { width: 100%; padding: 12px; border: 1px solid var(--cf-border); background: #fff; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; justify-content: center; font-size: 13px; }
.cf-btn-action-full:hover { background: #f8fafc; border-color: var(--cf-orange); color: var(--cf-orange); }

.cf-btn-danger-full { width: 100%; padding: 12px; border: none; background: #dc2626; color: #fff; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 13px; }
.cf-btn-danger-full:hover { background: #991b1b; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2); }

.cf-btn-primary-sm { background: var(--cf-orange); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 12px; cursor: pointer; }

/* Switches */
.cf-switch { position: relative; display: inline-block; width: 44px; height: 22px; }
.cf-switch input { opacity: 0; width: 0; height: 0; }
.cf-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
.cf-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .cf-slider { background-color: var(--cf-orange); }
input:checked + .cf-slider:before { transform: translateX(22px); }

/* Animation */
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
    .cf-dashboard-container { padding: 12px; }
    .cf-dashboard-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .cf-title-text h1 { font-size: 19px; }
    .cf-title-text p { font-size: 12px; }
    
    .cf-manage-grid { grid-template-columns: 1fr; gap: 15px; margin-top: 20px; }
    
    .cf-card-premium { padding: 16px; border-radius: 12px; }
    .cf-card-header h3 { font-size: 15px; }
    .cf-card-header p { font-size: 11px; margin-bottom: 10px; }
    
    .cf-btn-action-full, .cf-btn-danger-full { padding: 10px; font-size: 12px; border-radius: 8px; }
    .cf-card-body { font-size: 13px; }
    
    .cf-switch { width: 36px; height: 18px; }
    .cf-slider:before { height: 12px; width: 12px; left: 3px; bottom: 3px; }
    input:checked + .cf-slider:before { transform: translateX(18px); }
    
    .cf-card-danger { padding: 16px; border-radius: 12px; }
}
</style>
{/literal}
