<!-- Load External Assets -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="cf-container">
    <div class="cf-header">
        <div class="cf-title">
            <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare" style="height: 28px;">
            <span>Manager: <span class="cf-domain-name">{$domain}</span></span>
        </div>
        <div class="cf-badge-container">
            <span class="cf-badge {if $isPro}cf-badge-pro{else}cf-badge-managed{/if}">
                {if $isPro}<i class="fa fa-star"></i> PRO TIER{else}<i class="fa fa-shield"></i> CORE MANAGED{/if}
            </span>
            <button onclick="handleOp('purgeCache')" class="cf-btn-cache" title="Purge Everything">
                <i class="fa fa-bolt"></i> Purge Cache
            </button>
        </div>
    </div>
    
    <!-- Nameserver Information -->
    {if $nameservers}
    <div class="cf-ns-alert animate-pop">
        <div class="cf-ns-icon"><i class="fa fa-info-circle"></i></div>
        <div class="cf-ns-content">
            <strong>Update your Nameservers</strong>
            <p>Ensure your domain is pointed to: <code class="cf-code">{$nameservers[0]}</code> and <code class="cf-code">{$nameservers[1]}</code></p>
        </div>
    </div>
    {/if}

    {if $needsMigration}
        <div class="cf-migration-overlay">
            <div class="cf-migration-card animate-pop">
                <div class="cf-migration-header">
                    <div class="cf-icon-pulse"><i class="fa fa-exchange"></i></div>
                    <h3>Migration Required</h3>
                    <p>This domain is currently managed externally. Follow the steps below to migrate it to your premium dashboard.</p>
                </div>
                <div class="cf-migration-body">
                    <div class="cf-step">
                        <div class="cf-step-num">1</div>
                        <div class="cf-step-text">
                            <strong>Remove from External Cloudflare</strong>
                            <p>Log in to your current account and remove <strong>{$domain}</strong>. This frees the domain for our infrastructure.</p>
                        </div>
                    </div>
                    <div class="cf-step">
                        <div class="cf-step-num">2</div>
                        <div class="cf-step-text">
                            <strong>Wait 2-3 Minutes</strong>
                            <p>Wait for Cloudflare to update its global edge records. This ensures a seamless transition.</p>
                        </div>
                    </div>
                    <div class="cf-step">
                        <div class="cf-step-num">3</div>
                        <div class="cf-step-text">
                            <strong>Initialize Managed Setup</strong>
                            <p>Click below to provision your zone on our high-performance infrastructure.</p>
                        </div>
                    </div>
                </div>
                <div class="cf-migration-footer">
                    <button onclick="handleOp('migrate')" class="cf-btn-migrate-action">
                        <i class="fa fa-rocket"></i> Begin Migration
                    </button>
                    {if !$isPro}
                        <div class="cf-pro-upsell">
                            <i class="fa fa-info-circle"></i> Don't want to migrate? <a href="index.php?m=cloudflare&action=buyPro">Upgrade to Pro</a> for BYOT support.
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    {else}
        <div class="cf-grid">
            <div class="cf-card-main">
                <div class="cf-card-header">
                    <h4><i class="fa fa-list"></i> DNS Records</h4>
                    <div class="cf-header-actions">
                        <button class="cf-btn-refresh" onclick="window.location.reload()"><i class="fa fa-refresh"></i></button>
                    </div>
                </div>
                <div class="cf-table-wrapper">
                    <table class="cf-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Content</th>
                                <th>Proxy</th>
                                <th style="width: 100px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$dnsRecords item=record}
                                <tr id="row-{$record.id}">
                                    <td data-label="Type"><span class="cf-label-type">{$record.type}</span></td>
                                    <td data-label="Name"><span class="cf-text-bold">{$record.name}</span></td>
                                    <td data-label="Content"><span class="cf-text-muted" title="{$record.content}">{$record.content|truncate:35:"..."}</span></td>
                                    <td data-label="Proxy">
                                        <div class="cf-proxy-indicator {if $record.proxied}active{/if}" title="{if $record.proxied}Proxied{else}DNS Only{/if}">
                                            <i class="fa fa-cloud"></i>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="cf-row-actions">
                                            <button class="cf-btn-icon-edit" onclick="toggleEdit('{$record.id}')">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button class="cf-btn-icon-delete" onclick="handleDelete('{$record.id}')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="edit-row-{$record.id}" class="cf-edit-row" style="display:none;">
                                    <td colspan="5">
                                        <form method="post" onsubmit="return handleFormSubmit(this)">
                                            <input type="hidden" name="op" value="editRecord">
                                            <input type="hidden" name="record_id" value="{$record.id}">
                                            <div class="cf-edit-container">
                                                <div class="cf-edit-fields">
                                                    <input type="text" name="name" value="{$record.name}" class="cf-input-inline" placeholder="Name">
                                                    <input type="text" name="content" value="{$record.content}" class="cf-input-inline" placeholder="Content">
                                                </div>
                                                <div class="cf-edit-btns">
                                                    <button type="submit" class="cf-btn-save-inline">Update</button>
                                                    <button type="button" class="cf-btn-cancel-inline" onclick="toggleEdit('{$record.id}')">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cf-sidebar">
                <div class="cf-card-side">
                    <div class="cf-card-header">
                        <h4><i class="fa fa-plus-circle"></i> Quick Add</h4>
                    </div>
                    <form method="post" onsubmit="return handleFormSubmit(this)" class="cf-form-side">
                        <input type="hidden" name="op" value="addRecord">
                        <select name="type" class="cf-select-side">
                            <option value="A">A Record</option>
                            <option value="CNAME">CNAME Record</option>
                            <option value="MX">MX Record</option>
                            <option value="TXT">TXT Record</option>
                        </select>
                        <input type="text" name="name" class="cf-input-side" placeholder="Name (@/www)">
                        <input type="text" name="content" class="cf-input-side" placeholder="Content/Value">
                        <button type="submit" class="cf-btn-add-side">Add Record</button>
                    </form>
                </div>

                <div class="cf-card-side">
                    <div class="cf-card-header">
                        <h4><i class="fa fa-shield"></i> Security Toggles</h4>
                    </div>
                    <div class="cf-toggles-list">
                        <div class="cf-toggle-item">
                            <div class="cf-toggle-label">
                                <strong>Under Attack</strong>
                                <span>High protection</span>
                            </div>
                            <button onclick="handleOp('toggleSecurity')" class="cf-toggle-btn {if $underAttack}active{/if}">
                                <div class="cf-toggle-knob"></div>
                            </button>
                        </div>
                        <div class="cf-toggle-item">
                            <div class="cf-toggle-label">
                                <strong>Proxy Status</strong>
                                <span>Pause/Resume</span>
                            </div>
                            <button onclick="handleOp('togglePause')" class="cf-toggle-btn {if $isPaused}active{/if}">
                                <div class="cf-toggle-knob"></div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
