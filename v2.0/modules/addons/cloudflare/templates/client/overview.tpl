<!-- Load External Assets -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{if $restricted}
    <div class="cf-restricted-container animate-fade-in">
        <div class="cf-restricted-header">
            <div class="cf-lock-icon">
                <i class="fa fa-shield-alt"></i>
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
                <i class="fa fa-shield-alt"></i> Active Proxied Domains
            </button>
            <button class="cf-tab-btn" onclick="switchTab('all', this)">
                <i class="fa fa-globe"></i> All Domains
            </button>
        </div>

        <!-- Tab Content: Managed Accounts -->
        <div id="tab-accounts" class="cf-tab-content active animate-fade-in">
            <div class="cf-content-header">
                <h3>Your Cloudflare Accounts</h3>
                <button class="cf-btn-primary" onclick="openAccountModal()">
                    <i class="fa fa-plus"></i> Add New Account
                </button>
            </div>
            <div class="cf-account-grid">
                {foreach from=$userAccounts item=acc}
                    <div class="cf-account-card">
                        <div class="cf-acc-header">
                            <div class="cf-acc-icon"><i class="fa fa-user-circle"></i></div>
                            <div class="cf-acc-info">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <h4 style="margin:0 0 5px; font-weight:700;">{$acc->name}</h4>
                                <span class="cf-badge cf-badge-active" style="font-size:10px;">{$acc->email}</span>
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
                    <div class="cf-empty-state" style="grid-column: 1 / -1; text-align:center; padding:40px; background:#f8fafc; border-radius:12px; border:1px dashed #e2e8f0;">
                        <i class="fa fa-shield" style="font-size:32px; color:#cbd5e1; margin-bottom:15px;"></i>
                        <p style="color:#64748b;">No accounts linked. Add one to start managing your domains.</p>
                    </div>
                    {/if}
                </div>
            </div>

            <div id="add-account-view" style="display:none;">
                <div class="cf-content-header" style="margin-bottom:20px;">
                    <button class="cf-btn-manage-sm" onclick="hideAddAccount()" style="margin-bottom:15px;"><i class="fa fa-arrow-left"></i> Back to Accounts</button>
                    <h3>Connect Cloudflare Account</h3>
                    <p>Integrate your Cloudflare account to unlock advanced edge protection.</p>
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

                            <div style="margin-bottom:20px;">
                                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:8px; color:var(--cf-dark);">Account Label</label>
                                <input type="text" name="name" class="cf-input" placeholder="e.g. Personal Account" required>
                            </div>

                            <div style="margin-bottom:20px;">
                                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:8px; color:var(--cf-dark);">Cloudflare Email</label>
                                <input type="email" name="email" class="cf-input" placeholder="your@email.com" required>
                            </div>

                            <div id="input-token-container" style="margin-bottom:25px;">
                                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:8px; color:var(--cf-dark);">API Token</label>
                                <input type="password" name="api_token" id="api_token" class="cf-input" placeholder="Paste your API token here">
                                <p style="font-size:11px; color:#94a3b8; margin-top:8px;"><i class="fa fa-lock"></i> Use "Edit Zone DNS" template for maximum security.</p>
                            </div>

                            <div id="input-global-container" style="display:none; margin-bottom:25px;">
                                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:8px; color:var(--cf-dark);">Global API Key</label>
                                <input type="password" name="global_key" id="global_key" class="cf-input" placeholder="Paste your Global API Key here">
                                <p style="font-size:11px; color:#f59e0b; margin-top:8px;"><i class="fa fa-warning"></i> Global keys have full account access. Use with caution.</p>
                            </div>

                            <button type="submit" class="cf-btn-primary" style="width:100%; justify-content:center; padding:12px;">Complete Integration</button>
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
                                <p>Use the <strong>"Edit Zone DNS"</strong> template and ensure <strong>"All Zones"</strong> is selected under permissions.</p>
                            </div>
                        </div>

                        <div class="cf-guide-step">
                            <div class="cf-step-num">3</div>
                            <div class="cf-step-content">
                                <h5>Authorize Platform</h5>
                                <p>Copy the generated token and paste it into the form to the left to finalize integration.</p>
                            </div>
                        </div>

                        <div style="margin-top:40px; padding:20px; background:rgba(243, 128, 32, 0.05); border-radius:12px; border:1px solid rgba(243, 128, 32, 0.1);">
                            <p style="font-size:12px; color:var(--cf-orange); margin:0; line-height:1.6;">
                                <i class="fa fa-info-circle"></i> <strong>Note:</strong> We recommend using API Tokens over Global Keys for enhanced security and scoped access.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
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
        </script>

        <!-- Tab Content: Proxied Domains -->
        <div id="tab-proxied" class="cf-tab-content animate-fade-in">
            <div class="cf-content-header">
                <h3>Active "Proxied" Assets</h3>
                <p>Domains currently active across all your connected Cloudflare accounts.</p>
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
                                        <button class="cf-btn-del-sm" onclick="deleteZone('{$p.name}', '{$p.account_id}')" title="Delete Zone from Cloudflare">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="4" class="text-center">No proxied domains found in your connected accounts.</td></tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: All Domains -->
        <div id="tab-all" class="cf-tab-content animate-fade-in">
            <div class="cf-content-header">
                <h3>All Domain Assets</h3>
                <p>Every domain registered in your {$companyname} account and its current Cloudflare status.</p>
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

