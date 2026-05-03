<!-- Load External Assets -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{if $restricted}
    <div class="cf-restricted-container animate-fade-in">
        <div class="cf-restricted-header">
            <div class="cf-lock-icon">
                <i class="fa fa-shield"></i>
                <i class="fa fa-lock cf-lock-badge"></i>
            </div>
            <h2>Advanced Protection Locked</h2>
            <p>Cloudflare Infrastructure management is only available for domains associated with our Premium Server Clusters.</p>
        </div>

        <div class="cf-eligibility-card">
            <h3>How to unlock?</h3>
            <p>Purchase or upgrade to one of the following eligible plans to activate automated Cloudflare synchronization:</p>
            
            <div class="cf-eligible-grid">
                {foreach from=$eligibleProducts item=p}
                    <div class="cf-plan-card">
                        <div class="cf-plan-icon"><i class="fa fa-server"></i></div>
                        <h4>{$p->name}</h4>
                        <a href="cart.php?a=add&pid={$p->id}" class="cf-btn-order">View Plan <i class="fa fa-arrow-right"></i></a>
                    </div>
                {foreachelse}
                    <div class="cf-empty-state">
                        <p>No eligible plans are currently available. Please contact support.</p>
                    </div>
                {/foreach}
            </div>
        </div>

        <div class="cf-restricted-footer">
            <p>Already have one of these plans? Make sure your service is <strong>Active</strong>.</p>
        </div>
    </div>
{else}
    <div class="cf-dashboard-container">
        <!-- Top Header & Stats -->
        <div class="cf-dashboard-header">
            <div class="cf-main-title">
                <div class="cf-logo-bg">
                    <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare">
                </div>
                <div class="cf-title-text">
                    <h1>Cloudflare Infrastructure</h1>
                    <p>Manage your personal Cloudflare accounts and proxied assets.</p>
                </div>
            </div>
            <div class="cf-stats-container">
                <div class="cf-stat-box">
                    <span class="cf-stat-val">{count($userAccounts)}</span>
                    <span class="cf-stat-lab">Managed Accounts</span>
                </div>
                <div class="cf-stat-box">
                    <span class="cf-stat-val">{count($proxiedDomains)}</span>
                    <span class="cf-stat-lab">Proxied Assets</span>
                </div>
            </div>
        </div>

        <!-- Main Navigation Tabs -->
        <div class="cf-tab-nav animate-slide-up">
            <button class="cf-tab-btn active" onclick="switchTab('accounts', this)">
                <i class="fa fa-key"></i> Managed Accounts
            </button>
            <button class="cf-tab-btn" onclick="switchTab('proxied', this)">
                <i class="fa fa-shield"></i> Active Proxied Domains
            </button>
            <button class="cf-tab-btn" onclick="switchTab('all', this)">
                <i class="fa fa-globe"></i> All Domains
            </button>
        </div>

        <!-- Tab Content: Managed Accounts -->
        <div id="tab-accounts" class="cf-tab-content active animate-fade-in">
            <div id="account-list-view">
                <div class="cf-content-header">
                    <div>
                        <h3>Cloudflare Accounts</h3>
                        <p>Link your Cloudflare accounts via API to manage domains and security settings.</p>
                    </div>
                    <button class="cf-btn-primary" onclick="showAddAccount()"><i class="fa fa-plus"></i> Add New Account</button>
                </div>
                
                <div class="cf-account-grid">
                    {foreach from=$userAccounts item=acc}
                    <div class="cf-account-card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <h4 style="margin:0 0 5px; font-weight:700;">{$acc->name}</h4>
                                <span class="cf-badge" style="background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:4px; font-size:11px;">{$acc->email}</span>
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
                    {/foreach}
                    {if count($userAccounts) == 0}
                    <div class="cf-empty-state-card">
                        <i class="fa fa-shield"></i>
                        <p>No accounts linked. Add one to start managing your domains.</p>
                    </div>
                    {/if}
                </div>
            </div>

            <div id="add-account-view" style="display:none;">
                <div class="cf-content-header" style="margin-bottom:20px;">
                    <div>
                        <button class="cf-btn-back" onclick="hideAddAccount()"><i class="fa fa-arrow-left"></i> Back to Accounts</button>
                        <h3 style="margin-top:15px;">Connect Cloudflare Account</h3>
                        <p>Integrate your Cloudflare account to unlock advanced edge protection.</p>
                    </div>
                </div>

                <div class="cf-setup-container">
                    <div class="cf-setup-form">
                        <form method="post" id="addAccountForm">
                            <input type="hidden" name="action" value="addAccount">
                            
                            <div class="cf-auth-toggle">
                                <div class="cf-toggle-btn active" id="btn-token" onclick="switchAuth('token')">API Token</div>
                                <div class="cf-toggle-btn" id="btn-global" onclick="switchAuth('global')">Global API Key</div>
                            </div>

                            <input type="hidden" name="auth_type" id="auth_type" value="token">

                            <div class="cf-form-group">
                                <label>Account Label</label>
                                <input type="text" name="name" class="cf-input" placeholder="e.g. Personal Account" required>
                            </div>

                            <div class="cf-form-group">
                                <label>Cloudflare Email</label>
                                <input type="email" name="email" class="cf-input" placeholder="your@email.com" required>
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
                        
                        <div class="cf-guide-step">
                            <div class="cf-step-num">1</div>
                            <div class="cf-step-content">
                                <h5>Access API Dashboard</h5>
                                <p>Login to Cloudflare and navigate to <strong>My Profile > API Tokens</strong>.</p>
                            </div>
                        </div>

                        <div class="cf-guide-step">
                            <div class="cf-step-num">2</div>
                            <div class="cf-step-content" id="guide-step-2">
                                <h5>Create API Token</h5>
                                <p>Use the <strong>"Edit Zone DNS"</strong> template and ensure <strong>"All Zones"</strong> is selected.</p>
                            </div>
                        </div>

                        <div class="cf-guide-step">
                            <div class="cf-step-num">3</div>
                            <div class="cf-step-content">
                                <h5>Authorize Platform</h5>
                                <p>Copy the generated token and paste it into the form to finalize integration.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Proxied Domains -->
        <div id="tab-proxied" class="cf-tab-content animate-fade-in" style="display:none;">
            <div class="cf-content-header">
                <div>
                    <h3>Active Proxied Assets</h3>
                    <p>Domains currently active across all your connected Cloudflare accounts.</p>
                </div>
            </div>
            <div class="cf-table-card">
                <table class="cf-dashboard-table">
                    <thead>
                        <tr>
                            <th>Domain Name</th>
                            <th>Account</th>
                            <th>Network Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$proxiedDomains item=p}
                            <tr>
                                <td><strong>{$p.name}</strong></td>
                                <td><span class="cf-tag-account">{$p.account_name}</span></td>
                                <td><span class="cf-status-tag tag-active">{$p.status}</span></td>
                                <td style="text-align:right;">
                                    <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                                        <a href="index.php?m=cloudflare&action=manage&domain={$p.name}&acc={$p.account_id}" class="cf-btn-manage-sm">Manage <i class="fa fa-chevron-right"></i></a>
                                        <button class="cf-btn-del-sm" onclick="deleteZone('{$p.name}', '{$p.account_id}')" title="Delete Zone">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="4" class="text-center" style="padding:40px; color:#64748b;">No proxied domains found.</td></tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: All Domains -->
        <div id="tab-all" class="cf-tab-content animate-fade-in" style="display:none;">
            <div class="cf-content-header">
                <div>
                    <h3>All Domain Assets</h3>
                    <p>Every domain in your account and its current Cloudflare status.</p>
                </div>
            </div>
            <div class="cf-table-card">
                <table class="cf-dashboard-table">
                    <thead>
                        <tr>
                            <th>Domain Name</th>
                            <th>Status</th>
                            <th style="text-align:right;">Cloudflare Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$domains item=d}
                            {assign var="isProxied" value=false}
                            {foreach from=$proxiedDomains item=p}
                                {if $p.name == $d->domain}{assign var="isProxied" value=true}{/if}
                            {/foreach}
                            <tr>
                                <td><strong>{$d->domain}</strong></td>
                                <td><span class="label label-default">{$d->status}</span></td>
                                <td style="text-align:right;">
                                    {if $isProxied}
                                        <span class="cf-status-tag tag-active"><i class="fa fa-check"></i> Proxied</span>
                                    {else}
                                        <button class="cf-btn-sync-all" onclick="syncDomain('{$d->domain}')">Initialize Sync</button>
                                    {/if}
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

