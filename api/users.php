<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Validator.php';

header('Content-Type: application/json');

$session = Auth::requireRole('admin');
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
        SELECT u.id, u.username, u.email, u.token, u.is_active, u.created_at,
               GROUP_CONCAT(t.name) as tags
        FROM users u
        LEFT JOIN user_tags ut ON u.id = ut.user_id
        LEFT JOIN tags t ON ut.tag_id = t.id
        WHERE u.created_by_admin_id = ? AND u.is_active = 1
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute([$session['user_id']]);
    $users = $stmt->fetchAll();
    
    echo json_encode(['users' => $users]);
    
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $tags = $data['tags'] ?? [];
    
    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    if (!Validator::isValidUsername($username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid username format']);
        exit;
    }
    
    if (!Validator::isValidEmail($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    
    if (!Validator::isValidPassword($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $exists = $stmt->fetch();
    
    if ($exists['count'] > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Username or email already exists']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
        $token = bin2hex(random_bytes(32));
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, token, created_by_admin_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $token, $session['user_id']]);
        $userId = $pdo->lastInsertId();
        
        if (!empty($tags) && is_array($tags)) {
            foreach ($tags as $tagId) {
                $stmt = $pdo->prepare("INSERT INTO user_tags (user_id, tag_id, assigned_by_id) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $tagId, $session['user_id']]);
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO user_routing_config (user_id, device_scope) VALUES (?, 'ALL')");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("INSERT INTO user_domain_selection (user_id, selection_type) VALUES (?, 'random_user')");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("INSERT INTO user_meta (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        logAction($pdo, 'admin', $session['user_id'], 'create_user', 'user', $userId, ['username' => $username]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'user_id' => $userId,
            'token' => $token
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user']);
    }
    
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $userId = $data['id'] ?? 0;
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? null;
    
    if (empty($userId) || empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    if (!Validator::isValidEmail($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND created_by_admin_id = ? AND is_active = 1");
    $stmt->execute([$userId, $session['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    try {
        if ($password) {
            if (!Validator::isValidPassword($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 8 characters']);
                exit;
            }
            
            $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
            $stmt->execute([$email, $hashedPassword, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $userId]);
        }
        
        logAction($pdo, 'admin', $session['user_id'], 'update_user', 'user', $userId, ['email' => $email]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user']);
    }
    
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['id'] ?? 0;
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND created_by_admin_id = ? AND is_active = 1");
    $stmt->execute([$userId, $session['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$userId]);
        
        logAction($pdo, 'admin', $session['user_id'], 'delete_user', 'user', $userId, []);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
