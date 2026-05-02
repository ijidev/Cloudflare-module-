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
    
    <!-- Account Privileges Check Alert -->
    {if $isPro && !$dedicatedAvailable}
    <div class="cf-notice-bar">
        <i class="fa fa-info-circle"></i>
        <span>Dedicated Sub-Account isolation is currently unavailable on this master account. Please use <strong>BYOT</strong> for isolated management.</span>
    </div>
    {/if}

    <!-- Educational Landing Section -->
    <div class="cf-architecture-guide animate-slide-up">
        <div class="cf-guide-header">
            <h3>Infrastructure Architecture Guide</h3>
            <p>Understand the deployment modes available for your domains.</p>
        </div>
        <div class="cf-guide-grid">
            <div class="cf-guide-card">
                <div class="cf-guide-icon" style="color: #058a5e; background: #d1fae5;"><i class="fa fa-shield"></i></div>
                <h4>Managed Core</h4>
                <div class="cf-guide-tags"><span class="cf-tag" style="background: #e2e8f0; color: #475569;">Free / Pro</span></div>
                <p>Your domains are securely proxied through our enterprise master account. You get high performance and DDoS mitigation without the hassle of managing a Cloudflare account yourself.</p>
                <ul class="cf-guide-list">
                    <li><i class="fa fa-check" style="color:#058a5e;"></i> Zero setup required</li>
                    <li><i class="fa fa-check" style="color:#058a5e;"></i> Managed directly via this portal</li>
                    <li><i class="fa fa-exclamation-triangle" style="color:#ca8a04;"></i> Shared risk (If one domain is flagged, others may be affected)</li>
                </ul>
            </div>
            
            {if $dedicatedAvailable}
            <div class="cf-guide-card">
                <div class="cf-guide-icon" style="color: #f38020; background: #ffedd5;"><i class="fa fa-lock"></i></div>
                <h4>Dedicated Sub-Account</h4>
                <div class="cf-guide-tags"><span class="cf-tag" style="background: #fef3c7; color: #92400e;">Paid Tier</span></div>
                <p>We automatically provision a totally isolated Cloudflare account using your WHMCS email. It operates independently while still billing through your hosting invoice.</p>
                <ul class="cf-guide-list">
                    <li><i class="fa fa-check" style="color:#058a5e;"></i> Direct login to Cloudflare.com</li>
                    <li><i class="fa fa-check" style="color:#058a5e;"></i> Isolated security perimeter</li>
                    <li><i class="fa fa-times" style="color:#dc2626;"></i> Must not have an existing CF account</li>
                </ul>
            </div>
            {/if}

            <div class="cf-guide-card cf-card-recommended">
                <div class="cf-guide-icon" style="color: #3b82f6; background: #dbeafe;"><i class="fa fa-key"></i></div>
                <div class="cf-recommended-badge">RECOMMENDED</div>
                <h4>BYOT (Personal Token)</h4>
                <div class="cf-guide-tags"><span class="cf-tag" style="background: #e2e8f0; color: #475569;">Free Tier</span></div>
                <p>Connect your personal Cloudflare account using an API token. You maintain 100% ownership, administrative control, and full data privacy.</p>
                <ul class="cf-guide-list">
                    <li><i class="fa fa-check" style="color:#058a5e;"></i> Full administrative control</li>
                    <li><i class="fa fa-check" style="color:#058a5e;"></i> Private & Isolated environment</li>
                    <li><i class="fa fa-info-circle" style="color:#ca8a04;"></i> Manual token generation required</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Architecture & Pro Settings -->
    <div class="cf-settings-card animate-slide-up">
        <div class="cf-card-header-gradient">
            <h4><i class="fa fa-sliders"></i> Architecture Configuration</h4>
            <div class="cf-header-right">
                <button class="cf-btn-info" onclick="showByotGuide()"><i class="fa fa-question-circle"></i> Setup Guide</button>
                {if $isPro}<span class="cf-pro-status-badge">PRO ACTIVE</span>{/if}
            </div>
        </div>
        <form method="post" action="index.php?m=cloudflare&action=updateProSettings" class="cf-settings-form" onsubmit="return handleSettingsUpdate(this)">
            <div class="cf-form-grid">
                <div class="cf-form-group">
                    <label>Architecture Mode</label>
                    <select name="account_type" class="cf-select-custom" onchange="toggleByot(this.value)">
                        <option value="byot" {if $accountType == 'byot'}selected{/if}>BYOT (Personal Token) - RECOMMENDED</option>
                        <option value="managed" {if $accountType == 'managed'}selected{/if}>Managed Core (Shared Risk)</option>
                        {if $isPro && $dedicatedAvailable}<option value="dedicated" {if $accountType == 'dedicated'}selected{/if}>Dedicated Sub-Account</option>{/if}
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

    <!-- Dynamic Promo for Dedicated (If supported but user is not Pro) -->
    {if !$isPro && $dedicatedAvailable}
    <div class="cf-premium-banner animate-slide-up">
        <div class="cf-premium-info">
            <div class="cf-premium-badge"><i class="fa fa-bolt"></i> GO PRO</div>
            <h2>Unlock Enterprise-Grade Controls</h2>
            <p>Isolated Dedicated Account provisioning and advanced DDoS mitigation are just one click away.</p>
        </div>
        <div class="cf-premium-cta">
            <a href="index.php?m=cloudflare&action=buyPro" class="cf-btn-premium">Upgrade Now <i class="fa fa-arrow-right"></i></a>
        </div>
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
                            <td data-label="Domain Name">
                                <div class="cf-domain-info">
                                    <span class="cf-domain-text">{$domain->domain}</span>
                                </div>
                            </td>
                            <td data-label="Network Status">
                                <span class="cf-status-tag tag-active">Active</span>
                            </td>
                            <td data-label="Infrastructure">
                                <span class="cf-infra-tag">{if $isPro && $accountType == 'dedicated'}DEDICATED{elseif $accountType == 'byot'}BYOT{else}MANAGED{/if}</span>
                            </td>
                            <td style="text-align: right;">
                                <div class="cf-domain-actions">
                                    <button onclick="handleSync('{$domain->id}')" class="cf-btn-sync" title="Sync & Connect">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <a href="index.php?m=cloudflare&action=manage&id={$domain->id}" class="cf-btn-action-manage">
                                        Manage <i class="fa fa-chevron-right"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{literal}