function showAddAccount() {
    $('#account-list-view').fadeOut(200, function() {
        $('#add-account-view').fadeIn(200);
    });
}

function hideAddAccount() {
    $('#add-account-view').fadeOut(200, function() {
        $('#account-list-view').fadeIn(200);
    });
}

function switchAuth(type) {
    $('#auth_type').val(type);
    if (type === 'token') {
        $('#btn-token').addClass('active');
        $('#btn-global').removeClass('active');
        $('#input-token-container').show();
        $('#input-global-container').hide();
        $('#guide-step-2 h5').text('Create API Token');
        $('#guide-step-2 p').html('Use the <strong>"Edit Zone DNS"</strong> template and ensure <strong>"All Zones"</strong> is selected.');
    } else {
        $('#btn-global').addClass('active');
        $('#btn-token').removeClass('active');
        $('#input-global-container').show();
        $('#input-token-container').hide();
        $('#guide-step-2 h5').text('Find Global Key');
        $('#guide-step-2 p').html('Scroll to the bottom of the API Tokens page and click <strong>"View"</strong> next to Global API Key.');
    }
}

function syncDomain(domain) {
    const accounts = {};
    document.querySelectorAll('.cf-account-card h4').forEach((h4, idx) => {
        // Simple mapping for this demo, usually you'd have IDs in the DOM
        const id = document.querySelectorAll('input[name="id"]')[idx].value;
        accounts[id] = h4.innerText;
    });

    if (Object.keys(accounts).length === 0) {
        Swal.fire('No Accounts', 'Please add a Cloudflare account first.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Initialize Sync',
        text: `Which account should ${domain} be connected to?`,
        input: 'select',
        inputOptions: accounts,
        inputPlaceholder: 'Select an account',
        showCancelButton: true,
        confirmButtonColor: '#f38020'
    }).then(res => {
        if (res.isConfirmed && res.value) {
            window.location.href = `index.php?m=cloudflare&action=manage&domain=${domain}&acc=${res.value}&trigger_sync=1`;
        }
    });
}

