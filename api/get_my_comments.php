<?php
// /api/get_my_comments.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$user_id = (int)$_GET['user_id'];

try {
    // جلب تعليقات المستخدم مع عنوان الفيديو الذي تم التعليق عليه
    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.comment_text,
            c.created_at,
            v.title AS video_title
        FROM
            comments c
        JOIN
            videos v ON c.video_id = v.id
        WHERE
            c.user_id = :user_id
        ORDER BY
            c.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'comments' => $comments]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>