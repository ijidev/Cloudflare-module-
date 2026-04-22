# Project Reference & Timeline

## Preferences & Instructions
- **Design System**: Use the `stitch` MCP server for image and source code references when working on design. Use `code.html` as the code source and image as the visual reference.
- **CSS & CDNs**: Always scan and verify all CSS properties and CDNs are copied. Manually create them if they aren't working.
- **Functionality over Placeholders**: Implement actual functionality. Avoid dummy placeholders, test data, or broken links. All links must be functional and point to the correct destination.
- **Prompt Optimization**: Constantly redefine and optimize prompts for the best possible results before proceeding with any task.
- **Design Iteration**: Apply "stitch loop skills" for all design-related prompts, ensuring iterative refinement and deep integration with the stitch MCP server's visual assets and source code.
- **File Management**: This centralized `project_refrence.md` file serves to merge all markdown tracking files (walkthrough, implementation plans, task) to track project progress. All `.md` files should ideally be stored in this folder (`project ref`).

## Project Timeline & Task List

### Recent Updates (April 2026)
- **Cloudflare WHMCS Module v2.0 Bug Fixes**
  - **Issue**: Authentication error at line 67 in `lib/API.php`.
  - **Resolution**: 
    - Added `trim()` wrapper to `$this->apiToken` and `$this->email` inside the `API.php` request headers to ensure trailing spaces (often caused by copy/pasting keys from Cloudflare) do not cause 401/403 Authentication Errors.
    - Improved the Cloudflare API exception error message specifically for authentication errors (HTTP 401, 403, and API error codes 10000, 9109) to advise admins about correctly using the Email field when choosing between a Bearer API Token vs. a Global API Key.

### To Do List
- [x] Fix Cloudflare Authentication Error on Line 67 in `lib/API.php`
- [ ] Monitor logs for any new Cloudflare authentication issues or edge cases with BYOT (Bring Your Own Token).
- [ ] Review UI/UX using Stitch MCP server for any new client area screens.

