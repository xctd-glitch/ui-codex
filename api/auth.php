<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Validator.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'login') {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? '';
        
        if (empty($username) || empty($password) || empty($role)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        if (!in_array($role, ['superadmin', 'admin', 'user'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
            exit;
        }
        
        try {
            $pdo = getDbConnection();
            $success = Auth::login($username, $password, $role, $pdo);
            
            if ($success) {
                $session = Auth::getSession();
                echo json_encode([
                    'success' => true,
                    'user' => $session
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    } elseif ($action === 'logout') {
        Auth::logout();
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} elseif ($method === 'GET') {
    $session = Auth::getSession();
    
    if ($session) {
        echo json_encode([
            'authenticated' => true,
            'user' => $session
        ]);
    } else {
        echo json_encode([
            'authenticated' => false
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