<script>
function toggleByot(val) {
    const section = document.getElementById('byot-section');
    const selectElem = document.querySelector('select[name="account_type"]');
    
    if (val === 'dedicated') {
        Swal.fire({
            title: 'Setup Dedicated Account',
            html: `
                <div style="text-align: left; font-size: 14px;">
                    <p>You have selected Dedicated Account Isolation.</p>
                    <p><strong>Important:</strong> If your WHMCS email address is already registered with Cloudflare, we cannot create a new dedicated account for you automatically.</p>
                    <p style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-left: 3px solid #0051c3;">
                        If you already have a Cloudflare account, please select <strong>BYOT (Personal Token)</strong> instead. You will need to generate an API token from your Cloudflare profile and paste it here.
                    </p>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0051c3',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Proceed with Dedicated',
            cancelButtonText: 'Switch to BYOT'
        }).then((result) => {
            if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                // User clicked switch to BYOT
                selectElem.value = 'byot';
                section.style.display = 'grid';
            } else if (!result.isConfirmed) {
                // User closed popup
                selectElem.value = 'managed';
                section.style.display = 'none';
            } else {
                // Proceed with dedicated
                section.style.display = 'none';
            }
        });
    } else {
        section.style.display = (val === 'byot' ? 'grid' : 'none');
    }
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

function handleSync(domainId) {
    Swal.fire({
        title: 'Sync & Connect Domain',
        html: `
            <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                <p>Are you sure you want to synchronize this domain? The system will automatically perform the following actions:</p>
                <ul style="margin-top: 10px; margin-bottom: 15px; padding-left: 20px;">
                    <li><strong>Initialize Zone:</strong> Provision the domain on Cloudflare if not active.</li>
                    <li><strong>DNS Records:</strong> Scan and apply any missing default DNS templates.</li>
                    <li><strong>Nameservers:</strong> Automatically update the domain's nameservers at your registrar.</li>
                </ul>
                <p style="color: #ca8a04;"><strong>Note:</strong> You can optionally provide a custom IP below. If left blank, we will auto-detect your server IP.</p>
            </div>
        `,
        input: 'text',
        inputPlaceholder: 'Optional: Custom Server IP',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Proceed with Sync',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Synchronizing...',
                text: 'Updating nameservers and DNS templates. Please wait...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('op', 'sync');
            formData.append('id', domainId);
            if (result.value) {
                formData.append('custom_ip', result.value);
            }

            fetch('index.php?m=cloudflare', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sync Successful',
                        text: data.message,
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Sync Failed',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'An unexpected error occurred or the API is unreachable.'
                });
            });
        }
    });
}

function showByotGuide() {
    Swal.fire({
        title: 'BYOT Setup Guide',
        html: `
            <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                <p><strong>Step 1:</strong> Create a free account at <a href="https://dash.cloudflare.com/sign-up" target="_blank">Cloudflare.com</a>.</p>
                <p><strong>Step 2:</strong> Go to <strong>My Profile > API Tokens</strong>.</p>
                <p><strong>Step 3:</strong> Click <strong>Create Token</strong> and use the <strong>Edit Zone DNS</strong> template.</p>
                <p><strong>Step 4:</strong> Ensure the token has the following permissions:
                    <ul style="margin-top: 5px;">
                        <li>Account - Account Settings: Read</li>
                        <li>Zone - DNS: Edit</li>
                        <li>Zone - Zone: Edit</li>
                        <li>Zone - Cache Purge: Edit</li>
                    </ul>
                </p>
                <p><strong>Step 5:</strong> Copy the token and paste it into the API Token field below.</p>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#3b82f6',
        confirmButtonText: 'Got it!'
    });
}
</script>
{/literal}

{literal}
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

/* Architecture Guide */
.cf-architecture-guide { margin-bottom: 40px; }
.cf-guide-header { margin-bottom: 25px; text-align: center; }
.cf-guide-header h3 { margin: 0 0 5px; font-size: 22px; font-weight: 800; color: var(--cf-dark); }
.cf-guide-header p { margin: 0; color: var(--cf-text-gray); font-size: 15px; }
.cf-guide-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.cf-guide-card { background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: var(--cf-card-shadow); display: flex; flex-direction: column; transition: transform 0.3s ease; }
.cf-guide-card:hover { transform: translateY(-5px); }
.cf-guide-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 20px; }
.cf-guide-card h4 { margin: 0 0 10px; font-size: 18px; font-weight: 800; color: var(--cf-dark); }
.cf-guide-tags { margin-bottom: 15px; }
.cf-tag { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
.cf-guide-card p { margin: 0 0 20px; color: var(--cf-text-gray); font-size: 14px; line-height: 1.6; flex-grow: 1; }
.cf-guide-list { list-style: none; padding: 0; margin: 0; border-top: 1px solid #f1f5f9; padding-top: 20px; }
.cf-guide-list li { display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: var(--cf-dark); margin-bottom: 10px; font-weight: 500; }
.cf-guide-list li:last-child { margin-bottom: 0; }

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
.cf-select-custom { 
    width: 100%; padding: 12px 35px 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: var(--cf-light-gray); 
    appearance: none; -webkit-appearance: none; -moz-appearance: none;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat; background-position: right 15px center; background-size: 10px auto; cursor: pointer; color: var(--cf-dark);
}
.cf-input-custom { width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: var(--cf-light-gray); color: var(--cf-dark); }
.cf-select-custom:focus, .cf-input-custom:focus { outline: none; border-color: var(--cf-primary); box-shadow: 0 0 0 3px rgba(243, 128, 32, 0.1); }
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

.cf-btn-action-manage:hover { background: var(--cf-light-gray); border-color: var(--cf-primary); color: var(--cf-primary); }

/* Additional Styles */
.cf-notice-bar { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 12px 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; font-size: 14px; color: #92400e; }
.cf-notice-bar i { font-size: 18px; }
.cf-header-right { display: flex; align-items: center; gap: 10px; }
.cf-btn-info { background: #fff; border: 1px solid #e2e8f0; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.cf-btn-info:hover { border-color: var(--cf-primary); color: var(--cf-primary); }
.cf-domain-actions { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
.cf-btn-sync { background: #fff; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 8px; color: var(--cf-text-gray); cursor: pointer; transition: 0.2s; }
.cf-btn-sync:hover { border-color: #3b82f6; color: #3b82f6; }

/* Animations */
.animate-slide-up { animation: slideUp 0.6s cubic-bezier(0.165, 0.84, 0.44, 1); }
@keyframes slideUp {
    0% { transform: translateY(30px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}

/* Recommended Badge */
.cf-card-recommended { position: relative; border-color: #3b82f6 !important; box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.2) !important; }
.cf-recommended-badge { position: absolute; top: -12px; right: 20px; background: #3b82f6; color: #fff; font-size: 10px; font-weight: 800; padding: 4px 12px; border-radius: 20px; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }

@media (max-width: 992px) {
    .cf-search-box { width: 100%; }
    .cf-stats-container { width: 100%; overflow-x: auto; padding-bottom: 10px; }
    .cf-stat-box { flex-shrink: 0; }
    .cf-guide-grid { grid-template-columns: 1fr; }
    
    .cf-table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 12px; }
    .cf-dashboard-table { min-width: 700px; }
    .cf-dashboard-table td, .cf-dashboard-table th { white-space: nowrap; }
}
</style>
{/literal}
