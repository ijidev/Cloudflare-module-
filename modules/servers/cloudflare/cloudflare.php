 <?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/lib/API.php';

use WHMCS\Module\Server\Cloudflare\API;

/**
 * Define module metadata
 */
function cloudflare_MetaData()
{
    return [
        'DisplayName' => 'Cloudflare Multi-Tier',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
    ];
}

/**
 * Define configuration options
 */
function cloudflare_ConfigOptions()
{
    return [
        'mode' => [
            'FriendlyName' => 'Account Mode',
            'Type' => 'dropdown',
            'Options' => [
                'managed' => 'Managed (Host Account)',
                'dedicated' => 'Dedicated (Client Account)',
                'byot' => 'BYOT (Client Token)',
            ],
            'Description' => 'Choose how zones are managed.',
        ],
        'api_token' => [
            'FriendlyName' => 'Master API Token',
            'Type' => 'password',
            'Size' => '25',
            'Description' => 'API Token for Managed/Dedicated mode.',
        ],
        'account_id' => [
            'FriendlyName' => 'Account ID',
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'Cloudflare Account ID for Managed mode.',
        ],
        'dns_template' => [
            'FriendlyName' => 'DNS Template',
            'Type' => 'textarea',
            'Rows' => '5',
            'Cols' => '50',
            'Description' => 'Format: type|name|content,separated|by|newlines. <br>Variables: {ip}, {domain}',
        ],
    ];
}

/**
 * Provisioning: Create Account / Add Zone
 */