</div>

{literal}
<script>
function toggleEdit(id) {
    const row = document.getElementById('edit-row-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

function handleOp(op, extraData = {}) {
    Swal.fire({
        title: 'Processing...',
        text: 'Communicating with Cloudflare API',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('op', op);
    formData.append('id', '{$cf_domain_id}');
    for (const key in extraData) {
        formData.append(key, extraData[key]);
    }

    fetch('index.php?m=cloudflare&action=manage&id={$cf_domain_id}', {
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
                title: 'Action Completed',
                text: data.message || 'The request was successful.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload(); // Still reload on success to update table data for now, but via AJAX
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'API Error',
                text: data.message || 'Something went wrong.'
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

function handleDelete(recordId) {
    Swal.fire({
        title: 'Delete Record?',
        text: "This action cannot be undone on Cloudflare.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            handleOp('deleteRecord', { record_id: recordId });
        }
    });
}

function handleFormSubmit(form) {
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => { data[key] = value; });
    handleOp(data.op, data);
    return false;
}

// Handle Page Refresh Button
document.querySelector('.cf-btn-refresh').onclick = function() {
    window.location.reload();
};
</script>
{/literal}

{literal}
<style>
:root {
    --cf-orange: #f38020;
    --cf-orange-dark: #d97706;
    --cf-blue: #0051c3;
    --cf-green: #058a5e;
    --cf-dark: #0f172a;
    --cf-gray: #64748b;
    --cf-border: #e2e8f0;
    --cf-bg: #f8fafc;
}

.cf-container { font-family: 'Inter', sans-serif; color: var(--cf-dark); }
.cf-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid var(--cf-border); padding-bottom: 20px; }
.cf-title { display: flex; align-items: center; gap: 15px; font-size: 22px; font-weight: 700; }
.cf-domain-name { color: var(--cf-orange); }

.cf-badge { padding: 6px 14px; border-radius: 30px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
.cf-badge-managed { background: #f1f5f9; color: #475569; }
.cf-badge-pro { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }

.cf-btn-cache { background: #fff; border: 1px solid var(--cf-border); padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; }
.cf-btn-cache:hover { background: #f1f5f9; border-color: var(--cf-orange); color: var(--cf-orange); }

.cf-grid { display: grid; grid-template-columns: 1fr 300px; gap: 30px; }
.cf-card-main { background: #fff; border: 1px solid var(--cf-border); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.cf-card-header { padding: 16px 24px; background: var(--cf-bg); border-bottom: 1px solid var(--cf-border); display: flex; justify-content: space-between; align-items: center; }
.cf-card-header h4 { margin: 0; font-size: 15px; font-weight: 700; color: #334155; }

.cf-table { width: 100%; border-collapse: collapse; }
.cf-table th { padding: 14px 24px; text-align: left; font-size: 12px; color: var(--cf-gray); font-weight: 600; text-transform: uppercase; }
.cf-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

.cf-label-type { background: #eff6ff; color: #1d4ed8; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
.cf-text-bold { font-weight: 600; color: #1e293b; }
.cf-proxy-indicator { color: #cbd5e1; font-size: 18px; }
.cf-proxy-indicator.active { color: var(--cf-orange); }

.cf-btn-icon-edit, .cf-btn-icon-delete { background: none; border: none; padding: 6px; cursor: pointer; border-radius: 6px; transition: 0.2s; }
.cf-btn-icon-edit:hover { background: #f1f5f9; color: var(--cf-blue); }
.cf-btn-icon-delete:hover { background: #fee2e2; color: #ef4444; }

/* NS Alert */
.cf-ns-alert { background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid var(--cf-blue); border-radius: 12px; padding: 15px 25px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; box-shadow: var(--cf-card-shadow); }
.cf-ns-icon { font-size: 24px; color: var(--cf-blue); }
.cf-ns-content p { margin: 5px 0 0; color: var(--cf-gray); font-size: 14px; }
.cf-code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: var(--cf-dark); font-weight: 600; }

/* Migration UI */
.cf-migration-overlay { padding: 60px 0; }
.cf-migration-card { max-width: 650px; margin: 0 auto; background: #fff; border: 1px solid var(--cf-border); border-radius: 24px; padding: 48px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); text-align: center; }
.cf-icon-pulse { width: 80px; height: 80px; background: #fff7ed; color: var(--cf-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px; animation: pulse 2s infinite; }
.cf-migration-header h3 { font-size: 28px; font-weight: 800; margin-bottom: 12px; }
.cf-migration-header p { color: var(--cf-gray); font-size: 16px; line-height: 1.6; }
.cf-migration-body { text-align: left; margin: 40px 0; }
.cf-step { display: flex; gap: 20px; margin-bottom: 24px; }
.cf-step-num { width: 32px; height: 32px; background: var(--cf-bg); color: var(--cf-gray); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
.cf-step-text strong { display: block; margin-bottom: 4px; color: var(--cf-dark); }
.cf-step-text p { margin: 0; font-size: 14px; color: var(--cf-gray); }
.cf-btn-migrate-action { background: var(--cf-orange); color: #fff; border: none; padding: 16px 40px; border-radius: 12px; font-weight: 700; font-size: 18px; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(243, 128, 32, 0.3); }
.cf-btn-migrate-action:hover { background: var(--cf-orange-dark); transform: translateY(-2px); }
.cf-pro-upsell { margin-top: 24px; font-size: 13px; color: var(--cf-gray); }

/* Sidebar */
.cf-card-side { background: #fff; border: 1px solid var(--cf-border); border-radius: 16px; margin-bottom: 24px; overflow: hidden; }
.cf-form-side { padding: 20px; }
.cf-input-side { width: 100%; padding: 12px; border: 1px solid var(--cf-border); border-radius: 8px; margin-bottom: 12px; font-size: 14px; }
.cf-select-side { 
    width: 100%; padding: 12px 35px 12px 15px; border: 1px solid var(--cf-border); border-radius: 8px; margin-bottom: 12px; font-size: 14px; 
    appearance: none; -webkit-appearance: none; -moz-appearance: none;
    background-color: #fff;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat; background-position: right 15px center; background-size: 10px auto; cursor: pointer; color: var(--cf-dark);
}
.cf-btn-add-side { width: 100%; background: var(--cf-dark); color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.cf-btn-add-side:hover { background: #1e293b; }

.cf-toggles-list { padding: 12px 20px 20px; }
.cf-toggle-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
.cf-toggle-label strong { display: block; font-size: 14px; }
.cf-toggle-label span { font-size: 11px; color: var(--cf-gray); }
.cf-toggle-btn { width: 44px; height: 24px; background: #e2e8f0; border-radius: 20px; border: none; position: relative; cursor: pointer; transition: 0.3s; }
.cf-toggle-btn.active { background: var(--cf-green); }
.cf-toggle-knob { width: 18px; height: 18px; background: #fff; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.cf-toggle-btn.active .cf-toggle-knob { left: 23px; }

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(243, 128, 32, 0.4); }
    70% { box-shadow: 0 0 0 15px rgba(243, 128, 32, 0); }
    100% { box-shadow: 0 0 0 0 rgba(243, 128, 32, 0); }
}
.animate-pop { animation: pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
@keyframes pop {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
@media (max-width: 992px) {
    .cf-header { flex-direction: column; gap: 20px; align-items: flex-start; }
    .cf-grid { grid-template-columns: 1fr; }
    .cf-table-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 12px; }
    .cf-table { min-width: 800px; }
    .cf-table td, .cf-table th { white-space: nowrap; }
    .cf-sidebar { order: -1; }
    .cf-migration-card { padding: 30px 20px; }
}
</style>
{/literal}
