<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$session = Auth::requireRole('user');
$pdo = getDbConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $dateRange = $_GET['date_range'] ?? 'today';
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    $whereClause = "WHERE user_id = ?";
    $params = [$session['user_id']];
    
    switch ($dateRange) {
        case 'today':
            $whereClause .= " AND DATE(created_at) = CURDATE()";
            break;
        case 'yesterday':
            $whereClause .= " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'weekly':
            $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'custom':
            if ($startDate && $endDate) {
                $whereClause .= " AND DATE(created_at) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
            break;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_clicks FROM redirect_logs $whereClause");
    $stmt->execute($params);
    $totalClicks = $stmt->fetch()['total_clicks'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as redirect_count FROM redirect_logs $whereClause AND decision = 'redirect'");
    $stmt->execute($params);
    $redirectCount = $stmt->fetch()['redirect_count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as normal_count FROM redirect_logs $whereClause AND decision = 'normal'");
    $stmt->execute($params);
    $normalCount = $stmt->fetch()['normal_count'];
    
    $stmt = $pdo->prepare("
        SELECT country_iso, COUNT(*) as count
        FROM redirect_logs
        $whereClause AND country_iso IS NOT NULL
        GROUP BY country_iso
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $countryStats = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT device_type, COUNT(*) as count
        FROM redirect_logs
        $whereClause AND device_type IS NOT NULL
        GROUP BY device_type
    ");
    $stmt->execute($params);
    $deviceStats = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM redirect_logs
        $whereClause
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $stmt->execute($params);
    $dailyStats = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT SUM(c.conversion_value) as total_earnings
        FROM conversions c
        INNER JOIN redirect_logs rl ON c.redirect_log_id = rl.id
        $whereClause
    ");
    $stmt->execute($params);
    $totalEarnings = $stmt->fetch()['total_earnings'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as conversion_count
        FROM conversions c
        INNER JOIN redirect_logs rl ON c.redirect_log_id = rl.id
        $whereClause
    ");
    $stmt->execute($params);
    $conversionCount = $stmt->fetch()['conversion_count'];
    
    echo json_encode([
        'total_clicks' => $totalClicks,
        'redirect_count' => $redirectCount,
        'normal_count' => $normalCount,
        'country_stats' => $countryStats,
        'device_stats' => $deviceStats,
        'daily_stats' => $dailyStats,
        'total_earnings' => $totalEarnings,
        'conversion_count' => $conversionCount
    ]);
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