function cloudflare_CreateAccount(array $params)
{
    try {
        $domain = $params['domain'];
        $serverIp = $params['serverip'];
        $mode = $params['configoption1']; 
        $masterToken = $params['configoption2']; 
        $masterAccountId = $params['configoption3'];
        $dnsTemplate = $params['configoption4'];

        $api = new API($masterToken);

        if ($mode === 'managed') {
            $targetAccountId = $masterAccountId;
        } elseif ($mode === 'dedicated') {
            // Create a sub-account for the client
            $accountName = $params['clientsdetails']['firstname'] . ' ' . $params['clientsdetails']['lastname'] . ' (' . $params['domain'] . ')';
            $accountResponse = $api->createAccount($accountName);
            $targetAccountId = $accountResponse['result']['id'];
        }

        if ($mode === 'managed' || $mode === 'dedicated') {
            // Add zone to target account
            $zoneResponse = $api->createZone($domain, $targetAccountId);
            $zoneId = $zoneResponse['result']['id'];
            $ns = $zoneResponse['result']['name_servers'];

            // Automated Nameserver Switching
            if (!empty($ns) && !empty($params['domainid'])) {
                $command = 'UpdateNameservers';
                $postData = [
                    'domainid' => $params['domainid'],
                    'ns1' => $ns[0],
                    'ns2' => $ns[1],
                ];
                localAPI($command, $postData);
            }

            // Automated DNS Template Population
            if (!empty($dnsTemplate)) {
                $lines = explode("\n", str_replace("\r", "", $dnsTemplate));
                foreach ($lines as $line) {
                    $parts = explode('|', trim($line));
                    if (count($parts) >= 3) {
                        $type = $parts[0];
                        $name = str_replace(['{domain}', '{ip}'], [$domain, $serverIp], $parts[1]);
                        $content = str_replace(['{domain}', '{ip}'], [$domain, $serverIp], $parts[2]);
                        
                        try {
                            $api->addDNSRecord($zoneId, $type, $name, $content);
                        } catch (\Exception $e) {
                            // Continue on individual record failure
                        }
                    }
                }
            }
        } elseif ($mode === 'byot') {
            // In BYOT, we wait for client token
            return 'success';
        }

        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Client Area Dashboard
 */
function cloudflare_ClientArea(array $params)
{
    $serviceid = $params['serviceid'];
    $domain = $params['domain'];
    $mode = $params['configoption1'];
    $masterToken = $params['configoption2'];
    $masterAccountId = $params['configoption3'];

    // Determine target token and accountId based on mode
    $targetToken = $masterToken;
    $isByotEnabled = false;

    if ($mode === 'byot') {
        // Fetch client token from custom field
        $customfields = $params['customfields'];
        if (isset($customfields['Cloudflare Token']) && !empty($customfields['Cloudflare Token'])) {
            $targetToken = $customfields['Cloudflare Token'];
            $isByotEnabled = true;
        } else {
            return [
                'templatefile' => 'templates/clientarea',
                'vars' => [
                    'domain' => $domain,
                    'mode' => $mode,
                    'error' => "Please provide your Cloudflare API Token in the 'Cloudflare Token' custom field to manage this zone.",
                ],
            ];
        }
    }

    $api = new API($targetToken);
    $vars = [
        'serviceid' => $serviceid,
        'domain' => $domain,
        'mode' => strtoupper($mode),
        'isByot' => ($mode === 'byot'),
    ];

    try {
        $zoneId = $api->getZoneId($domain);
        if ($zoneId) {
            $dnsResponse = $api->getDNSRecords($zoneId);
            $vars['dnsRecords'] = $dnsResponse['result'];
            
            $zoneDetails = $api->getZoneDetails($zoneId);
            $vars['nameservers'] = $zoneDetails['result']['name_servers'];
            $vars['zoneStatus'] = $zoneDetails['result']['status'];
            $vars['zonePaused'] = $zoneDetails['result']['paused'];

            $settingsResponse = $api->getZoneSettings($zoneId);
            foreach ($settingsResponse['result'] as $setting) {
                if ($setting['id'] === 'development_mode') {
                    $vars['devMode'] = $setting['value'];
                }
                if ($setting['id'] === 'security_level') {
                    $vars['securityLevel'] = $setting['value'];
                }
            }
        } else {
            $vars['error'] = "Domain not found in Cloudflare. Please ensure it is provisioned correctly.";
        }
    } catch (\Exception $e) {
        $vars['error'] = $e->getMessage();
    }

    return [
        'templatefile' => 'templates/clientarea',
        'vars' => $vars,
    ];
}

/**
 * Handle Add Record Action
 */
function cloudflare_addRecord($params)
{
    try {
        $token = $params['configoption2'];
        if ($params['configoption1'] === 'byot') {
            $token = $params['customfields']['Cloudflare Token'];
        }
        $api = new API($token);
        $zoneId = $api->getZoneId($params['domain']);
        
        $type = $_POST['type'];
        $name = $_POST['name'];
        $content = $_POST['content'];
        $proxied = (isset($_POST['proxied']) && $_POST['proxied'] == 'on');

        $api->addDNSRecord($zoneId, $type, $name, $content, 1, $proxied);
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Handle Delete Record Action
 */
function cloudflare_deleteRecord($params)
{
    try {
        $token = $params['configoption2'];
        if ($params['configoption1'] === 'byot') {
            $token = $params['customfields']['Cloudflare Token'];
        }
        $api = new API($token);
        $zoneId = $api->getZoneId($params['domain']);
        $recordId = $_POST['record_id'];
        
        $api->deleteDNSRecord($zoneId, $recordId);
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Handle Purge Cache Action
 */
function cloudflare_purgeCache($params)
{
    try {
        $token = $params['configoption2'];
        if ($params['configoption1'] === 'byot') {
            $token = $params['customfields']['Cloudflare Token'];
        }
        $api = new API($token);
        $zoneId = $api->getZoneId($params['domain']);
        
        $api->purgeCache($zoneId);
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Handle Development Mode Toggle
 */
function cloudflare_toggleDevMode($params)
{
    try {
        $token = $params['configoption2'];
        if ($params['configoption1'] === 'byot') {
            $token = $params['customfields']['Cloudflare Token'];
        }
        $api = new API($token);
        $zoneId = $api->getZoneId($params['domain']);
        
        $value = ($_POST['value'] === 'on') ? 'on' : 'off';
        $api->updateZoneSetting($zoneId, 'development_mode', $value);
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Handle Under Attack Mode Toggle
 */
function cloudflare_toggleUnderAttack($params)
{
    try {
        $token = $params['configoption2'];
        if ($params['configoption1'] === 'byot') {
            $token = $params['customfields']['Cloudflare Token'];
        }
        $api = new API($token);
        $zoneId = $api->getZoneId($params['domain']);
        
        $value = ($_POST['value'] === 'on') ? 'under_attack' : 'medium';
        $api->updateZoneSetting($zoneId, 'security_level', $value);
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}
