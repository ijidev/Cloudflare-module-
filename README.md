# Cloudflare Multi-Tier WHMCS Module

A premium, industrial-themed Cloudflare management module for WHMCS supporting tiered access and advanced integration.

## 📁 Repository Structure

### 🔹 [v2.0 Core Integration (Current)](v2.0/)
The primary version of the module. This is an **Addon Module** that integrates directly into the core domain management flow.
- **Core System Integration**: Ties directly into WHMCS domains, no longer a standalone product.
- **Domain Sync Tool**: Admin can bulk-select which domains are managed via Cloudflare.
- **Client-Level Pro Tier**: Tier status is tied to the Client Account. One "Pro" subscription secures all domains.
- **Flexible Account Modes**: Supports **Managed** (Admin Account), **Dedicated** (Isolated), and **BYOT** (Client Token).
- **Migration Guide**: Professional UI walkthrough for migrating domains from existing Cloudflare accounts.
- **Full DNS Control**: Add, Edit, and Delete DNS records with instant sync.
- **Automated Nameservers**: Provisioned domains automatically switch to Cloudflare edge nameservers.
- [Read v2.0 Documentation](v2.0/v2.0_DOCUMENTATION.md)

### 🔹 [v1.0 Standalone Server Module (Legacy)](v1.0/)
The original version of the module. This is a **Server Module** (Product-based) that handles Cloudflare as a separate standalone product/service instead of an addon. 
- Allows providing Cloudflare through the WHMCS server module architecture.
- [Read v1.0 Documentation](v1.0/v1.0_DOCUMENTATION.md)

## 🔧 Features Overview (Both Versions)

- **DNS Template System**: Define custom DNS records to be automatically created on provisioning.
- **Automated Nameserver Switching**: Seamlessly update WHMCS nameservers.
- **Security Controls**: Direct control over "Under Attack Mode" and Development Mode from WHMCS.
- **Cache Management**: One-click purge cache capabilities.

## ⚙️ Configuration & Documentation

Please refer to the documentation inside the specific version folder you wish to install.
For most modern WHMCS setups, **v2.0** is recommended for native integration with domain management.

---
*Last updated: 2026-04-23*
