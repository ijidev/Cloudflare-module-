# Cloudflare WHMCS Module Project Reference

## 1. Project Overview & Optimized Prompt
**Original Request:** "i want to build a whmcs cloudflare moduel"
**Optimized Prompt:** "Develop a comprehensive WHMCS provisioning module for Cloudflare supporting three account modes: 
- **BYOT (Bring Your Own Token)**: Clients provide their own API tokens.
- **Managed**: A central provider-managed account for all client services.
- **Dedicated**: Isolated accounts automatically provisioned for each client.

The module must support configurable fees for BYOT and Dedicated modes. v1 features include A, CNAME, MX, and TXT DNS management and automated nameserver switching. Performance-optimized, premium UI integrated with 'skitch mpc' assets."

## 2. User Preferences & Global Instructions
- **Centralized Docs:** All walkthroughs, implementation plans, and tasks tracking are merged into this `project_reference.md` file. 
- **Functionality vs Placeholder:** Implement full functionality instead of dummy text or placeholder UI; ensure all links and buttons are functional and pointed correctly.
- **Thorough Design Implementation:** Ensure all CSS properties and CDNs are accurately applied.

## 3. Implementation Plan
### Technical Architecture
- **Server Provisioning Module** (`modules/servers/cloudflare/`):
    - `cloudflare.php`: Main module file implementing `ConfigOptions`, `CreateAccount`, `Suspend`, `Unsuspend`, etc.
    - `lib/API.php`: A PHP class wrapper for the Cloudflare API v4 with cache and security settings support.
    - `templates/clientarea.tpl`: Premium, modern user interface for DNS and Security management.
- **Account Type Logic**:
    1. **Managed**: Zone is added to the admin's master Cloudflare account.
    2. **Dedicated**: A new Cloudflare account is created for the client, and the zone is added there.
    3. **BYOT**: Client provides their own Cloudflare API Token to manage their zone.

### Features Implemented
- **DNS Management**: A, CNAME, MX, TXT records with proxy toggling.
- **Security & Dev Tools**: One-click Under Attack Mode, Development Mode, and Purge Cache.
- **Automated DNS Templates**: Pre-populate zones with admin-defined records using variables ({ip}, {domain}) upon provisioning.
- **Lifecycle Automation**: Suspend/Unsuspend pauses/unpauses the zone. Automated nameserver updates on provisioning.

## 4. Task List & Timeline
- [x] Initial project setup and prompt optimization
- [x] Planning multi-tier account support (BYOT, Managed, Dedicated)
- [x] Implement Cloudflare API v4 Wrapper
- [x] Implement WHMCS Provisioning Module Logic
- [x] Implement DNS Management UI
- [x] Automated Nameserver Switching
- [x] Dedicated Account Isolation Logic
- [x] Implement Security & Dev Toggles (Under Attack, Dev Mode)
- [x] Implement lifecycle hooks (Suspend/Unsuspend)
- [x] Automated DNS Templates & Variable Support
- [x] Premium CSS/UX Polishing
- [x] Verification and Walkthrough
- [x] GitHub Repository Deployment

---
*Last updated: 2026-04-19 (Project Completed)*
