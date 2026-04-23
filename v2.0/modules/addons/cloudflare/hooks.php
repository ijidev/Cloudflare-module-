<?php
/**
 * Cloudflare Module Hooks
 */

use WHMCS\Database\Capsule;

add_hook('InvoicePaid', 1, function($vars) {
    $invoiceId = $vars['invoiceid'];
    
    $items = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->get();
        
    $hasPro = false;
    foreach ($items as $item) {
        if (stripos($item->description, 'Cloudflare Pro') !== false) {
            $hasPro = true;
            break;
        }
    }
    
    if ($hasPro) {
        $userId = Capsule::table('tblinvoices')->where('id', $invoiceId)->value('userid');
        if ($userId) {
            Capsule::table('mod_cloudflare_client_status')->updateOrInsert(
                ['client_id' => $userId],
                ['is_pro' => 1]
            );
        }
    }
});

/**
 * Inject Cloudflare link into Domain Management Sidebar
 */
add_hook('ClientAreaSecondarySidebar', 1, function($secondarySidebar) {
    $filename = basename($_SERVER['PHP_SELF']);
    $action = $_GET['action'];

    if ($filename == 'clientarea.php' && $action == 'domaindetails') {
        $domainId = $_GET['id'];
        $domain = Capsule::table('tbldomains')->where('id', $domainId)->value('domain');

        if ($domain) {
            $secondarySidebar->addChild('Cloudflare Management', [
                'label' => 'Cloudflare Manager',
                'icon' => 'fa-cloud',
                'order' => 10,
            ]);

            $secondarySidebar->getChild('Cloudflare Management')
                ->addChild('Manage Domain', [
                    'label' => 'Manage DNS & Security',
                    'uri' => 'index.php?m=cloudflare&action=manage&id=' . $domainId,
                    'order' => 1,
                    'icon' => 'fa-cog',
                ]);
        }
    }
});

/**
 * Add Cloudflare Manager to the primary navigation menu
 */
add_hook('ClientAreaPrimaryNavbar', 1, function($primaryNavbar) {
    if (!WHMCS\Session::get('uid')) return;

    $servicesMenu = $primaryNavbar->getChild('Services');
    if ($servicesMenu) {
        $servicesMenu->addChild('Cloudflare Manager', [
            'label' => 'Cloudflare Manager',
            'uri' => 'index.php?m=cloudflare',
            'order' => 10,
            'icon' => 'fa-cloud',
        ]);
    }
});

