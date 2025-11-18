<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Validator.php';

header('Content-Type: application/json');

$session = Auth::requireAnyRole(['admin', 'user']);
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
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'admin_countries' && $session['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM admin_countries WHERE admin_id = ? ORDER BY iso_code ASC");
        $stmt->execute([$session['user_id']]);
        $countries = $stmt->fetchAll();
        
        echo json_encode(['countries' => $countries]);
        
    } elseif ($action === 'user_countries' && $session['role'] === 'user') {
        $stmt = $pdo->prepare("SELECT * FROM user_countries WHERE user_id = ? ORDER BY iso_code ASC");
        $stmt->execute([$session['user_id']]);
        $countries = $stmt->fetchAll();
        
        echo json_encode(['countries' => $countries]);
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} elseif ($method === 'POST') {
    if ($action === 'set_admin_countries' && $session['role'] === 'admin') {
        $countriesInput = $data['countries'] ?? '';
        
        $countries = Validator::parseCountryList($countriesInput);
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM admin_countries WHERE admin_id = ?");
            $stmt->execute([$session['user_id']]);
            
            foreach ($countries as $code) {
                $stmt = $pdo->prepare("INSERT INTO admin_countries (admin_id, iso_code) VALUES (?, ?)");
                $stmt->execute([$session['user_id'], $code]);
            }
            
            logAction($pdo, 'admin', $session['user_id'], 'set_countries', 'admin_countries', null, ['count' => count($countries)]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'count' => count($countries)]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to set countries']);
        }
        
    } elseif ($action === 'set_user_countries' && $session['role'] === 'user') {
        $countriesInput = $data['countries'] ?? '';
        
        $countries = Validator::parseCountryList($countriesInput);
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM user_countries WHERE user_id = ?");
            $stmt->execute([$session['user_id']]);
            
            foreach ($countries as $code) {
                $stmt = $pdo->prepare("INSERT INTO user_countries (user_id, iso_code) VALUES (?, ?)");
                $stmt->execute([$session['user_id'], $code]);
            }
            
            logAction($pdo, 'user', $session['user_id'], 'set_countries', 'user_countries', null, ['count' => count($countries)]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'count' => count($countries)]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to set countries']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
