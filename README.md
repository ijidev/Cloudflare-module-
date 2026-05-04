# Cloudflare Premium DNS Module for WHMCS (v2.2)

A high-fidelity, infrastructure-first Cloudflare management module designed exclusively for WHMCS. This module transforms the standard Cloudflare integration into a premium, white-labeled "Premium DNS" service, allowing hosting providers to seamlessly map infrastructure clusters to WHMCS products and automate DNS propagation.

## Core Architecture

This module abandons the flawed "Global Master Account" approach. Instead, it relies on strict **Infrastructure-Based Management** and **BYOT (Bring Your Own Token)**:

1. **Infrastructure Clusters:** Administrators define physical servers or clusters, attaching them to specific WHMCS products.
2. **Dynamic DNS Templates:** Each cluster has its own set of DNS templates (A records, CNAMEs, MX) using dynamic variables (`{domain}`, `{ip}`).
3. **Client-Side BYOT:** Clients connect their own Cloudflare accounts via API Tokens or Global Keys.
4. **Automated Sync:** Once connected, the module securely pushes the cluster's DNS templates to the client's Cloudflare account.

## Features

- **Admin Toggle for Global Domain Sync:** Allow clients to manage *all* domains found in their Cloudflare account, or restrict management only to domains registered within your WHMCS instance.
- **Glassmorphic Mobile-Optimized UI:** A highly polished, responsive client area utilizing modern CSS techniques to provide a premium user experience on all devices.
- **Full DNS Record Management:** Clients can add, delete, and view their DNS records (A, AAAA, CNAME, TXT, MX) directly from the WHMCS client area without logging into Cloudflare.
- **Edge Security Controls:** Toggle SSL/TLS modes and purge cache instantly via AJAX endpoints.
- **Automated Access Control:** Clients can only manage infrastructure if they have an active WHMCS service mapped to an eligible Cloudflare Infrastructure Cluster.

## Installation & Setup

1. **Upload:** Place the module folder in `/modules/addons/cloudflare/`
2. **Activate:** Go to **System Settings -> Addon Modules** and activate "Cloudflare Manager".
3. **Permissions:** Grant Full Administrator access.
4. **Configure:** Check the "Fetch All Cloudflare Domains" toggle if you want the system to act as a universal domain manager.

## Admin Configuration (Clusters)

Navigate to **Addons -> Cloudflare Manager**.
1. Create a new Infrastructure Cluster (select a WHMCS server or enter a manual IP).
2. Click "Manage" on the cluster.
3. Define your DNS Templates (e.g., Type A, Name `@`, Content `{ip}`).
4. Go to the "Linked Products" tab and select which WHMCS products grant access to this cluster.

## Client Experience

Clients will see a modern "Premium DNS" interface. They must first connect their Cloudflare account using an API token. Once connected, they can view their active assets, force-sync new domains to the infrastructure, and seamlessly manage DNS records.
