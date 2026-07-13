<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}//Fetch notifications for the logged-in user

$user_id = (int) $_SESSION['user_id'];
$limit = filter_var($_GET['limit'] ?? 8, FILTER_VALIDATE_INT);
if (!$limit || $limit < 1 || $limit > 50) {
    $limit = 8;
}

try {
    $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $count_stmt->execute([$user_id]);
    $unread_count = (int) $count_stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT id, type, title, message, icon, action_url, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit);
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'unread_count' => $unread_count,
        'notifications' => $notifications,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch notifications.']);
}
