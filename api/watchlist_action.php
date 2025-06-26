<?php
// /api/watchlist_action.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Only POST method is accepted.']);
    exit;
}

$video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : null;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : null; // "add" or "remove"

if (empty($video_id) || empty($user_id) || !in_array($action, ['add', 'remove'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid parameters.']);
    exit;
}

try {
    if ($action === 'add') {
        // INSERT IGNORE يتجاهل الأمر إذا كان الفيديو موجودًا بالفعل في القائمة
        $stmt = $pdo->prepare("INSERT IGNORE INTO watchlist (video_id, user_id) VALUES (:video_id, :user_id)");
        $stmt->execute(['video_id' => $video_id, 'user_id' => $user_id]);
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM watchlist WHERE video_id = :video_id AND user_id = :user_id");
        $stmt->execute(['video_id' => $video_id, 'user_id' => $user_id]);
    }

    // التحقق مما إذا كان العنصر الآن في قائمة المشاهدة
    $stmt_check = $pdo->prepare("SELECT 1 FROM watchlist WHERE video_id = :video_id AND user_id = :user_id");
    $stmt_check->execute(['video_id' => $video_id, 'user_id' => $user_id]);
    $in_watchlist = ($stmt_check->fetchColumn() !== false);

    echo json_encode([
        'success' => true,
        'inWatchlist' => $in_watchlist
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>