function deleteZone(domain, accId) {
    Swal.fire({
        title: 'Delete Zone?',
        text: `Are you sure you want to remove ${domain} from Cloudflare?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626'
    }).then(res => {
        if (res.isConfirmed) {
            Swal.showLoading();
            fetch('index.php?m=cloudflare', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&op=deleteZone&domain=${domain}&acc_id=${accId}`
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else Swal.fire('Error', data.message, 'error');
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
    --cf-light: #f8fafc;
    --cf-border: #e2e8f0;
}

.cf-dashboard-container { font-family: 'Inter', sans-serif; max-width: 1100px; margin: 0 auto; padding: 20px; color: var(--cf-dark); }

/* Header & Stats */
.cf-dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.cf-main-title { display: flex; align-items: center; gap: 20px; }
.cf-logo-bg { background: #fff; padding: 12px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.cf-logo-bg img { height: 35px; }
.cf-title-text h1 { margin: 0; font-size: 24px; font-weight: 800; }
.cf-title-text p { margin: 5px 0 0; color: var(--cf-gray); font-size: 14px; }

.cf-stats-container { display: flex; gap: 15px; }
.cf-stat-box { background: #fff; padding: 15px 25px; border-radius: 12px; border: 1px solid var(--cf-border); text-align: center; }
.cf-stat-val { display: block; font-size: 24px; font-weight: 800; color: var(--cf-orange); }
.cf-stat-lab { font-size: 10px; font-weight: 700; color: var(--cf-gray); text-transform: uppercase; letter-spacing: 0.5px; }

/* Tabs Navigation */
.cf-tab-nav { display: flex; gap: 10px; margin-bottom: 25px; background: #fff; padding: 6px; border-radius: 12px; border: 1px solid var(--cf-border); }
.cf-tab-btn { flex: 1; padding: 12px; border: none; background: none; border-radius: 8px; font-weight: 600; color: var(--cf-gray); cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; }
.cf-tab-btn.active { background: var(--cf-dark); color: #fff; }
.cf-tab-btn:hover:not(.active) { background: var(--cf-light); }

/* Grid & Cards */
.cf-account-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
.cf-account-card { background: #fff; border: 1px solid var(--cf-border); border-radius: 16px; padding: 24px; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
.cf-account-card:hover { transform: translateY(-4px); box-shadow: 0 12px 20px -5px rgba(0,0,0,0.05); }

/* Premium Add Account Setup */
.cf-setup-container { display: flex; background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid var(--cf-border); box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
.cf-setup-form { flex: 1.2; padding: 40px; border-right: 1px solid var(--cf-border); }
.cf-setup-guide { flex: 0.8; padding: 40px; background: #f8fafc; }

.cf-auth-toggle { display: flex; background: #f1f5f9; border-radius: 50px; padding: 4px; margin-bottom: 30px; width: fit-content; }
.cf-toggle-btn { padding: 8px 24px; border-radius: 50px; cursor: pointer; font-size: 13px; font-weight: 700; color: #64748b; transition: 0.3s; }
.cf-toggle-btn.active { background: #fff; color: var(--cf-orange); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

.cf-form-group { margin-bottom: 20px; }
.cf-form-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
.cf-input { width: 100%; padding: 12px 16px; border-radius: 10px; border: 1px solid var(--cf-border); font-size: 14px; transition: 0.2s; }
.cf-input:focus { border-color: var(--cf-orange); outline: none; box-shadow: 0 0 0 3px rgba(243, 128, 32, 0.1); }
.cf-input-hint { font-size: 11px; color: var(--cf-gray); margin-top: 8px; display: flex; align-items: center; gap: 5px; }
.hint-warning { color: #f59e0b; }

.cf-guide-step { display: flex; gap: 15px; margin-bottom: 30px; }
.cf-step-num { width: 30px; height: 30px; background: var(--cf-orange); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; }
.cf-step-content h5 { margin: 0 0 5px 0; font-weight: 700; font-size: 14px; }
.cf-step-content p { margin: 0; font-size: 13px; color: var(--cf-gray); line-height: 1.6; }

/* Dashboard Tables */
.cf-table-card { background: #fff; border-radius: 16px; border: 1px solid var(--cf-border); overflow: hidden; }
.cf-dashboard-table { width: 100%; border-collapse: collapse; }
.cf-dashboard-table th { background: #f8fafc; padding: 16px 24px; text-align: left; font-size: 11px; font-weight: 800; color: var(--cf-gray); text-transform: uppercase; letter-spacing: 1px; }
.cf-dashboard-table td { padding: 18px 24px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
.cf-status-tag { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
.tag-active { background: #dcfce7; color: #166534; }

/* Buttons & Utils */
.cf-btn-primary { background: var(--cf-orange); color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
.cf-btn-primary:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(243, 128, 32, 0.2); }
.cf-btn-back { background: none; border: none; color: var(--cf-gray); font-weight: 600; font-size: 13px; cursor: pointer; padding: 0; }
.cf-btn-back:hover { color: var(--cf-dark); }
.cf-btn-manage-sm { text-decoration: none; color: var(--cf-orange); font-weight: 700; font-size: 13px; }
.cf-btn-del-sm { background: #fee2e2; color: #dc2626; border: none; padding: 8px; border-radius: 8px; cursor: pointer; }

/* Restricted State */
.cf-restricted-container { max-width: 700px; margin: 80px auto; text-align: center; }
.cf-lock-icon { font-size: 64px; color: #cbd5e1; position: relative; margin-bottom: 30px; }
.cf-lock-badge { position: absolute; bottom: -5px; right: -10px; font-size: 28px; color: var(--cf-orange); background: #fff; border-radius: 50%; padding: 5px; }
.cf-eligible-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; }
.cf-plan-card { background: #fff; border: 1px solid var(--cf-border); border-radius: 16px; padding: 24px; transition: 0.3s; }
.cf-plan-card:hover { border-color: var(--cf-orange); transform: translateY(-5px); }
.cf-btn-order { display: block; background: var(--cf-dark); color: #fff; text-decoration: none; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700; margin-top: 15px; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 850px) {
    .cf-setup-container { flex-direction: column; }
    .cf-setup-form { border-right: none; border-bottom: 1px solid var(--cf-border); }
    .cf-dashboard-header { flex-direction: column; align-items: flex-start; gap: 20px; }
}
</style>
{/literal}
