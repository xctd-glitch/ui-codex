<?php

class RedirectLogic {
    private $pdo;
    private $userId;
    private $country;
    private $deviceType;
    private $isVPN;
    
    public function __construct($pdo, $userId, $country, $deviceType, $isVPN) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->country = $country;
        $this->deviceType = $deviceType;
        $this->isVPN = $isVPN;
    }
    
    public function decide() {
        $stmt = $this->pdo->prepare("
            SELECT rr.*, rs.is_muted, rs.last_state_change
            FROM redirect_rules rr
            LEFT JOIN rule_state rs ON rr.id = rs.rule_id
            WHERE rr.user_id = ? AND rr.is_active = 1
            ORDER BY rr.priority DESC, rr.id ASC
        ");
        $stmt->execute([$this->userId]);
        $rules = $stmt->fetchAll();
        
        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule);
            if ($result !== null) {
                return $result;
            }
        }
        
        return [
            'decision' => 'normal',
            'target' => null,
            'rule_applied' => null
        ];
    }
    
    private function evaluateRule($rule) {
        if (!$this->passesFilters()) {
            return null;
        }
        
        switch ($rule['rule_type']) {
            case 'mute_unmute':
                return $this->evaluateMuteUnmuteRule($rule);
            case 'random_route':
                return $this->evaluateRandomRouteRule($rule);
            case 'static_route':
                return $this->evaluateStaticRouteRule($rule);
            default:
                return null;
        }
    }
    
    private function evaluateMuteUnmuteRule($rule) {
        $isMuted = $rule['is_muted'] ?? 0;
        $lastStateChange = $rule['last_state_change'] ?? null;
        
        if ($lastStateChange === null) {
            $stmt = $this->pdo->prepare("INSERT INTO rule_state (rule_id, is_muted, last_state_change) VALUES (?, 0, NOW())");
            $stmt->execute([$rule['id']]);
            $isMuted = 0;
            $lastStateChange = time();
        } else {
            $lastStateChange = strtotime($lastStateChange);
        }
        
        $elapsed = time() - $lastStateChange;
        
        if ($isMuted) {
            if ($elapsed >= $rule['mute_duration_off']) {
                $stmt = $this->pdo->prepare("UPDATE rule_state SET is_muted = 0, last_state_change = NOW() WHERE rule_id = ?");
                $stmt->execute([$rule['id']]);
                $isMuted = 0;
            }
        } else {
            if ($elapsed >= $rule['mute_duration_on']) {
                $stmt = $this->pdo->prepare("UPDATE rule_state SET is_muted = 1, last_state_change = NOW() WHERE rule_id = ?");
                $stmt->execute([$rule['id']]);
                $isMuted = 1;
            }
        }
        
        if ($isMuted) {
            return null;
        }
        
        $target = $this->getTargetForUser();
        if ($target) {
            return [
                'decision' => 'redirect',
                'target' => $target,
                'rule_applied' => $rule['id']
            ];
        }
        
        return null;
    }
    
    private function evaluateRandomRouteRule($rule) {
        $target = $this->getTargetForUser();
        if ($target) {
            return [
                'decision' => 'redirect',
                'target' => $target,
                'rule_applied' => $rule['id']
            ];
        }
        return null;
    }
    
    private function evaluateStaticRouteRule($rule) {
        if (!empty($rule['target_url'])) {
            $target = $this->replaceDomainPlaceholder($rule['target_url']);
            return [
                'decision' => 'redirect',
                'target' => $target,
                'rule_applied' => $rule['id']
            ];
        }
        return null;
    }
    
    private function passesFilters() {
        $stmt = $this->pdo->prepare("SELECT device_scope FROM user_routing_config WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $config = $stmt->fetch();
        
        if ($config) {
            $deviceScope = $config['device_scope'];
            if ($deviceScope !== 'ALL') {
                if ($deviceScope !== $this->deviceType) {
                    return false;
                }
            }
        }
        
        if ($this->country) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM user_countries WHERE user_id = ?");
            $stmt->execute([$this->userId]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM user_countries WHERE user_id = ? AND iso_code = ?");
                $stmt->execute([$this->userId, $this->country]);
                $countryMatch = $stmt->fetch();
                
                if ($countryMatch['count'] == 0) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    private function getTargetForUser() {
        $stmt = $this->pdo->prepare("SELECT selection_type, specific_domain FROM user_domain_selection WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $selection = $stmt->fetch();
        
        if (!$selection) {
            $selection = ['selection_type' => 'random_user', 'specific_domain' => null];
        }
        
        $stmt = $this->pdo->prepare("SELECT url FROM user_target_urls WHERE user_id = ? ORDER BY RAND() LIMIT 1");
        $stmt->execute([$this->userId]);
        $targetUrl = $stmt->fetch();
        
        if (!$targetUrl) {
            return null;
        }
        
        return $this->replaceDomainPlaceholder($targetUrl['url']);
    }
    
    private function replaceDomainPlaceholder($url) {
        if (strpos($url, '{domain}') === false) {
            return $url;
        }
        
        $stmt = $this->pdo->prepare("SELECT selection_type, specific_domain FROM user_domain_selection WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $selection = $stmt->fetch();
        
        if (!$selection) {
            $selection = ['selection_type' => 'random_user', 'specific_domain' => null];
        }
        
        $domain = null;
        
        switch ($selection['selection_type']) {
            case 'specific':
                $domain = $selection['specific_domain'];
                break;
                
            case 'random_global':
                $stmt = $this->pdo->prepare("
                    SELECT apd.domain
                    FROM admin_parked_domains apd
                    INNER JOIN users u ON u.created_by_admin_id = apd.admin_id
                    INNER JOIN user_tags ut ON ut.user_id = u.id
                    INNER JOIN admin_tags at ON at.tag_id = ut.tag_id AND at.admin_id = apd.admin_id
                    WHERE u.id = ?
                    ORDER BY RAND()
                    LIMIT 1
                ");
                $stmt->execute([$this->userId]);
                $result = $stmt->fetch();
                $domain = $result ? $result['domain'] : null;
                break;
                
            case 'random_user':
            default:
                $stmt = $this->pdo->prepare("SELECT domain FROM user_parked_domains WHERE user_id = ? ORDER BY RAND() LIMIT 1");
                $stmt->execute([$this->userId]);
                $result = $stmt->fetch();
                $domain = $result ? $result['domain'] : null;
                break;
        }
        
        if ($domain) {
            return str_replace('{domain}', $domain, $url);
        }
        
        return $url;
    }
}
