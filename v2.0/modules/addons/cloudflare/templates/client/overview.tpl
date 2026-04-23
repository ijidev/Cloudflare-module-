<!-- Load External Assets -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="cf-dashboard-container">
    <!-- Top Header & Stats -->
    <div class="cf-dashboard-header">
        <div class="cf-main-title">
            <div class="cf-logo-bg">
                <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare">
            </div>
            <div class="cf-title-text">
                <h1>Infrastructure Dashboard</h1>
                <p>Manage your global edge settings and security.</p>
            </div>
        </div>
        <div class="cf-stats-container">
            <div class="cf-stat-box">
                <span class="cf-stat-val">{$totalDomains}</span>
                <span class="cf-stat-lab">Total Domains</span>
            </div>
            <div class="cf-stat-box pro-stat {if $isPro}active{/if}">
                <span class="cf-stat-val">{if $isPro}PRO{else}FREE{/if}</span>
                <span class="cf-stat-lab">Account Tier</span>
            </div>
        </div>
    </div>

    <!-- Dynamic Promo / Pro Settings -->
    {if !$isPro}
        <div class="cf-premium-banner animate-slide-up">
            <div class="cf-premium-info">
                <div class="cf-premium-badge"><i class="fa fa-bolt"></i> GO PRO</div>
                <h2>Unlock Enterprise-Grade Controls</h2>
                <p>BYOT support, Dedicated Account Isolation, and advanced DDoS mitigation are just one click away.</p>
            </div>
            <div class="cf-premium-cta">
                <a href="{$proUpgradeUrl}" class="cf-btn-premium">Upgrade Now <i class="fa fa-arrow-right"></i></a>
            </div>
        </div>
    {else}
        <div class="cf-settings-card animate-slide-up">
            <div class="cf-card-header-gradient">
                <h4><i class="fa fa-sliders"></i> Pro Configuration</h4>
                <span class="cf-pro-status-badge">ACTIVE</span>
            </div>
            <form method="post" action="index.php?m=cloudflare&action=updateProSettings" class="cf-settings-form" onsubmit="return handleSettingsUpdate(this)">
                <div class="cf-form-grid">
                    <div class="cf-form-group">
                        <label>Architecture Mode</label>
                        <select name="account_type" class="cf-select-custom" onchange="toggleByot(this.value)">
                            <option value="managed" {if $accountType == 'managed'}selected{/if}>Managed Core (Recommended)</option>
                            <option value="dedicated" {if $accountType == 'dedicated'}selected{/if}>Dedicated Sub-Account</option>
                            <option value="byot" {if $accountType == 'byot'}selected{/if}>BYOT (Personal Token)</option>
                        </select>
                    </div>
                    <div id="byot-section" class="cf-form-subgrid" style="{if $accountType != 'byot'}display:none;{/if}">
                        <div class="cf-form-group">
                            <label>API Token</label>
                            <input type="password" name="api_token" value="{$apiToken}" class="cf-input-custom" placeholder="••••••••••••••••">
                        </div>
                        <div class="cf-form-group">
                            <label>Account Email</label>
                            <input type="email" name="email" value="{$email}" class="cf-input-custom" placeholder="email@example.com">
                        </div>
                    </div>
                    <div class="cf-form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="cf-btn-save-settings">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    {/if}

    <!-- Domain Management Area -->
    <div class="cf-domain-card">
        <div class="cf-domain-header">
            <h3><i class="fa fa-globe"></i> Active Domain Assets</h3>
            <div class="cf-search-box">
                <i class="fa fa-search"></i>
                <input type="text" id="domainSearch" placeholder="Filter domains..." onkeyup="filterDomains()">
            </div>
        </div>
        <div class="cf-table-responsive">
            <table class="cf-dashboard-table" id="domainTable">
                <thead>
                    <tr>
                        <th>Domain Name</th>
                        <th>Network Status</th>
                        <th>Infrastructure</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$domains item=domain}
                        <tr>
                            <td>
                                <div class="cf-domain-info">
                                    <span class="cf-domain-text">{$domain->domain}</span>
                                </div>
                            </td>
                            <td>
                                <span class="cf-status-tag tag-active">Active</span>
                            </td>
                            <td>
                                <span class="cf-infra-tag">{if $isPro}PRO{else}MANAGED{/if}</span>
                            </td>
                            <td style="text-align: right;">
                                <a href="index.php?m=cloudflare&action=manage&id={$domain->id}" class="cf-btn-action-manage">
                                    Manage <i class="fa fa-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleByot(val) {
    const section = document.getElementById('byot-section');
    section.style.display = (val === 'byot' ? 'grid' : 'none');
}

