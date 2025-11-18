<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Validator.php';

header('Content-Type: application/json');

$session = Auth::requireRole('user');
$pdo = getDbConnection();

function logAction($pdo, $actorType, $actorId, $action, $resourceType, $resourceId, $details) {
    $stmt = $pdo->prepare("INSERT INTO system_logs (actor_type, actor_id, action, resource_type, resource_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $actorType,
        $actorId,
        $action,
        $resourceType,
        $resourceId,
        json_encode($details),
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT rr.*, rs.is_muted, rs.last_state_change
        FROM redirect_rules rr
        LEFT JOIN rule_state rs ON rr.id = rs.rule_id
        WHERE rr.user_id = ?
        ORDER BY rr.priority DESC, rr.created_at DESC
    ");
    $stmt->execute([$session['user_id']]);
    $rules = $stmt->fetchAll();
    
    echo json_encode(['rules' => $rules]);
    
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ruleName = $data['rule_name'] ?? '';
    $ruleType = $data['rule_type'] ?? '';
    $targetUrl = $data['target_url'] ?? null;
    $muteDurationOn = $data['mute_duration_on'] ?? 120;
    $muteDurationOff = $data['mute_duration_off'] ?? 300;
    $priority = $data['priority'] ?? 0;
    
    if (empty($ruleName) || empty($ruleType)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    if (!Validator::isValidRuleType($ruleType)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid rule type']);
        exit;
    }
    
    if ($ruleType === 'static_route' && empty($targetUrl)) {
        http_response_code(400);
        echo json_encode(['error' => 'Target URL required for static_route']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO redirect_rules (user_id, rule_name, rule_type, target_url, mute_duration_on, mute_duration_off, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$session['user_id'], $ruleName, $ruleType, $targetUrl, $muteDurationOn, $muteDurationOff, $priority]);
        $ruleId = $pdo->lastInsertId();
        
        if ($ruleType === 'mute_unmute') {
            $stmt = $pdo->prepare("INSERT INTO rule_state (rule_id, is_muted, last_state_change) VALUES (?, 0, NOW())");
            $stmt->execute([$ruleId]);
        }
        
        logAction($pdo, 'user', $session['user_id'], 'create_rule', 'redirect_rules', $ruleId, ['rule_name' => $ruleName, 'rule_type' => $ruleType]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'rule_id' => $ruleId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create rule']);
    }
    
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ruleId = $data['id'] ?? 0;
    $ruleName = $data['rule_name'] ?? '';
    $targetUrl = $data['target_url'] ?? null;
    $muteDurationOn = $data['mute_duration_on'] ?? 120;
    $muteDurationOff = $data['mute_duration_off'] ?? 300;
    $priority = $data['priority'] ?? 0;
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    
    if (empty($ruleId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing rule ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM redirect_rules WHERE id = ? AND user_id = ?");
    $stmt->execute([$ruleId, $session['user_id']]);
    $rule = $stmt->fetch();
    
    if (!$rule) {
        http_response_code(404);
        echo json_encode(['error' => 'Rule not found']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE redirect_rules SET rule_name = ?, target_url = ?, mute_duration_on = ?, mute_duration_off = ?, priority = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$ruleName, $targetUrl, $muteDurationOn, $muteDurationOff, $priority, $isActive, $ruleId]);
        
        logAction($pdo, 'user', $session['user_id'], 'update_rule', 'redirect_rules', $ruleId, ['rule_name' => $ruleName]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update rule']);
    }
    
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ruleId = $data['id'] ?? 0;
    
    if (empty($ruleId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing rule ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM redirect_rules WHERE id = ? AND user_id = ?");
    $stmt->execute([$ruleId, $session['user_id']]);
    $rule = $stmt->fetch();
    
    if (!$rule) {
        http_response_code(404);
        echo json_encode(['error' => 'Rule not found']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM redirect_rules WHERE id = ?");
        $stmt->execute([$ruleId]);
        
        logAction($pdo, 'user', $session['user_id'], 'delete_rule', 'redirect_rules', $ruleId, []);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete rule']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
