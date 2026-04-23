# Cloudflare Enterprise Management Module for WHMCS (v2.0)

A high-performance, fully integrated Cloudflare provisioning and management module for WHMCS. This module allows you to seamlessly offer Managed Cloudflare protection to your clients, while offering premium "Pro" features like Dedicated Sub-Accounts and BYOT (Bring Your Own Token).

## Features

- **Three Architecture Modes:**
  - **Managed Core:** Domains are proxied through your master account (Free/Included tier).
  - **Dedicated Sub-Account:** Provisions an isolated Cloudflare account using the client's email via Tenant API (Pro Tier).
  - **BYOT:** Clients can bring their own personal Cloudflare API tokens (Pro Tier).
- **Automated Pro Upgrades:** Instantly generates standalone upgrade invoices without needing complex WHMCS product setups.
- **Client Dashboard:** A beautiful, SweetAlert2-powered dashboard for DNS management, Cache Purging, and Security Mode toggling.
- **Intelligent Migration:** Detects if a domain is externally managed and guides the client through a step-by-step migration process.

---

## Installation Guide

1. **Upload Files:** Upload the `cloudflare` folder to your WHMCS directory: `/modules/addons/cloudflare/`
2. **Activate Module:** Go to your WHMCS Admin Area -> **System Settings** -> **Addon Modules**. Find "Cloudflare Manager" and click **Activate**.
3. **Configure Permissions:** Click **Configure** on the module and grant Access Control to "Full Administrator".
4. **Initial Setup:** Click **Save Changes**.

---

## Admin Setup & Configuration

1. **Access the Module:** Navigate to **Addons -> Cloudflare Manager** in the top WHMCS navigation menu.
2. **Configure Master API:**
   - **Master API Token:** Enter your Cloudflare Global API Key or API Token (Requires `Zone:Edit`, `DNS:Edit`, and `Account:Edit` permissions).
   - **Master Account ID:** Your Cloudflare Enterprise/Partner Account ID.
   - **Account Email:** The email address associated with your Cloudflare account.
3. **Save Configuration:** Click "Save Settings". The module will automatically verify your credentials.

*(Note: Pricing and Recurring billing settings are managed directly within this interface. The module automatically generates invoices for clients who click "Upgrade Now" in the client area based on these settings.)*

---

## Client Experience

Clients can access the Cloudflare interface in two ways:
1. **Domain Management Sidebar:** When viewing a specific domain (`clientarea.php?action=domaindetails`), a new "Cloudflare Management" link appears in the sidebar.
2. **Primary Navigation:** A centralized "Cloudflare Manager" link is available in the primary "Services" dropdown, allowing clients to manage all their active domains from one unified dashboard.

When a client selects **"Dedicated Sub-Account"**, the module automatically intercepts the request, forces the use of their registered WHMCS email, and attempts to provision the account. If the email is already in use at Cloudflare, it safely redirects them to use the BYOT option instead.

---

## Technical Support

If you encounter a blank page or API error, ensure that:
1. Your server is running PHP 8.1+
2. Your WHMCS installation has the required `tblhostingaddons` table intact.
3. Your Cloudflare API Token has sufficient privileges.