<!-- Account Modal (SweetAlert is handled in JS) -->

{literal}
<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.cf-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.cf-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
    btn.classList.add('active');
}

function openAccountModal(acc = null) {
    Swal.fire({
        title: acc ? 'Edit Cloudflare Account' : 'Add Cloudflare Account',
        html: `
            <div class="cf-modal-form">
                <input type="hidden" id="modal-account-id" value="${acc ? acc.id : ''}">
                <div class="form-group">
                    <label>Account Label</label>
                    <input type="text" id="modal-name" class="swal2-input" placeholder="e.g. Personal Account" value="${acc ? acc.name : ''}">
                </div>
                <div class="form-group">
                    <label>Cloudflare Email</label>
                    <input type="email" id="modal-email" class="swal2-input" placeholder="email@example.com" value="${acc ? acc.email : ''}">
                </div>
                <div class="form-group">
                    <label>API Token</label>
                    <input type="password" id="modal-token" class="swal2-input" placeholder="Enter Token" value="${acc ? acc.api_token : ''}">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Account',
        preConfirm: () => {
            const name = document.getElementById('modal-name').value;
            const email = document.getElementById('modal-email').value;
            const token = document.getElementById('modal-token').value;
            if (!name || !email || !token) {
                Swal.showValidationMessage('Please fill all fields');
            }
            return { name, email, token, id: document.getElementById('modal-account-id').value };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?m=cloudflare&action=saveAccount';
            
            Object.keys(result.value).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key === 'id' ? 'account_id' : key;
                input.value = result.value[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function syncDomain(domain) {
    const accounts = {};
    document.querySelectorAll('.cf-account-card').forEach(card => {
        const id = card.querySelector('input[name="account_id"]').value;
        const name = card.querySelector('strong').innerText;
        accounts[id] = name;
    });

    if (Object.keys(accounts).length === 0) {
        Swal.fire('No Accounts', 'Please add a Cloudflare account first in the Managed Accounts tab.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Initialize Infrastructure',
        text: `Which Cloudflare account should ${domain} be connected to?`,
        input: 'select',
        inputOptions: accounts,
        inputPlaceholder: 'Select an account',
        showCancelButton: true,
        confirmButtonColor: '#f38020',
        preConfirm: (accountId) => {
            if (!accountId) {
                Swal.showValidationMessage('You must select an account');
            }
            return accountId;
        }
    }).then(res => {
        if (res.isConfirmed) {
            // Proceed to manage page with sync trigger
            window.location.href = `index.php?m=cloudflare&action=manage&domain=${domain}&acc=${res.value}&trigger_sync=1`;
        }
    });
}

function deleteZone(domain, accId) {
    Swal.fire({
        title: 'Delete Zone?',
        text: `Are you sure you want to permanently delete ${domain} from your Cloudflare account? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, Delete it'
    }).then(res => {
        if (res.isConfirmed) {
            Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            fetch('index.php?m=cloudflare', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&op=deleteZone&domain=${domain}&acc_id=${accId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', 'The domain has been removed from Cloudflare.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Failed to delete zone', 'error');
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
    --cf-light: #f1f5f9;
}

.cf-dashboard-container { font-family: 'Inter', sans-serif; max-width: 1100px; margin: 0 auto; padding: 20px; }

/* Restricted Access Styles */
.cf-restricted-container { max-width: 800px; margin: 60px auto; text-align: center; font-family: 'Inter', sans-serif; }
.cf-restricted-header { margin-bottom: 40px; }
.cf-lock-icon { font-size: 60px; color: var(--cf-gray); position: relative; display: inline-block; margin-bottom: 20px; }
.cf-lock-badge { position: absolute; bottom: 0; right: -5px; font-size: 24px; color: var(--cf-orange); background: #fff; border-radius: 50%; padding: 4px; }
.cf-restricted-header h2 { font-size: 32px; font-weight: 800; margin: 0 0 10px; }
.cf-restricted-header p { color: var(--cf-gray); font-size: 16px; max-width: 500px; margin: 0 auto; }

.cf-eligibility-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 40px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
.cf-eligibility-card h3 { margin-top: 0; font-size: 20px;/* Account Grid & Form */
.cf-account-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
.cf-account-card { background: #fff; border: 1px solid #eef2f6; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: 0.3s; }
.cf-account-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }

/* Premium Add Account Section */
.cf-setup-container { display: flex; background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #eef2f6; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-top: 20px; }
.cf-setup-form { flex: 1.2; padding: 40px; border-right: 1px solid #f1f5f9; }
.cf-setup-guide { flex: 0.8; padding: 40px; background: #f8fafc; }

.cf-auth-toggle { display: flex; background: #f1f5f9; border-radius: 50px; padding: 4px; margin-bottom: 25px; width: fit-content; }
.cf-toggle-btn { padding: 8px 20px; border-radius: 50px; cursor: pointer; font-size: 13px; font-weight: 600; color: #64748b; transition: 0.3s; }
.cf-toggle-btn.active { background: #fff; color: var(--cf-orange); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

.cf-guide-step { display: flex; gap: 15px; margin-bottom: 25px; }
.cf-step-num { width: 28px; height: 28px; background: var(--cf-orange); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.cf-step-content h5 { margin: 0 0 5px 0; font-weight: 600; font-size: 14px; color: var(--cf-dark); }
.cf-step-content p { margin: 0; font-size: 13px; color: #64748b; line-height: 1.5; }

.cf-restricted-footer { margin-top: 30px; color: var(--cf-gray); font-size: 13px; }
.cf-plan-card h4 { margin: 0 0 15px; font-size: 15px; font-weight: 700; }
.cf-btn-order { display: inline-block; background: var(--cf-dark); color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: 0.2s; }
.cf-btn-order:hover { background: var(--cf-orange); color: #fff; }

.cf-restricted-footer { margin-top: 30px; color: var(--cf-gray); font-size: 13px; }

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .cf-dashboard-header { flex-direction: column; align-items: flex-start; gap: 20px; }
    .cf-stats-container { width: 100%; }
    .cf-stat-box { flex: 1; }
    .cf-tab-nav { flex-direction: column; }
    .cf-tab-btn { justify-content: flex-start; width: 100%; }
    .cf-account-grid { grid-template-columns: 1fr; }
    .cf-table-card { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .cf-dashboard-table { min-width: 600px; }
    .cf-restricted-header h2 { font-size: 24px; }
}

/* Header */
.cf-dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.cf-main-title { display: flex; align-items: center; gap: 20px; }
.cf-logo-bg { background: #fff; padding: 12px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
.cf-logo-bg img { height: 35px; }
.cf-title-text h1 { margin: 0; font-size: 24px; font-weight: 800; }
.cf-title-text p { margin: 5px 0 0; color: var(--cf-gray); }

.cf-stats-container { display: flex; gap: 15px; }
.cf-stat-box { background: #fff; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; text-align: center; }
.cf-stat-val { display: block; font-size: 24px; font-weight: 800; color: var(--cf-orange); }
.cf-stat-lab { font-size: 11px; font-weight: 600; color: var(--cf-gray); text-transform: uppercase; }

/* Tabs */
.cf-tab-nav { display: flex; gap: 10px; margin-bottom: 25px; background: #fff; padding: 8px; border-radius: 12px; border: 1px solid #e2e8f0; }
.cf-tab-btn { flex: 1; padding: 12px; border: none; background: none; border-radius: 8px; font-weight: 600; color: var(--cf-gray); cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.cf-tab-btn.active { background: var(--cf-dark); color: #fff; }
.cf-tab-btn:hover:not(.active) { background: var(--cf-light); }

.cf-tab-content { display: none; }
.cf-tab-content.active { display: block; }

/* Content Headers */
.cf-content-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.cf-content-header h3 { margin: 0; font-size: 20px; font-weight: 700; }
.cf-content-header p { margin: 5px 0 0; color: var(--cf-gray); }

/* Account Cards */
.cf-account-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; }
.cf-account-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
.cf-acc-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.cf-acc-icon { font-size: 32px; color: var(--cf-gray); }
.cf-acc-info strong { display: block; font-size: 16px; }
.cf-acc-info span { font-size: 13px; color: var(--cf-gray); }
.cf-acc-actions { display: flex; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 15px; }
.cf-acc-actions button { flex: 1; border: none; padding: 8px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-edit { background: var(--cf-light); color: var(--cf-dark); }
.btn-del { background: #fee2e2; color: #dc2626; max-width: 45px; }

/* Tables */
.cf-table-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.cf-dashboard-table { width: 100%; border-collapse: collapse; }
.cf-dashboard-table th { background: #f8fafc; padding: 15px 20px; text-align: left; font-size: 12px; font-weight: 700; color: var(--cf-gray); text-transform: uppercase; }
.cf-dashboard-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; }
.cf-tag-account { background: var(--cf-light); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; color: var(--cf-dark); }
.cf-btn-manage-sm { text-decoration: none; font-size: 13px; font-weight: 700; color: var(--cf-orange); }

.cf-btn-sync-all { background: var(--cf-orange); color: #fff; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; }
.cf-btn-del-sm { background: #fee2e2; color: #dc2626; border: none; padding: 5px 8px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
.cf-btn-del-sm:hover { background: #fecaca; }

/* Utils */
.cf-btn-primary { background: var(--cf-orange); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
{/literal}
