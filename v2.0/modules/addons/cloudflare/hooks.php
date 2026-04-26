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

/**
 * Automatic Cloudflare Provisioning on Domain Add
 */
add_hook('DomainAdd', 1, function($vars) {
    $domainId = $vars['domainid'];
    $domain = $vars['domain'];
    
    $dbSettings = Capsule::table('mod_cloudflare_settings')->pluck('value', 'setting');
    if (empty($dbSettings['master_api_token'])) return;
    
    require_once __DIR__ . '/lib/API.php';
    $api = new \WHMCS\Module\Addon\Cloudflare\API($dbSettings['master_api_token'], $dbSettings['master_email']);
    
    try {
        $response = $api->createZone($domain, $dbSettings['master_account_id']);
        $zoneId = $response['result']['id'];
        $ns = $response['result']['name_servers'] ?? [];
        
        // Apply DNS Templates
        $templates = Capsule::table('mod_cloudflare_templates')->get();
        foreach ($templates as $t) {
            $content = str_replace(['{domain}', '{ip}'], [$domain, $_SERVER['SERVER_ADDR']], $t->content);
            $api->addDNSRecord($zoneId, $t->type, $t->name, $content, $t->ttl, $t->proxied);
        }
        
        // Update Nameservers
        if (count($ns) >= 2) {
            localAPI('DomainUpdateNameservers', [
                'domainid' => $domainId,
                'ns1' => $ns[0],
                'ns2' => $ns[1]
            ]);
        }
    } catch (\Exception $e) {
        // Silently fail if zone exists or API is down
    }
});
