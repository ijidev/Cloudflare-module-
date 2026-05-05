# Cloudflare Premium DNS Infrastructure for WHMCS
### The Ultimate Self-Healing DNS Management Solution

Transform your WHMCS platform into a high-performance, white-labeled DNS hosting powerhouse. This module bridges the gap between Cloudflare's world-class performance and your server infrastructure, offering automated "Self-Healing" capabilities that ensure your clients' DNS is always perfectly synchronized with their hosting.

---

## 🚀 Key Features

### 1. Infrastructure-First "Self-Healing" Sync
Our proprietary sync engine doesn't just wait for users to add domains. It proactively crawls your WHMCS active services and cross-references them with Cloudflare zones.
- **Auto-Discovery**: Identifies domains pointing to your cluster IPs (A records).
- **Auto-Linking**: Automatically maps discovered domains to the correct WHMCS product.
- **Auto-Sync**: Applies cluster-specific DNS templates (A, CNAME, MX, TXT) instantly.

### 2. Intelligent IP Migration (Zero-Downtime Updates)
Changing server IPs? No problem. The module tracks historical Cluster IPs.
- **IP Retention**: Stores a history of old IPs.
- **Seamless Migration**: When a sync is triggered, it identifies domains pointing to *old* IPs and automatically migrates them to the *new* IP in Cloudflare.
- **Audit Logs**: Both Admin and Client see exactly when and how their records were updated.

### 3. Premium Client Dashboard
A stunning, glassmorphic management interface designed for the modern web.
- **Mobile-First Design**: Fully responsive UI that feels like a native app.
- **Full DNS Control**: Clients can manage A, CNAME, MX, and TXT records with custom TTLs.
- **Edge Security**: One-click toggles for "Always Use HTTPS" and "Automatic HTTPS Rewrites".
- **Quick Actions**: Purge Edge Cache and Pause Cloudflare without leaving WHMCS.

### 4. Enterprise-Grade Security
- **WHM Verification**: Optionally verify that a domain is actually an "Addon Domain" on the cPanel/WHM server before allowing management.
- **Ownership Lockdown**: Multi-account BYOT (Bring Your Own Token) support ensures clients only manage what they own.
- **Account Verification**: Every AJAX call is gated by ownership checks against the WHMCS session.

### 5. Detailed Audit Logs
Transparency for you and your clients.
- **Admin Logs**: Track global infrastructure changes and automated repairs.
- **Client History**: Clients can view a timeline of DNS changes, including old vs. new values.

---

## 🛠 Technical Architecture

- **Backend**: PHP 7.4+ with Laravel-style Capsule DB integration.
- **Frontend**: Modern JS (jQuery/Swal2) with glassmorphic CSS.
- **API**: Full Cloudflare v4 API implementation.
- **Integration**: Plugs directly into WHMCS Addon and Hook systems.

---

## 📦 Installation & Setup

1.  **Upload**: Move the `modules/addons/cloudflare` folder to your WHMCS installation.
2.  **Activate**: Navigate to `Setup -> Addon Modules` and activate "Cloudflare Manager".
3.  **Configure**: Set your Master API Token and Account ID in the General Settings.
4.  **Build Clusters**: Create your Infrastructure Clusters and define your DNS Templates.
5.  **Sync**: Run the "Global Sync Hub" to watch your infrastructure map itself!

---

## 📜 Roadmap
- [x] v2.0: Self-Healing & IP Migration
- [x] v2.1: WHM Addon Verification
- [ ] v2.3: Support for Firewall Rules
- [ ] v2.5: Multi-CDN Integration

---
*Developed with ❤️ for premium hosting providers.*
