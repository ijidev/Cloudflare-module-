<?php

namespace WHMCS\Module\Addon\Cloudflare;

/**
 * Cloudflare API v4 Wrapper for Addon Module
 */
class API
{
    private $apiToken;
    private $email;
    private $apiUrl = 'https://api.cloudflare.com/client/v4/';

    public function __construct($apiToken, $email = null)
    {
        $this->apiToken = $apiToken;
        $this->email = $email;
    }

    /**
     * Perform an API Request
     */
    private function request($endpoint, $data = [], $method = 'GET')
    {
        $ch = curl_init();
        $url = $this->apiUrl . $endpoint;

        $email = trim($this->email);
        $token = trim($this->apiToken);

        // Use Global API Key if email is provided, otherwise use Bearer Token.
        if (!empty($email)) {
            $headers = [
                'X-Auth-Email: ' . $email,
                'X-Auth-Key: ' . $token,
                'Content-Type: application/json',
            ];
        } else {
            // Treat as an API Token, which uses Bearer auth
            $headers = [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ];
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400 || (isset($decoded['success']) && !$decoded['success'])) {
            $errorMsg = isset($decoded['errors'][0]['message']) ? $decoded['errors'][0]['message'] : 'Unknown API Error';
            $errorCode = isset($decoded['errors'][0]['code']) ? $decoded['errors'][0]['code'] : '';
            
            $extra = '';
            if ($httpCode == 401 || $httpCode == 403 || $errorCode == 10000 || $errorCode == 9109) {
                $extra = " (Auth Error: If using an API Token, ensure the Email field is EMPTY in settings. If using a Global Key, ensure Email is correct. Also ensure no trailing spaces.)";
            }
            
            throw new \Exception("Cloudflare API Error (HTTP $httpCode): " . $errorMsg . " (Code: " . $errorCode . ")" . $extra);
        }

        return $decoded;
    }

    public function createAccount($name, $email)
    {
        // Requires Tenant API / Partner privileges.
        $data = ['name' => $name, 'type' => 'standard'];
        return $this->request('accounts', $data, 'POST');
    }

    public function createZone($domain, $accountId = null)
    {
        $data = ['name' => $domain, 'jump_start' => true];
        if ($accountId) $data['account'] = ['id' => $accountId];
        return $this->request('zones', $data, 'POST');
    }

    public function getZoneId($domain)
    {
        $response = $this->request('zones?name=' . urlencode($domain));
        return $response['result'][0]['id'] ?? null;
    }

    public function getZones()
    {
        $response = $this->request('zones');
        return $response['result'] ?? [];
    }

    public function getDNSRecords($zoneId)
    {
        return $this->request("zones/$zoneId/dns_records");
    }

    public function addDNSRecord($zoneId, $type, $name, $content, $ttl = 1, $proxied = true)
    {
        $data = ['type' => $type, 'name' => $name, 'content' => $content, 'ttl' => $ttl, 'proxied' => $proxied];
        return $this->request("zones/$zoneId/dns_records", $data, 'POST');
    }

    public function deleteDNSRecord($zoneId, $recordId)
    {
        return $this->request("zones/$zoneId/dns_records/$recordId", [], 'DELETE');
    }

    public function updateDNSRecord($zoneId, $recordId, $type, $name, $content, $ttl = 1, $proxied = true)
    {
        $data = ['type' => $type, 'name' => $name, 'content' => $content, 'ttl' => $ttl, 'proxied' => $proxied];
        return $this->request("zones/$zoneId/dns_records/$recordId", $data, 'PUT');
    }

    public function pauseZone($zoneId, $paused = true)
    {
        return $this->request("zones/$zoneId", ['paused' => $paused], 'PATCH');
    }

    public function unpauseZone($zoneId)
    {
        return $this->pauseZone($zoneId, false);
    }

    public function purgeCache($zoneId)
    {
        return $this->request("zones/$zoneId/purge_cache", ['purge_everything' => true], 'POST');
    }

    public function updateZoneSetting($zoneId, $setting, $value)
    {
        return $this->request("zones/$zoneId/settings/$setting", ['value' => $value], 'PATCH');
    }

    public function getZoneSettings($zoneId)
    {
        return $this->request("zones/$zoneId/settings");
    }

    public function getZoneDetails($zoneId)
    {
        return $this->request("zones/$zoneId");
    }

    public function getAccounts()
    {
        return $this->request("accounts");
    }

    public function getPlanType($accountId)
    {
        try {
            $response = $this->request("accounts/$accountId");
            // Check for Enterprise/Partner flags in account roles or subscriptions if possible
            // For now, we'll return the response and check in the controller
            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }
}
