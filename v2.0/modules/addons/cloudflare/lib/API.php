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

        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
        ];

        if ($this->email) {
            $headers[] = 'X-Auth-Email: ' . $this->email;
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
            $error = isset($decoded['errors'][0]['message']) ? $decoded['errors'][0]['message'] : 'Unknown API Error';
            throw new \Exception("Cloudflare API Error: " . $error);
        }

        return $decoded;
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
}
