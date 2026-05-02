<!-- Load External Assets -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="cf-container">
    <div class="cf-header">
        <div class="cf-title">
            <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare" style="height: 28px;">
            <span>Manager: <span class="cf-domain-name">{$domain}</span></span>
        </div>
        <div class="cf-badge-container">
            <span class="cf-badge cf-badge-managed">
                <i class="fa fa-key"></i> BYOT ACCOUNT #{$acc_id}
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
                    <div class="cf-icon-pulse"><i class="fa fa-rocket"></i></div>
                    <h3>Initialize Infrastructure</h3>
                    <p>This domain is not yet active in your personal Cloudflare account. Click below to provision the zone and apply infrastructure templates.</p>
                </div>
                <div class="cf-migration-footer" style="margin-top:30px;">
                    <button onclick="handleOp('sync')" class="cf-btn-migrate-action">
                        <i class="fa fa-rocket"></i> Initialize & Sync
                    </button>
                </div>
            </div>
        </div>
    {else}
        <div class="cf-grid">
            <div class="cf-card-main">
                <div class="cf-card-header">
                    <h4><i class="fa fa-list"></i> DNS Records</h4>
                    <div class="cf-header-actions">
                        <button class="cf-btn-sync-small" onclick="handleSyncTemplates()" title="Sync DNS"><i class="fa fa-sync"></i> Sync DNS</button>
                        <button class="cf-btn-refresh" onclick="window.location.reload()"><i class="fa fa-sync"></i></button>
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
                                            <input type="hidden" name="type" value="{$record.type}">
                                            <div class="cf-edit-container">
                                                <div class="cf-edit-fields">
                                                    <input type="text" name="name" value="{$record.name}" class="cf-input-inline" placeholder="Name">
                                                    <input type="text" name="content" value="{$record.content}" class="cf-input-inline" placeholder="Content">
                                                    <label class="cf-proxy-toggle">
                                                        <input type="checkbox" name="proxied" {if $record.proxied}checked{/if}>
                                                        <span>Proxy</span>
                                                    </label>
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
window.onload = function() {
    if ({/literal}{$triggerSync|default:0}{literal}) {
        handleOp('sync');
    }
};

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
    formData.append('domain', '{/literal}{$domain}{literal}');
    formData.append('acc_id', '{/literal}{$acc_id}{literal}');
    
    for (const key in extraData) {
        formData.append(key, extraData[key]);
    }

    fetch('index.php?m=cloudflare', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Action Completed',
                text: data.message || 'Success!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'index.php?m=cloudflare&action=manage&domain={/literal}{$domain}{literal}&acc={/literal}{$acc_id}{literal}';
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    })
    .catch(error => {
        Swal.fire({ icon: 'error', title: 'System Error', text: 'API unreachable.' });
    });
}

function handleDelete(recordId) {
    Swal.fire({
        title: 'Delete Record?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
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
    form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        if (cb.checked) data[cb.name] = '1';
        else delete data[cb.name];
    });
    handleOp(data.op, data);
    return false;
}

function handleSyncTemplates() {
    Swal.fire({
        title: 'Sync Infrastructure Templates?',
        text: 'This will ensure all default records exist for this domain. Leave IP blank for auto-detect.',
        input: 'text',
        inputPlaceholder: 'Optional: Custom Server IP',
        icon: 'info',
        showCancelButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            handleOp('sync', { custom_ip: result.value });
        }
    });
}
</script>

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

.cf-btn-cache, .cf-btn-sync-small { background: #fff; border: 1px solid var(--cf-border); padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; }
.cf-btn-cache:hover, .cf-btn-sync-small:hover { background: #f1f5f9; border-color: var(--cf-orange); color: var(--cf-orange); }
.cf-btn-sync-small { color: var(--cf-blue); margin-right: 8px; }

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

.cf-edit-container { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
.cf-edit-fields { display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
.cf-input-inline { flex: 1; min-width: 150px; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; }
.cf-proxy-toggle { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--cf-gray); }
.cf-edit-btns { display: flex; gap: 10px; }
.cf-btn-save-inline { background: var(--cf-blue); color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.cf-btn-cancel-inline { background: #e2e8f0; color: var(--cf-dark); border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }

/* NS Alert */
.cf-ns-alert { background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid var(--cf-blue); border-radius: 12px; padding: 15px 25px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; }
.cf-ns-icon { font-size: 24px; color: var(--cf-blue); }
.cf-ns-content p { margin: 5px 0 0; color: var(--cf-gray); font-size: 14px; }
.cf-code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: var(--cf-dark); font-weight: 600; }

/* Migration UI */
.cf-migration-overlay { padding: 40px 0; }
.cf-migration-card { max-width: 550px; margin: 0 auto; background: #fff; border: 1px solid var(--cf-border); border-radius: 20px; padding: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); text-align: center; }
.cf-icon-pulse { width: 64px; height: 64px; background: #fff7ed; color: var(--cf-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px; }
.cf-migration-header h3 { font-size: 24px; font-weight: 800; margin-bottom: 10px; }
.cf-migration-header p { color: var(--cf-gray); font-size: 15px; }
.cf-btn-migrate-action { background: var(--cf-orange); color: #fff; border: none; padding: 14px 30px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.2s; }

/* Sidebar */
.cf-card-side { background: #fff; border: 1px solid var(--cf-border); border-radius: 16px; margin-bottom: 24px; overflow: hidden; }
.cf-form-side { padding: 20px; }
.cf-input-side, .cf-select-side { width: 100%; padding: 12px; border: 1px solid var(--cf-border); border-radius: 8px; margin-bottom: 12px; font-size: 14px; }
.cf-btn-add-side { width: 100%; background: var(--cf-dark); color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; }

.cf-toggles-list { padding: 10px 20px 20px; }
.cf-toggle-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
.cf-toggle-label strong { display: block; font-size: 14px; }
.cf-toggle-label span { font-size: 11px; color: var(--cf-gray); }
.cf-toggle-btn { width: 44px; height: 24px; background: #e2e8f0; border-radius: 20px; border: none; position: relative; cursor: pointer; transition: 0.3s; }
.cf-toggle-btn.active { background: var(--cf-green); }
.cf-toggle-knob { width: 18px; height: 18px; background: #fff; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: 0.3s; }
.cf-toggle-btn.active .cf-toggle-knob { left: 23px; }

.animate-pop { animation: pop 0.4s ease-out; }
@keyframes pop { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
</style>
{/literal}
