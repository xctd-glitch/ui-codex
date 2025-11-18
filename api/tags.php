<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Validator.php';

header('Content-Type: application/json');

$session = Auth::requireRole('superadmin');
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
    $stmt = $pdo->query("SELECT * FROM tags ORDER BY name ASC");
    $tags = $stmt->fetchAll();
    
    echo json_encode(['tags' => $tags]);
    
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing tag name']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tags WHERE name = ?");
    $stmt->execute([$name]);
    $exists = $stmt->fetch();
    
    if ($exists['count'] > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Tag already exists']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tags (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $tagId = $pdo->lastInsertId();
        
        logAction($pdo, 'superadmin', $session['user_id'], 'create_tag', 'tag', $tagId, ['name' => $name]);
        
        echo json_encode([
            'success' => true,
            'tag_id' => $tagId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create tag']);
    }
    
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $tagId = $data['id'] ?? 0;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    
    if (empty($tagId) || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE tags SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $tagId]);
        
        logAction($pdo, 'superadmin', $session['user_id'], 'update_tag', 'tag', $tagId, ['name' => $name]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update tag']);
    }
    
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $tagId = $data['id'] ?? 0;
    
    if (empty($tagId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing tag ID']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$tagId]);
        
        logAction($pdo, 'superadmin', $session['user_id'], 'delete_tag', 'tag', $tagId, []);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete tag']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
