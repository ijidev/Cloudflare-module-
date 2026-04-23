<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="cf-container">
    <!-- Header & Stats -->
    <div class="cf-header-overview">
        <div class="cf-title">
            <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare" style="height: 32px;">
            <span>Manager Dashboard</span>
        </div>
        <div class="cf-stats-grid">
            <div class="cf-stat-card">
                <div class="cf-stat-value">{$totalDomains}</div>
                <div class="cf-stat-label">Total Domains</div>
            </div>
            <div class="cf-stat-card">
                <div class="cf-stat-value">{$managedCount}</div>
                <div class="cf-stat-label">Sync Enabled</div>
            </div>
            <div class="cf-stat-card">
                <div class="cf-stat-value">{if $isPro}PRO{else}FREE{/if}</div>
                <div class="cf-stat-label">Account Tier</div>
            </div>
        </div>
    </div>

    <!-- Upgrade Banner -->
    {if !$isPro}
        <div class="cf-promo-banner-large">
            <div class="cf-promo-content">
                <h3><i class="fa fa-rocket"></i> Unlock Cloudflare Pro</h3>
                <p>Get Dedicated Account Isolation, BYOT (Bring Your Own Token), and enhanced security controls.</p>
            </div>
            <div class="cf-promo-action">
                <a href="{$proUpgradeUrl}" class="cf-btn-upgrade-large">Upgrade Now</a>
            </div>
        </div>
    {/if}

    <!-- Pro Settings Section -->
    {if $isPro}
        <div class="cf-card-overview" style="margin-bottom: 30px;">
            <div class="cf-card-header">
                <h4><i class="fa fa-sliders"></i> Cloudflare Pro Configuration</h4>
            </div>
            <form method="post" action="index.php?m=cloudflare&action=updateProSettings" class="cf-pro-form">
                <div class="cf-pro-grid">
                    <div class="cf-pro-item">
                        <label>Account Mode</label>
                        <select name="account_type" class="cf-input" onchange="toggleByot(this.value)">
                            <option value="managed" {if $accountType == 'managed'}selected{/if}>Managed (Our Infrastructure)</option>
                            <option value="dedicated" {if $accountType == 'dedicated'}selected{/if}>Dedicated (Isolated Account)</option>
                            <option value="byot" {if $accountType == 'byot'}selected{/if}>BYOT (Bring Your Own Token)</option>
                        </select>
                    </div>
                    <div id="byot-fields" style="{if $accountType != 'byot'}display:none;{/if} contents;">
                        <div class="cf-pro-item">
                            <label>Cloudflare API Token</label>
                            <input type="password" name="api_token" value="{$apiToken}" class="cf-input" placeholder="Enter your scoped token">
                        </div>
                        <div class="cf-pro-item">
                            <label>Cloudflare Email (Optional)</label>
                            <input type="email" name="email" value="{$email}" class="cf-input" placeholder="Required for Global Key">
                        </div>
                    </div>
                    <div class="cf-pro-item" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="cf-btn-save-pro">Save Settings</button>
                    </div>
                </div>
            </form>
        </div>
        <script>
        function toggleByot(val) {
            document.getElementById('byot-fields').style.display = (val === 'byot' ? 'contents' : 'none');
        }
        </script>
    {/if}

    <!-- Domain List -->
    <div class="cf-card-overview">
        <div class="cf-card-header">
            <h4><i class="fa fa-globe"></i> Your Domains</h4>
        </div>
        <div class="cf-table-wrapper">
            <table class="cf-table">
                <thead>
                    <tr>
                        <th>Domain Name</th>
                        <th>Status</th>
                        <th style="width: 150px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$domains item=domain}
                        <tr>
                            <td><strong>{$domain->domain}</strong></td>
                            <td>
                                <span class="label label-success">Active</span>
                            </td>
                            <td style="text-align: right;">
                                <a href="index.php?m=cloudflare&action=manage&id={$domain->id}" class="cf-btn-manage">
                                    <i class="fa fa-cog"></i> Manage
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .cf-pro-form { padding: 20px; background: #f8fafc; }
    .cf-pro-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 150px; gap: 20px; }
    .cf-pro-item label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #64748b; }
    .cf-btn-save-pro { background: #0f172a; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; transition: background 0.2s; }
    .cf-btn-save-pro:hover { background: #1e293b; }
    .cf-input { width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
    
    .cf-container { font-family: 'Inter', sans-serif; max-width: 1100px; margin: 0 auto; color: #1e293b; }
    .cf-header-overview { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; gap: 20px; }
    .cf-title { display: flex; align-items: center; gap: 15px; font-size: 24px; font-weight: 700; color: #0f172a; }
    
    .cf-stats-grid { display: flex; gap: 15px; }
    .cf-stat-card { background: #fff; padding: 15px 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); text-align: center; min-width: 140px; }
    .cf-stat-value { font-size: 24px; font-weight: 700; color: #f38020; }
    .cf-stat-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-top: 5px; }

    .cf-promo-banner-large { background: linear-gradient(135deg, #f38020 0%, #faad14 100%); color: #fff; padding: 30px; border-radius: 16px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 15px -3px rgba(243, 128, 32, 0.3); }
    .cf-promo-content h3 { margin: 0 0 10px 0; font-size: 24px; font-weight: 700; }
    .cf-promo-content p { margin: 0; font-size: 16px; opacity: 0.9; }
    .cf-btn-upgrade-large { background: #fff; color: #f38020; padding: 12px 30px; border-radius: 8px; font-weight: 700; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .cf-btn-upgrade-large:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); color: #d97706; }

    .cf-card-overview { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .cf-card-header { padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .cf-card-header h4 { margin: 0; font-weight: 700; color: #334155; }
    
    .cf-table-wrapper { width: 100%; overflow-x: auto; }
    .cf-table { width: 100%; border-collapse: collapse; }
    .cf-table th { padding: 12px 20px; text-align: left; background: #f1f5f9; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; }
    .cf-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; }
    
    .cf-btn-manage { display: inline-flex; align-items: center; gap: 8px; background: #f1f5f9; color: #475569; padding: 6px 15px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px; border: 1px solid #e2e8f0; transition: all 0.2s; }
    .cf-btn-manage:hover { background: #e2e8f0; color: #1e293b; }
    
    @media (max-width: 768px) {
        .cf-header-overview { flex-direction: column; }
        .cf-promo-banner-large { flex-direction: column; text-align: center; gap: 20px; }
        .cf-stats-grid { width: 100%; justify-content: space-between; }
        .cf-stat-card { flex: 1; min-width: 0; padding: 10px; }
    }
</style>
