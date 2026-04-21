<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="cf-center-container">
    <div class="cf-center-header">
        <div class="cf-center-title">
            <img src="https://www.cloudflare.com/img/logo-cloudflare-dark.svg" alt="Cloudflare" style="height: 28px;">
            <span>Cloudflare Center</span>
        </div>
        <div class="cf-center-stats">
            <div class="cf-stat-item">
                <span class="cf-stat-value">{$domains|count}</span>
                <span class="cf-stat-label">Total Domains</span>
            </div>
        </div>
    </div>

    <div class="cf-center-card">
        <div class="cf-center-card-header">
            <h4><i class="fa fa-globe"></i> Your Protected Domains</h4>
            <div class="cf-center-search">
                <input type="text" id="domainSearch" placeholder="Filter domains..." onkeyup="filterDomains()">
            </div>
        </div>
        <div class="cf-center-table-wrapper">
            <table class="cf-center-table" id="domainsTable">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Registration Date</th>
                        <th>Next Due Date</th>
                        <th>Status</th>
                        <th style="width: 150px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$domains item=d}
                        <tr>
                            <td class="cf-domain-name">
                                <strong>{$d->domain}</strong>
                            </td>
                            <td>{$d->registrationdate}</td>
                            <td>{$d->nextduedate}</td>
                            <td><span class="cf-label-status cf-status-{$d->status|strtolower}">{$d->status}</span></td>
                            <td>
                                <a href="index.php?m=cloudflare&action=manage&id={$d->id}" class="cf-btn-manage">
                                    <i class="fa fa-cog"></i> Manage
                                </a>
                            </td>
                        </tr>
                    {foreachelse}
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 40px;">
                                <i class="fa fa-info-circle fa-2x" style="color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                No active domains found in your account.
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterDomains() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("domainSearch");
    filter = input.value.toUpperCase();
    table = document.getElementById("domainsTable");
    tr = table.getElementsByTagName("tr");
    for (i = 1; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[0];
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

<style>
.cf-center-container {
    font-family: 'Inter', sans-serif;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    padding: 30px;
    margin-bottom: 30px;
}
.cf-center-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
}
.cf-center-title {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}
.cf-stat-item {
    text-align: right;
}
.cf-stat-value {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #f38020;
}
.cf-stat-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.cf-center-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}
.cf-center-card-header {
    padding: 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.cf-center-card-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}
.cf-center-search input {
    padding: 8px 15px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    width: 250px;
}
.cf-center-table {
    width: 100%;
    border-collapse: collapse;
}
.cf-center-table th {
    text-align: left;
    padding: 15px 20px;
    font-size: 13px;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.cf-center-table td {
    padding: 18px 20px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}
.cf-domain-name strong {
    color: #0f172a;
    font-size: 15px;
}
.cf-label-status {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}
.cf-status-active { background: #ecfdf5; color: #059669; }
.cf-btn-manage {
    background: #0051c3;
    color: white;
    padding: 6px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
}
.cf-btn-manage:hover {
    background: #003d94;
    color: white;
}
</style>
