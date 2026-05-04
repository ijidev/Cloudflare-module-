<!-- Premium DNS Client Dashboard -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{if $restricted}
    <div class="cf-restricted-container animate-fade-in">
        <div class="cf-restricted-header">
            <div class="cf-lock-icon">
                <i class="fa fa-shield"></i>
                <i class="fa fa-lock cf-lock-badge"></i>
            </div>
            <h2>Premium DNS Protection</h2>
            <p>Infrastructure management is only available for active server clusters.</p>
        </div>

        <div class="cf-eligibility-card">
            <h3>How to unlock?</h3>
            <div class="cf-eligible-grid">
                {foreach from=$eligibleProducts item=p}
                    <div class="cf-plan-card">
                        <div class="cf-plan-icon"><i class="fa fa-server"></i></div>
                        <h4>{$p->name}</h4>
                        <a href="cart.php?a=add&pid={$p->id}" class="cf-btn-order">Unlock Now <i class="fa fa-arrow-right"></i></a>
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
{else}
    <div class="cf-dashboard-container">
        <!-- Top Header & Stats -->
        <div class="cf-dashboard-header">
            <div class="cf-main-title">
                <div class="cf-logo-bg">
                    <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare" style="height: 30px;">
                </div>
                <div class="cf-title-text">
                    <h1>Premium DNS</h1>
                    <p>Manage your infrastructure accounts and assets.</p>
                </div>
            </div>
            <div class="cf-stats-container">
                <div class="cf-stat-box">
                    <span class="cf-stat-val">{count($userAccounts)}</span>
                    <span class="cf-stat-lab">Connected</span>
                </div>
                <div class="cf-stat-box">
                    <span class="cf-stat-val">{count($proxiedDomains)}</span>
                    <span class="cf-stat-lab">Active Assets</span>
                </div>
            </div>
        </div>

        <!-- Main Navigation Tabs -->
        <div class="cf-tab-nav animate-slide-up">
            <button class="cf-tab-btn active" onclick="switchTab('accounts', this)">
                <i class="fa fa-key"></i> Managed Accounts
            </button>
            <button class="cf-tab-btn" onclick="switchTab('proxied', this)">
                <i class="fa fa-shield"></i> Active Assets
            </button>
            <button class="cf-tab-btn" onclick="switchTab('all', this)">
                <i class="fa fa-globe"></i> Sync Domains
            </button>
        </div>

        <!-- Tab Content: Managed Accounts -->
        <div id="tab-accounts" class="cf-tab-content active animate-fade-in">
            <div id="account-list-view">
                <div class="cf-content-header">
                    <div>
                        <h3>Infrastructure Accounts</h3>
                        <p>Link your accounts via API to manage security settings.</p>
                    </div>
                    {if count($userAccounts) > 0}
                    <button class="cf-btn-primary" onclick="showAddAccount()"><i class="fa fa-plus"></i> Add Account</button>
                    {/if}
                </div>
                
                <div class="cf-account-grid">
                    {foreach from=$userAccounts item=acc}
                    <div class="cf-account-card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <h4 style="margin:0 0 5px; font-weight:700;">{$acc->name}</h4>
                                <span class="cf-badge" style="background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:4px; font-size:11px;">{$acc->email|default:'API Token Auth'}</span>
                            </div>
                            <form method="post" onsubmit="return confirm('Disconnect this account?')">
                                <input type="hidden" name="action" value="deleteAccount">
                                <input type="hidden" name="id" value="{$acc->id}">
                                <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer;"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                        <div style="margin-top:20px; font-size:12px; color:#64748b;">
                            <i class="fa fa-clock-o"></i> Linked: {$acc->created_at|date_format}
                        </div>
                    </div>
                    {foreachelse}
                    <div class="cf-empty-state-container" style="grid-column: 1 / -1;">
                        <div class="cf-empty-card">
                            <div class="cf-empty-icon"><i class="fa fa-shield"></i></div>
                            <h3>No Accounts Connected</h3>
                            <p>You haven't linked any infrastructure accounts yet. Connect your first account to begin managing your edge assets.</p>
                            <button class="cf-btn-primary" onclick="showAddAccount()" style="margin: 20px auto 0;">
                                <i class="fa fa-plus"></i> Connect Your First Account
                            </button>
                        </div>
                    </div>
                    {/foreach}
                </div>
            </div>

            <div id="add-account-view" style="display:none;">
                <div class="cf-content-header" style="margin-bottom:20px;">
                    <div>
                        <button class="cf-btn-back" onclick="hideAddAccount()"><i class="fa fa-arrow-left"></i> Back to Accounts</button>
                        <h3 style="margin-top:15px;">Connect Infrastructure Account</h3>
                        <p>Integrate your account to unlock advanced edge protection.</p>
                    </div>
                </div>

                <div class="cf-setup-container">
                    <div class="cf-setup-form">
                        <form method="post" id="addAccountForm">
                            <input type="hidden" name="action" value="addAccount">
                            
                            <div class="cf-auth-toggle">
                                <div class="cf-toggle-btn active" id="btn-token" onclick="switchAuth('token')">API Token</div>
                                <div class="cf-toggle-btn" id="btn-global" onclick="switchAuth('global')">Global Key</div>
                            </div>

                            <input type="hidden" name="auth_type" id="auth_type" value="token">

                            <div class="cf-form-group">
                                <label>Account Label</label>
                                <input type="text" name="name" class="cf-input" placeholder="e.g. My Personal Account" required>
                            </div>

                            <div class="cf-form-group">
                                <label>Cloudflare Account ID</label>
                                <input type="text" name="account_id" class="cf-input" placeholder="Paste your 32-character Account ID" required>
                                <p class="cf-input-hint"><i class="fa fa-info-circle"></i> Found on your Cloudflare dashboard sidebar.</p>
                            </div>

                            <div id="email-field-container" style="display:none; margin-bottom:20px;">
                                <div class="cf-form-group">
                                    <label>Cloudflare Email</label>
                                    <input type="email" name="email" id="email-field" class="cf-input" placeholder="your@email.com">
                                </div>
                            </div>

                            <div id="input-token-container">
                                <div class="cf-form-group">
                                    <label>API Token</label>
                                    <input type="password" name="api_token" class="cf-input" placeholder="Paste your API token here">
                                    <p class="cf-input-hint"><i class="fa fa-lock"></i> Use "Edit Zone DNS" template for maximum security.</p>
                                </div>
                            </div>

                            <div id="input-global-container" style="display:none;">
                                <div class="cf-form-group">
                                    <label>Global API Key</label>
                                    <input type="password" name="global_key" class="cf-input" placeholder="Paste your Global API Key here">
                                    <p class="cf-input-hint hint-warning"><i class="fa fa-warning"></i> Global keys have full account access.</p>
                                </div>
                            </div>

                            <button type="submit" class="cf-btn-primary" style="width:100%; justify-content:center; padding:12px; margin-top:10px;">Complete Integration</button>
                        </form>
                    </div>

                    <div class="cf-setup-guide">
                        <h4 style="margin:0 0 25px; font-weight:700; font-size:16px;">Integration Guide</h4>
                        <div class="cf-guide-step"><div class="cf-step-num">1</div><div class="cf-step-content"><h5>Locate Account ID</h5><p>Select any domain on Cloudflare. Your Account ID is in the right-hand sidebar.</p></div></div>
                        <div class="cf-guide-step"><div class="cf-step-num">2</div><div class="cf-step-content" id="guide-step-2"><h5>Create API Token</h5><p>Go to My Profile > API Tokens. Use the "Edit Zone DNS" template.</p></div></div>
                        <div class="cf-guide-step"><div class="cf-step-num">3</div><div class="cf-step-content"><h5>Finalize Connection</h5><p>Paste both the Account ID and the Token into the form.</p></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Proxied Assets -->
        <div id="tab-proxied" class="cf-tab-content animate-fade-in" style="display:none;">
            <div class="cf-content-header">
                <div><h3>Active Infrastructure Assets</h3><p>Domains currently active across all your connected accounts.</p></div>
            </div>
            <div class="cf-table-card">
                <table class="cf-dashboard-table">
                    <thead><tr><th>Domain Name</th><th>Account</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        {foreach from=$proxiedDomains item=p}
                            <tr>
                                <td><strong>{$p.name}</strong></td>
                                <td><span class="cf-tag-account">{$p.account_name}</span></td>
                                <td><span class="cf-status-tag tag-active">{$p.status}</span></td>
                                <td style="text-align:right;"><a href="index.php?m=cloudflare&action=manage&domain={$p.name}&acc={$p.account_id}" class="cf-btn-manage-sm">Manage DNS <i class="fa fa-chevron-right"></i></a></td>
                            </tr>
                        {foreachelse}<tr><td colspan="4" class="text-center" style="padding:40px; color:#64748b;">No active assets found.</td></tr>{/foreach}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: Sync Domains -->
        <div id="tab-all" class="cf-tab-content animate-fade-in" style="display:none;">
            <div class="cf-content-header">
                <div><h3>Sync Domain Assets</h3><p>Every domain in your account and its current infrastructure status.</p></div>
            </div>
            <div class="cf-table-card">
                <table class="cf-dashboard-table">
                    <thead><tr><th>Domain Name</th><th>Status</th><th style="text-align:right;">Infrastructure Status</th></tr></thead>
                    <tbody>
                        {foreach from=$domains item=d}
                            {assign var="isProxied" value=false}
                            {foreach from=$proxiedDomains item=p}{if $p.name == $d->domain}{assign var="isProxied" value=true}{/if}{/foreach}
                            <tr>
                                <td><strong>{$d->domain}</strong></td>
                                <td><span class="label label-default">{$d->status}</span></td>
                                <td style="text-align:right;">
                                    {if $isProxied}<span class="cf-status-tag tag-active"><i class="fa fa-check"></i> Proxied</span>
                                    {else}<button class="cf-btn-sync-all" onclick="syncDomain('{$d->domain}')">Initialize Sync</button>{/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
{/if}

{literal}
<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.cf-tab-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.cf-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tabId).style.display = 'block';
    btn.classList.add('active');
}
function showAddAccount() { $('#account-list-view').fadeOut(200, function() { $('#add-account-view').fadeIn(200); }); }
function hideAddAccount() { $('#add-account-view').fadeOut(200, function() { $('#account-list-view').fadeIn(200); }); }
function switchAuth(type) {
    $('#auth_type').val(type);
    if (type === 'token') {
        $('#btn-token').addClass('active'); $('#btn-global').removeClass('active');
        $('#input-token-container').show(); $('#input-global-container').hide();
        $('#email-field-container').hide(); $('#email-field').prop('required', false);
        $('#guide-step-2 h5').text('Create API Token');
        $('#guide-step-2 p').html('Go to <strong>My Profile > API Tokens</strong>. Use the "Edit Zone DNS" template.');
    } else {
        $('#btn-global').addClass('active'); $('#btn-token').removeClass('active');
        $('#input-global-container').show(); $('#input-token-container').hide();
        $('#email-field-container').show(); $('#email-field').prop('required', true);
        $('#guide-step-2 h5').text('Get Global Key');
        $('#guide-step-2 p').html('View your "Global API Key" at the bottom of the API Tokens page.');
    }
}
function syncDomain(domain) {
    const accounts = {};
    document.querySelectorAll('.cf-account-card h4').forEach((h4, idx) => {
        const id = document.querySelectorAll('input[name="id"]')[idx].value;
        accounts[id] = h4.innerText;
    });
    if (Object.keys(accounts).length === 0) { Swal.fire('No Accounts', 'Please add an account first.', 'warning'); return; }
    Swal.fire({
        title: 'Initialize Sync', text: `Which account should ${domain} be connected to?`,
        input: 'select', inputOptions: accounts, showCancelButton: true, confirmButtonColor: '#f38020'
    }).then(res => { if (res.isConfirmed && res.value) window.location.href = `index.php?m=cloudflare&action=manage&domain=${domain}&acc=${res.value}&trigger_sync=1`; });
}
</script>

