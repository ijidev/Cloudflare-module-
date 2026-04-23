<?php
/**
 * Cloudflare Module Hooks
 */

use WHMCS\Database\Capsule;

add_hook('InvoicePaid', 1, function($vars) {
    $invoiceId = $vars['invoiceid'];
    
    // Check if invoice contains a "Cloudflare Pro" item
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

add_hook('AddonActivated', 1, function($vars) {
    // If an addon specifically named Cloudflare Pro is activated
    $addonId = $vars['addonid'];
    $userId = Capsule::table('tblhostingaddon')->join('tblhosting', 'tblhostingaddon.hostingid', '=', 'tblhosting.id')->where('tblhostingaddon.id', $addonId)->value('tblhosting.userid');
    
    $addonName = Capsule::table('tbladdons')->where('id', $vars['id'])->value('name');
    if (stripos($addonName, 'Cloudflare Pro') !== false) {
        Capsule::table('mod_cloudflare_client_status')->updateOrInsert(
            ['client_id' => $userId],
            ['is_pro' => 1]
        );
    }
});
