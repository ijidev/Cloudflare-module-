<?php
/**
 * Cloudflare WHMCS Core Integration Hooks
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\View\Menu\Item as MenuItem;

/**
 * Add Cloudflare Center to Primary Navbar
 */
add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar) {
    if (!is_null($primaryNavbar->getChild('Services'))) {
        $primaryNavbar->getChild('Services')->addChild('Cloudflare Center', [
            'label' => 'Cloudflare Center',
            'uri' => 'index.php?m=cloudflare&action=center',
            'order' => 100,
        ]);
    }
});

/**
 * Inject Cloudflare into Domain Details Page
 */
add_hook('ClientAreaPageDomains', 1, function ($vars) {
    // Only proceed if viewing a specific domain
    if ($vars['action'] != 'domaindetails' || empty($vars['domainid'])) {
        return;
    }

    $domainId = $vars['domainid'];
    $domainName = $vars['domain'];
    $clientId = $_SESSION['uid'];

    // 1. Check if "Pro Addon" is active for this client
    $proAddonId = Capsule::table('mod_cloudflare_settings')->where('setting', 'pro_addon_id')->value('value');
    $isPro = false;
    
    if ($proAddonId > 0) {
        $isPro = Capsule::table('tblhostingaddon')
            ->join('tblhosting', 'tblhosting.id', '=', 'tblhostingaddon.hostingid')
            ->where('tblhosting.userid', $clientId)
            ->where('tblhostingaddon.addonid', $proAddonId)
            ->where('tblhostingaddon.status', 'Active')
            ->exists();
    }

    // 2. Prepare Variables for Template
    return [
        'cf_is_pro' => $isPro,
        'cf_domain_id' => $domainId,
        'cf_management_url' => 'index.php?m=cloudflare&action=manage&id=' . $domainId,
    ];
});

/**
 * Inject Button into Domain Details Sidebar or Main Body
 * Depending on the active theme, we might need different hooks.
 * For "Six" theme (Standard), we can use ClientAreaSecondarySidebar.
 */
add_hook('ClientAreaSecondarySidebar', 1, function (MenuItem $secondarySidebar) {
    $action = $_REQUEST['action'] ?? '';
    if ($action != 'domaindetails') return;

    if (!is_null($secondarySidebar->getChild('Domain Details Management'))) {
        $secondarySidebar->getChild('Domain Details Management')->addChild('Cloudflare Manager', [
            'label' => 'Cloudflare Manager',
            'uri' => 'index.php?m=cloudflare&action=manage&id=' . ($_REQUEST['id'] ?? ''),
            'order' => 50,
            'icon' => 'fa-cloud',
        ]);
    }
});
