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
    if ($action === 'admin_domains' && $session['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM admin_parked_domains WHERE admin_id = ? ORDER BY created_at DESC");
        $stmt->execute([$session['user_id']]);
        $domains = $stmt->fetchAll();
        
        echo json_encode(['domains' => $domains]);
        
    } elseif ($action === 'user_domains' && $session['role'] === 'user') {
        $stmt = $pdo->prepare("SELECT * FROM user_parked_domains WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$session['user_id']]);
        $domains = $stmt->fetchAll();
        
        echo json_encode(['domains' => $domains]);
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} elseif ($method === 'POST') {
    if ($action === 'add_admin_domains' && $session['role'] === 'admin') {
        $domainsInput = $data['domains'] ?? '';
        $cloudflareSync = $data['cloudflare_sync'] ?? false;
        
        if (empty($domainsInput)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing domains']);
            exit;
        }
        
        $domains = Validator::parseDomainList($domainsInput);
        
        if (empty($domains)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid domains provided']);
            exit;
        }
        
        if (count($domains) < 1 || count($domains) > 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Must provide between 1 and 10 domains']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin_parked_domains WHERE admin_id = ?");
        $stmt->execute([$session['user_id']]);
        $currentCount = $stmt->fetch()['count'];
        
        if ($currentCount + count($domains) > 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Maximum 10 domains allowed']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            foreach ($domains as $domain) {
                $stmt = $pdo->prepare("INSERT INTO admin_parked_domains (admin_id, domain, cloudflare_synced) VALUES (?, ?, ?)");
                $stmt->execute([$session['user_id'], $domain, $cloudflareSync ? 1 : 0]);
                
                if ($cloudflareSync) {
                    $stmt = $pdo->prepare("INSERT INTO cloudflare_sync_status (domain, sync_status) VALUES (?, 'pending')");
                    $stmt->execute([$domain]);
                }
            }
            
            logAction($pdo, 'admin', $session['user_id'], 'add_domains', 'admin_parked_domains', null, ['count' => count($domains)]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'added' => count($domains)]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add domains']);
        }
        
    } elseif ($action === 'add_user_domains' && $session['role'] === 'user') {
        $domainsInput = $data['domains'] ?? '';
        $cloudflareSync = $data['cloudflare_sync'] ?? false;
        
        if (empty($domainsInput)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing domains']);
            exit;
        }
        
        $domains = Validator::parseDomainList($domainsInput);
        
        if (empty($domains)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid domains provided']);
            exit;
        }
        
        if (count($domains) < 1 || count($domains) > 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Must provide between 1 and 10 domains']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_parked_domains WHERE user_id = ?");
        $stmt->execute([$session['user_id']]);
        $currentCount = $stmt->fetch()['count'];
        
        if ($currentCount + count($domains) > 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Maximum 10 domains allowed']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            foreach ($domains as $domain) {
                $stmt = $pdo->prepare("INSERT INTO user_parked_domains (user_id, domain, cloudflare_synced) VALUES (?, ?, ?)");
                $stmt->execute([$session['user_id'], $domain, $cloudflareSync ? 1 : 0]);
                
                if ($cloudflareSync) {
                    $stmt = $pdo->prepare("INSERT INTO cloudflare_sync_status (domain, sync_status) VALUES (?, 'pending')");
                    $stmt->execute([$domain]);
                }
            }
            
            logAction($pdo, 'user', $session['user_id'], 'add_domains', 'user_parked_domains', null, ['count' => count($domains)]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'added' => count($domains)]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add domains']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} elseif ($method === 'DELETE') {
    if ($action === 'delete_admin_domain' && $session['role'] === 'admin') {
        $domainId = $data['id'] ?? 0;
        
        if (empty($domainId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing domain ID']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM admin_parked_domains WHERE id = ? AND admin_id = ?");
        $stmt->execute([$domainId, $session['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            logAction($pdo, 'admin', $session['user_id'], 'delete_domain', 'admin_parked_domains', $domainId, []);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Domain not found']);
        }
        
    } elseif ($action === 'delete_user_domain' && $session['role'] === 'user') {
        $domainId = $data['id'] ?? 0;
        
        if (empty($domainId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing domain ID']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM user_parked_domains WHERE id = ? AND user_id = ?");
        $stmt->execute([$domainId, $session['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            logAction($pdo, 'user', $session['user_id'], 'delete_domain', 'user_parked_domains', $domainId, []);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Domain not found']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
