# Cloudflare Enterprise Management Module for WHMCS (v2.0)

A high-performance, fully integrated Cloudflare provisioning and management module for WHMCS. This module allows you to seamlessly offer Managed Cloudflare protection to your clients, while offering premium "Pro" features like Dedicated Sub-Accounts and BYOT (Bring Your Own Token).

## Features

- **Three Architecture Modes:**
  - **BYOT (Bring Your Own Token):** Clients can bring their own personal Cloudflare API tokens (Recommended Free Tier).
  - **Managed Core:** Domains are proxied through your master account (Free Tier - Shared Risk).
  - **Dedicated Sub-Account:** Provisions an isolated Cloudflare account using the client's email via Tenant API (Pro Tier - Automatically hidden if admin lacks privileges).
- **Asynchronous AJAX Dashboard:** A beautiful, responsive, SweetAlert2-powered dashboard for DNS management, Cache Purging, and Security Mode toggling. All actions execute instantly without page reloads.
- **Intelligent Sync & DNS Reset:** A global "Sync DNS" button automatically initializes zones, detects conflicts, applies DNS templates (using either the client's hosting IP or the admin-defined **Default Parking IP**), and updates WHMCS nameservers in one click. Zero-downtime automated migration when switching from Managed to BYOT.
- **Zero-Touch Provisioning:** Includes a WHMCS `DomainAdd` hook that automatically initializes Cloudflare protection the moment a domain is registered.

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
4. **Default Parking IP:** Enter the IP address (e.g. your main server or a "Coming Soon" page) to be used when a client syncs a domain that has no hosting account.
5. **Save Configuration:** Click "Save Settings". The module will automatically verify your credentials.

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
