# Cloudflare WHMCS Module Project Reference

## 1. Project Overview & Optimized Prompt
**Optimized Prompt:** "Develop a comprehensive WHMCS provisioning module for Cloudflare supporting three account modes: BYOT, Managed, and Dedicated. Support configurable fees and an integrated premium UI."

## 2. Centralized Documentation Rule
As per user preferences, all planning, tasks, and walkthroughs are merged into:
[stitch_project_tracker.md](file:///c:/AI%20Project/cloudflare%20whmcs%20moduel/project%20ref/stitch_project_tracker.md)

## 3. Current Strategic Debate
We are debating the shift from a **Product-based module** to an **Addon-based module**.

### Why the Pivot?
The user suggests an **Addon approach** like a 'DNS Manager' integrated into the core. This allows:
- **Upselling during checkout**: Shows up like "WHOIS Protection" on the domain checkout page.
- **Native Experience**: Feels like a feature rather than a separate product.

### Risks & Considerations
- **Technical Hooks**: Server modules are more powerful in WHMCS for automated provisioning. We need to verify if "Dedicated" sub-account creation is as robust in the Addon framework.
- **Billing**: Addons typically sync their billing to the parent domain.

---
*Last updated: 2026-04-21*