<style>
:root { --cf-orange: #f38020; --cf-dark: #0f172a; --cf-gray: #64748b; --cf-border: #e2e8f0; --cf-light: #f8fafc; }
.cf-dashboard-container { font-family: 'Inter', sans-serif; max-width: 1100px; margin: 0 auto; padding: 20px; color: var(--cf-dark); }

/* Header Fix */
.cf-dashboard-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; gap: 20px; }
.cf-main-title { display: flex; align-items: center; gap: 15px; flex: 1; }
.cf-logo-bg { background: #fff; padding: 12px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); flex-shrink: 0; }
.cf-title-text h1 { margin: 0; font-size: 24px; font-weight: 800; line-height: 1.1; }
.cf-title-text p { margin: 4px 0 0; color: var(--cf-gray); font-size: 13px; }

.cf-stats-container { display: flex; gap: 12px; }
.cf-stat-box { background: #fff; padding: 12px 20px; border-radius: 12px; border: 1px solid var(--cf-border); text-align: center; min-width: 110px; }
.cf-stat-val { display: block; font-size: 22px; font-weight: 800; color: var(--cf-orange); }
.cf-stat-lab { font-size: 9px; font-weight: 700; color: var(--cf-gray); text-transform: uppercase; letter-spacing: 0.5px; }

/* Tabs & Cards */
.cf-tab-nav { display: flex; gap: 10px; margin-bottom: 25px; background: #fff; padding: 6px; border-radius: 12px; border: 1px solid var(--cf-border); }
.cf-tab-btn { flex: 1; padding: 12px; border: none; background: none; border-radius: 8px; font-weight: 600; color: var(--cf-gray); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; }
.cf-tab-btn.active { background: var(--cf-dark); color: #fff; }
.cf-account-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px; }
.cf-account-card { background: #fff; border: 1px solid var(--cf-border); border-radius: 16px; padding: 24px; }

/* Table Responsiveness */
.cf-table-card { background: #fff; border-radius: 16px; border: 1px solid var(--cf-border); overflow-x: auto; -webkit-overflow-scrolling: touch; }
.cf-dashboard-table { width: 100%; border-collapse: collapse; min-width: 650px; }
.cf-dashboard-table th { background: #f8fafc; padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--cf-gray); text-transform: uppercase; }
.cf-dashboard-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

/* Setup & Forms */
.cf-setup-container { display: flex; background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid var(--cf-border); }
.cf-setup-form { flex: 1.2; padding: 40px; border-right: 1px solid var(--cf-border); }
.cf-setup-guide { flex: 0.8; padding: 40px; background: #f8fafc; }
.cf-auth-toggle { display: flex; background: #f1f5f9; border-radius: 50px; padding: 4px; margin-bottom: 30px; width: fit-content; }
.cf-toggle-btn { padding: 8px 20px; border-radius: 50px; cursor: pointer; font-size: 12px; font-weight: 700; }
.cf-toggle-btn.active { background: #fff; color: var(--cf-orange); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.cf-input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--cf-border); }

/* Empty State */
.cf-empty-card { background: #fff; border: 2px dashed var(--cf-border); border-radius: 20px; padding: 60px 40px; text-align: center; max-width: 600px; margin: 20px auto; }
.cf-empty-icon { font-size: 48px; color: #cbd5e1; margin-bottom: 20px; }

/* Mobile UI Polish */
@media (max-width: 768px) {
    .cf-dashboard-container { padding: 10px; }
    .cf-dashboard-header { flex-direction: column; align-items: flex-start; text-align: left; gap: 10px; margin-bottom: 20px; }
    .cf-main-title { flex-direction: row; align-items: center; gap: 10px; width: 100%; justify-content: flex-start; margin-bottom: 5px; }
    .cf-logo-bg { padding: 6px; border-radius: 8px; }
    .cf-logo-bg img { height: 20px; }
    .cf-title-text h1 { font-size: 18px; line-height: 1.2; }
    .cf-title-text p { font-size: 11px; margin-top: 2px; }
    
    .cf-stats-container { width: 100%; justify-content: space-between; gap: 10px; }
    .cf-stat-box { min-width: 0; padding: 10px; flex: 1; border-radius: 10px; }
    .cf-stat-val { font-size: 16px; }
    .cf-stat-lab { font-size: 9px; }
    
    .cf-tab-nav { overflow-x: auto; white-space: nowrap; gap: 4px; padding: 4px; margin-bottom: 15px; }
    .cf-tab-btn { padding: 8px 10px; font-size: 12px; flex: 1; }
    
    .cf-setup-container { flex-direction: column; }
    .cf-setup-form { padding: 20px; border-right: none; border-bottom: 1px solid var(--cf-border); }
    .cf-setup-guide { padding: 20px; }
    
    .cf-content-header { flex-direction: column; align-items: stretch; text-align: left; gap: 10px; margin-bottom: 15px; }
    .cf-content-header h3 { font-size: 16px; margin: 0; }
    .cf-content-header p { font-size: 12px; margin: 5px 0 10px; }
    .cf-btn-primary { width: 100%; justify-content: center; padding: 10px; font-size: 13px; }
    
    .cf-account-grid { grid-template-columns: 1fr; gap: 10px; }
    .cf-account-card { padding: 16px; border-radius: 12px; }
    .cf-account-card h4 { font-size: 15px; }
}

.cf-btn-primary { background: var(--cf-orange); color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; }
.cf-status-tag { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; }
.tag-active { background: #dcfce7; color: #166534; }
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
</style>
{/literal}
