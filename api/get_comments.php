<?php
// /api/get_comments.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if (!isset($_GET['video_id']) || !is_numeric($_GET['video_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Video ID is required."]);
    exit;
}

$video_id = (int)$_GET['video_id'];
$base_url_server = "http://192.168.0.249:8081/"; // ✅ الرابط الأساسي للخادم

try {
    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.video_id AS videoId,
            c.comment_text AS commentText,
            c.created_at AS createdAt,
            u.username AS userName,
            u.profile_image_url AS profileImageUrl -- ✅ تمت إضافة حقل الصورة الشخصية
        FROM
            comments c
        JOIN
            users u ON c.user_id = u.id
        WHERE
            c.video_id = :video_id
        ORDER BY
            c.created_at DESC
    ");

    $stmt->bindParam(':video_id', $video_id, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ بناء الرابط الكامل للصورة لكل تعليق
    foreach ($comments as &$comment) { // استخدام & لتعديل المصفوفة مباشرة
        if (!empty($comment['profileImageUrl']) && strpos($comment['profileImageUrl'], 'http') !== 0) {
            $comment['profileImageUrl'] = $base_url_server . $comment['profileImageUrl'];
        }
    }

    echo json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>