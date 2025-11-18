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
    $stmt = $pdo->prepare("
        SELECT a.id, a.username, a.email, a.is_active, a.created_at,
               GROUP_CONCAT(t.name) as tags
        FROM admins a
        LEFT JOIN admin_tags at ON a.id = at.admin_id
        LEFT JOIN tags t ON at.tag_id = t.id
        WHERE a.is_active = 1
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    echo json_encode(['admins' => $admins]);
    
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
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins WHERE username = ? OR email = ?");
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
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, created_by_superadmin_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $session['user_id']]);
        $adminId = $pdo->lastInsertId();
        
        if (!empty($tags) && is_array($tags)) {
            foreach ($tags as $tagId) {
                $stmt = $pdo->prepare("INSERT INTO admin_tags (admin_id, tag_id, assigned_by_id) VALUES (?, ?, ?)");
                $stmt->execute([$adminId, $tagId, $session['user_id']]);
            }
        }
        
        logAction($pdo, 'superadmin', $session['user_id'], 'create_admin', 'admin', $adminId, ['username' => $username]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'admin_id' => $adminId
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create admin']);
    }
    
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $adminId = $data['id'] ?? 0;
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? null;
    
    if (empty($adminId) || empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    if (!Validator::isValidEmail($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ? AND is_active = 1");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        http_response_code(404);
        echo json_encode(['error' => 'Admin not found']);
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
            $stmt = $pdo->prepare("UPDATE admins SET email = ?, password = ? WHERE id = ?");
            $stmt->execute([$email, $hashedPassword, $adminId]);
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET email = ? WHERE id = ?");
            $stmt->execute([$email, $adminId]);
        }
        
        logAction($pdo, 'superadmin', $session['user_id'], 'update_admin', 'admin', $adminId, ['email' => $email]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update admin']);
    }
    
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $adminId = $data['id'] ?? 0;
    
    if (empty($adminId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing admin ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ? AND is_active = 1");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        http_response_code(404);
        echo json_encode(['error' => 'Admin not found']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE admins SET is_active = 0 WHERE id = ?");
        $stmt->execute([$adminId]);
        
        logAction($pdo, 'superadmin', $session['user_id'], 'delete_admin', 'admin', $adminId, []);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete admin']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
