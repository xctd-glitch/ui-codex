<?php

class GeoIP {
    
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
    
    public static function detectDeviceType($userAgent) {
        if (empty($userAgent)) {
            return 'WEB';
        }
        
        $mobilePattern = '/mobile|android|iphone|ipad|tablet|blackberry|windows phone|webos/i';
        
        if (preg_match($mobilePattern, $userAgent)) {
            return 'WAP';
        }
        
        return 'WEB';
    }
    
    public static function getCountryFromIP($ip) {
        try {
            $response = @file_get_contents("https://api.ipquery.io/{$ip}?format=json");
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['location']['country_code'])) {
                    return strtoupper($data['location']['country_code']);
                }
            }
        } catch (Exception $e) {
            error_log("GeoIP lookup failed: " . $e->getMessage());
        }
        
        return null;
    }
    
    public static function isVPN($ip) {
        try {
            $response = @file_get_contents("https://blackbox.ipinfo.app/lookup/{$ip}");
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['proxy']) && $data['proxy'] === true) {
                    return true;
                }
            }
            
            $response = @file_get_contents("https://api.ipquery.io/{$ip}?format=json");
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['risk']['is_proxy']) && $data['risk']['is_proxy'] === true) {
                    return true;
                }
                if (isset($data['risk']['is_vpn']) && $data['risk']['is_vpn'] === true) {
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log("VPN detection failed: " . $e->getMessage());
        }
        
        return false;
    }
}
