<?php

namespace WHMCS\Module\Addon\Cloudflare;

use WHMCS\Database\Capsule;

class Helpers {
    /**
     * Detect the best IP address for a domain's DNS records
     */
    public static function getServerIp($domain, $userId = null) {
        $query = Capsule::table('tblhosting')->where('domain', $domain);
        if ($userId) {
            $query->where('userid', $userId);
        }
        
        $hosting = $query->whereIn('domainstatus', ['Active', 'Pending'])->first();
        
        if ($hosting) {
            if ($hosting->dedicatedip) return $hosting->dedicatedip;
            $serverIp = Capsule::table('tblservers')->where('id', $hosting->server)->value('ipaddress');
            if ($serverIp) return $serverIp;
        }
        
        // Final Fallback: Check for Admin Defined Parking IP
        $parkingIp = Capsule::table('mod_cloudflare_settings')->where('setting', 'default_parking_ip')->value('value');
        if ($parkingIp) return $parkingIp;

        return $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
    }
}
