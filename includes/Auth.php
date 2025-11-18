<?php

class Auth {
    const SESSION_TIMEOUT = 3600;
    
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function getSession() {
        self::startSession();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['last_activity'])) {
            return null;
        }
        
        if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
            self::logout();
            return null;
        }
        
        $_SESSION['last_activity'] = time();
        
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
    }
    
    public static function login($username, $password, $role, $pdo) {
        $tables = [
            'superadmin' => 'superadmins',
            'admin' => 'admins',
            'user' => 'users'
        ];
        
        if (!isset($tables[$role])) {
            return false;
        }
        
        $table = $tables[$role];
        $stmt = $pdo->prepare("SELECT id, username, email, password FROM $table WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        self::startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $role;
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    public static function logout() {
        self::startSession();
        session_unset();
        session_destroy();
    }
    
    public static function requireRole($role) {
        $session = self::getSession();
        
        if (!$session || $session['role'] !== $role) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        
        return $session;
    }
    
    public static function requireAnyRole($roles) {
        $session = self::getSession();
        
        if (!$session || !in_array($session['role'], $roles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        
        return $session;
    }
}
