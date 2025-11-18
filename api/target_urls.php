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
    if ($action === 'admin_target_urls' && $session['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM admin_target_urls WHERE admin_id = ? ORDER BY created_at DESC");
        $stmt->execute([$session['user_id']]);
        $urls = $stmt->fetchAll();
        
        echo json_encode(['target_urls' => $urls]);
        
    } elseif ($action === 'user_target_urls' && $session['role'] === 'user') {
        $stmt = $pdo->prepare("SELECT * FROM user_target_urls WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$session['user_id']]);
        $urls = $stmt->fetchAll();
        
        echo json_encode(['target_urls' => $urls]);
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} elseif ($method === 'POST') {
    if ($action === 'add_admin_target_url' && $session['role'] === 'admin') {
        $url = $data['url'] ?? '';
        
        if (empty($url)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing URL']);
            exit;
        }
        
        if (!Validator::isValidUrl($url) && strpos($url, '{domain}') === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URL format']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO admin_target_urls (admin_id, url) VALUES (?, ?)");
            $stmt->execute([$session['user_id'], $url]);
            $urlId = $pdo->lastInsertId();
            
            logAction($pdo, 'admin', $session['user_id'], 'add_target_url', 'admin_target_urls', $urlId, ['url' => $url]);
            
            echo json_encode(['success' => true, 'id' => $urlId]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add target URL']);
        }
        
    } elseif ($action === 'add_user_target_url' && $session['role'] === 'user') {
        $url = $data['url'] ?? '';
        
        if (empty($url)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing URL']);
            exit;
        }
        
        if (!Validator::isValidUrl($url) && strpos($url, '{domain}') === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URL format']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO user_target_urls (user_id, url) VALUES (?, ?)");
            $stmt->execute([$session['user_id'], $url]);
            $urlId = $pdo->lastInsertId();
            
            logAction($pdo, 'user', $session['user_id'], 'add_target_url', 'user_target_urls', $urlId, ['url' => $url]);
            
            echo json_encode(['success' => true, 'id' => $urlId]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add target URL']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} elseif ($method === 'DELETE') {
    if ($action === 'delete_admin_target_url' && $session['role'] === 'admin') {
        $urlId = $data['id'] ?? 0;
        
        if (empty($urlId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing URL ID']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM admin_target_urls WHERE id = ? AND admin_id = ?");
        $stmt->execute([$urlId, $session['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            logAction($pdo, 'admin', $session['user_id'], 'delete_target_url', 'admin_target_urls', $urlId, []);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'URL not found']);
        }
        
    } elseif ($action === 'delete_user_target_url' && $session['role'] === 'user') {
        $urlId = $data['id'] ?? 0;
        
        if (empty($urlId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing URL ID']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM user_target_urls WHERE id = ? AND user_id = ?");
        $stmt->execute([$urlId, $session['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            logAction($pdo, 'user', $session['user_id'], 'delete_target_url', 'user_target_urls', $urlId, []);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'URL not found']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
