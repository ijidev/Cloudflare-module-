# Cloudflare Multi-Tier WHMCS Module - Documentation

## 1. Overview
This module allows WHMCS administrators to offer Cloudflare services to their clients with three distinct provisioning modes. It includes a premium client area interface for DNS management, security controls, and automatic nameserver switching.

## 2. Installation
1. **Download/Clone**: Download the module files from GitHub.
2. **Upload**: Upload the `modules/servers/cloudflare/` directory to your WHMCS installation at `/path/to/whmcs/modules/servers/`.
3. **Internal Structure**: Ensure the following structure exists:
   - `modules/servers/cloudflare/cloudflare.php`
   - `modules/servers/cloudflare/lib/API.php`
   - `modules/servers/cloudflare/templates/clientarea.tpl`

## 3. WHMCS Configuration
### Step 1: Create the Product
1. Go to **Setup > Products/Services > Products/Services**.
2. Create a new product (Type: **Other**).
3. Under the **Module Settings** tab, select **Cloudflare Multi-Tier** from the module dropdown.

### Step 2: Configure Module Options
- **Account Mode**: Choose one of:
  - `managed`: All client zones are added to your central Master Account.
  - `dedicated`: A new isolated Cloudflare Sub-account is created for the client.
  - `byot`: Clients must provide their own API Token.
- **Master API Token**: Your Cloudflare API Token (Global or scoped).
- **Account ID**: Your Master Cloudflare Account ID (found in your dashboard URL).
- **DNS Template**: Define records to be automatically created on provisioning.
  - *Format*: `type|name|content` (one per line).
  - *Variables*: `{ip}` (server IP), `{domain}` (client domain).
  - *Example*: `A|@|{ip}`, `CNAME|www|@`, `MX|@|mail.{domain}`.

### Step 3: Custom Fields (For BYOT Mode)
If you wish to use **Bring Your Own Token (BYOT)**:
1. Go to the **Custom Fields** tab of the product.
2. Add a field named `Cloudflare Token`.
3. Set Type to **Text Box** and check **Show on Order Form**.

## 4. Feature Guide
### Client Area Features
- **DNS Management**: Add/Delete A, CNAME, MX, and TXT records. Toggle Cloudflare Proxy (Cloud icon).
- **Security Controls**:
  - **Under Attack Mode**: Immediate high-level security for DDoS protection.
  - **Development Mode**: Bypass Cloudflare cache for 3 hours to see site updates instantly.
- **Cache Management**: "Purge Everything" button to clear all cached files.
- **Nameservers**: Automatic display of assigned Cloudflare nameservers.

### Admin Automation
- **Nameserver Switching**: Upon successful provisioning, the module automatically updates the domain's nameservers in WHMCS to match Cloudflare.
- **Suspension**: Suspending a service in WHMCS will "Pause" the zone in Cloudflare, stopping all proxy services.

## 5. Troubleshooting
- **API Error: "Invalid Token"**: Ensure your Master API Token has permissions to `Zone:Edit` and `Account:Edit`.
- **DNS records not appearing**: Check the Cloudflare dashboard to see if the zone is "Pending Nameserver Update".
- **Dedicated mode fails**: Ensure your Cloudflare account has the ability to create Sub-accounts (Standard for most accounts).

---
*Documentation Version 1.1*