function handleSettingsUpdate(form) {
    Swal.fire({
        title: 'Updating Tier Configuration...',
        text: 'Applying security architecture changes.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    return true;
}

function filterDomains() {
    let input = document.getElementById('domainSearch');
    let filter = input.value.toLowerCase();
    let table = document.getElementById('domainTable');
    let tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td')[0];
        if (td) {
            let txtValue = td.textContent || td.innerText;
            tr[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        }
    }
}

// Success Notification
{if $smarty.get.success}
    Swal.fire({
        icon: 'success',
        title: 'Settings Saved',
        text: 'Your Cloudflare configuration has been updated.',
        timer: 3000,
        showConfirmButton: false,
        background: '#fff',
        iconColor: '#058a5e'
    });
{/if}
</script>

<style>
:root {
    --cf-primary: #f38020;
    --cf-primary-dark: #d97706;
    --cf-dark: #0f172a;
    --cf-light-gray: #f1f5f9;
    --cf-text-gray: #64748b;
    --cf-success: #058a5e;
    --cf-card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
}

.cf-dashboard-container { font-family: 'Inter', sans-serif; color: var(--cf-dark); max-width: 1200px; margin: 0 auto; padding-bottom: 50px; }

/* Header & Stats */
.cf-dashboard-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
.cf-main-title { display: flex; align-items: center; gap: 20px; }
.cf-logo-bg { background: #fff; padding: 12px; border-radius: 14px; box-shadow: var(--cf-card-shadow); border: 1px solid #e2e8f0; }
.cf-logo-bg img { height: 40px; }
.cf-title-text h1 { margin: 0; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
.cf-title-text p { margin: 5px 0 0; color: var(--cf-text-gray); font-size: 15px; }

.cf-stats-container { display: flex; gap: 15px; }
.cf-stat-box { background: #fff; padding: 20px 30px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: var(--cf-card-shadow); text-align: center; min-width: 150px; }
.cf-stat-val { display: block; font-size: 26px; font-weight: 800; color: var(--cf-primary); }
.cf-stat-lab { font-size: 11px; font-weight: 700; color: var(--cf-text-gray); text-transform: uppercase; margin-top: 4px; }
.pro-stat.active { background: #fffbeb; border-color: #fef3c7; }
.pro-stat.active .cf-stat-val { color: #92400e; }

/* Premium Banner */
.cf-premium-banner { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 40px; border-radius: 24px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
.cf-premium-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(243, 128, 32, 0.2); color: var(--cf-primary); padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; margin-bottom: 15px; }
.cf-premium-info h2 { margin: 0 0 10px; font-size: 26px; font-weight: 800; }
.cf-premium-info p { margin: 0; opacity: 0.8; font-size: 16px; max-width: 500px; line-height: 1.5; }
.cf-btn-premium { background: var(--cf-primary); color: #fff; padding: 16px 35px; border-radius: 12px; font-weight: 700; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; }
.cf-btn-premium:hover { background: var(--cf-primary-dark); transform: translateX(5px); }

/* Settings Card */
.cf-settings-card { background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 40px; overflow: hidden; box-shadow: var(--cf-card-shadow); }
.cf-card-header-gradient { background: #f8fafc; padding: 15px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.cf-card-header-gradient h4 { margin: 0; font-size: 15px; font-weight: 700; }
.cf-pro-status-badge { background: var(--cf-success); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; }
.cf-settings-form { padding: 30px; }
.cf-form-grid { display: grid; grid-template-columns: 250px 1fr auto; gap: 25px; align-items: flex-end; }
.cf-form-subgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.cf-form-group label { display: block; font-size: 13px; font-weight: 700; color: var(--cf-text-gray); margin-bottom: 10px; }
.cf-select-custom, .cf-input-custom { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: var(--cf-light-gray); }
.cf-btn-save-settings { background: var(--cf-dark); color: #fff; border: none; padding: 13px 25px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.cf-btn-save-settings:hover { background: #1e293b; }

/* Domain List */
.cf-domain-card { background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: var(--cf-card-shadow); overflow: hidden; }
.cf-domain-header { padding: 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.cf-domain-header h3 { margin: 0; font-size: 18px; font-weight: 800; }
.cf-search-box { position: relative; width: 300px; }
.cf-search-box i { position: absolute; left: 15px; top: 13px; color: var(--cf-text-gray); }
.cf-search-box input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #e2e8f0; border-radius: 30px; font-size: 14px; background: var(--cf-light-gray); }

.cf-dashboard-table { width: 100%; border-collapse: collapse; }
.cf-dashboard-table th { padding: 15px 25px; text-align: left; background: #f8fafc; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--cf-text-gray); border-bottom: 1px solid #f1f5f9; }
.cf-dashboard-table td { padding: 20px 25px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }

.cf-domain-text { font-weight: 700; font-size: 15px; color: var(--cf-dark); }
.cf-status-tag { padding: 5px 12px; border-radius: 30px; font-size: 11px; font-weight: 800; }
.tag-active { background: #ecfdf5; color: #065f46; }
.cf-infra-tag { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }

.cf-btn-action-manage { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e2e8f0; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; color: var(--cf-dark); text-decoration: none; transition: 0.2s; }
.cf-btn-action-manage:hover { background: var(--cf-light-gray); border-color: var(--cf-primary); color: var(--cf-primary); }

/* Animations */
.animate-slide-up { animation: slideUp 0.6s cubic-bezier(0.165, 0.84, 0.44, 1); }
@keyframes slideUp {
    0% { transform: translateY(30px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}

@media (max-width: 992px) {
    .cf-dashboard-header { flex-direction: column; gap: 20px; }
    .cf-premium-banner { flex-direction: column; text-align: center; gap: 30px; }
    .cf-form-grid { grid-template-columns: 1fr; }
    .cf-form-subgrid { grid-template-columns: 1fr; }
    .cf-domain-header { flex-direction: column; gap: 20px; align-items: flex-start; }
    .cf-search-box { width: 100%; }
}
</style>
