<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/GeoIP.php';
require_once __DIR__ . '/../includes/RedirectLogic.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = $_GET['user_id'] ?? null;
$token = $_GET['token'] ?? null;

if (empty($userId) || empty($token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id or token']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT system_on FROM system_config WHERE id = 1");
    $stmt->execute();
    $config = $stmt->fetch();
    
    if (!$config || $config['system_on'] != 1) {
        echo json_encode([
            'decision' => 'normal',
            'reason' => 'System is off'
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND token = ? AND is_active = 1");
    $stmt->execute([$userId, $token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid user_id or token']);
        exit;
    }
    
    $ip = GeoIP::getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceType = GeoIP::detectDeviceType($userAgent);
    $country = GeoIP::getCountryFromIP($ip);
    $isVPN = GeoIP::isVPN($ip);
    
    $redirectLogic = new RedirectLogic($pdo, $userId, $country, $deviceType, $isVPN);
    $decision = $redirectLogic->decide();
    
    $stmt = $pdo->prepare("
        INSERT INTO redirect_logs (user_id, domain_used, target_url, country_iso, device_type, ip_address, user_agent, is_vpn, rule_applied, decision)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $_SERVER['HTTP_HOST'] ?? null,
        $decision['target'] ?? null,
        $country,
        $deviceType,
        $ip,
        $userAgent,
        $isVPN ? 1 : 0,
        $decision['rule_applied'] ?? null,
        $decision['decision']
    ]);
    
    echo json_encode($decision);
    
} catch (Exception $e) {
    error_log("Redirect error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
