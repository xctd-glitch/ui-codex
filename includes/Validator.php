<?php

class Validator {
    
    public static function isValidUsername($username) {
        return is_string($username) && strlen($username) >= 3 && strlen($username) <= 255 && preg_match('/^[a-zA-Z0-9_]+$/', $username);
    }
    
    public static function isValidPassword($password) {
        return is_string($password) && strlen($password) >= 8;
    }
    
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public static function isValidISOCountryCode($code) {
        return is_string($code) && preg_match('/^[A-Z]{2}$/', strtoupper($code));
    }
    
    public static function isValidDomain($domain) {
        return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain);
    }
    
    public static function parseCountryList($input) {
        if (empty($input)) {
            return [];
        }
        
        $codes = array_map('trim', explode(',', $input));
        $validCodes = [];
        
        foreach ($codes as $code) {
            $code = strtoupper($code);
            if (self::isValidISOCountryCode($code)) {
                $validCodes[] = $code;
            }
        }
        
        return array_unique($validCodes);
    }
    
    public static function parseDomainList($input) {
        if (empty($input)) {
            return [];
        }
        
        $lines = explode("\n", $input);
        $validDomains = [];
        
        foreach ($lines as $line) {
            $domain = trim($line);
            if (!empty($domain) && self::isValidDomain($domain)) {
                $validDomains[] = $domain;
            }
        }
        
        return array_unique($validDomains);
    }
    
    public static function isValidRuleType($type) {
        return in_array($type, ['mute_unmute', 'random_route', 'static_route']);
    }
    
    public static function isValidDeviceScope($scope) {
        return in_array($scope, ['WAP', 'WEB', 'ALL']);
    }
    
    public static function isValidSelectionType($type) {
        return in_array($type, ['random_global', 'random_user', 'specific']);
    }
    
    public static function sanitizeString($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
