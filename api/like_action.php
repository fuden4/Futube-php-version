<?php
// /api/like_action.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Only POST method is accepted.']);
    exit;
}

$video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : null;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : null; // "like" or "unlike"

if (empty($video_id) || empty($user_id) || !in_array($action, ['like', 'unlike'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid parameters.']);
    exit;
}

try {
    if ($action === 'like') {
        // INSERT IGNORE يتجاهل الأمر إذا كان الإعجاب موجودًا بالفعل (بسبب القيد UNIQUE)
        $stmt = $pdo->prepare("INSERT IGNORE INTO likes (video_id, user_id) VALUES (:video_id, :user_id)");
        $stmt->execute(['video_id' => $video_id, 'user_id' => $user_id]);
    } elseif ($action === 'unlike') {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE video_id = :video_id AND user_id = :user_id");
        $stmt->execute(['video_id' => $video_id, 'user_id' => $user_id]);
    }

    // بعد تنفيذ الإجراء، قم بإرجاع العدد الجديد للإعجابات وحالة المستخدم
    // 1. جلب العدد الإجمالي للإعجابات
    $stmt_count = $pdo->prepare("SELECT COUNT(*) AS total_likes FROM likes WHERE video_id = :video_id");
    $stmt_count->execute(['video_id' => $video_id]);
    $total_likes = (int)$stmt_count->fetchColumn();

    // 2. التحقق مما إذا كان المستخدم الحالي قد أعجب بالفيديو
    $stmt_user = $pdo->prepare("SELECT 1 FROM likes WHERE video_id = :video_id AND user_id = :user_id");
    $stmt_user->execute(['video_id' => $video_id, 'user_id' => $user_id]);
    $user_liked = ($stmt_user->fetchColumn() !== false);

    echo json_encode([
        'success' => true,
        'total_likes' => $total_likes,
        'user_liked' => $user_liked
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>