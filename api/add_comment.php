<?php
// /api/add_comment.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php'; // الاتصال بقاعدة البيانات

// التأكد أن الطلب هو من نوع POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Only POST method is accepted.']);
    exit;
}

// استقبال البيانات من التطبيق
$video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : null;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : null;
$parent_comment_id = isset($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;

// التحقق من أن البيانات المطلوبة موجودة
if (empty($video_id) || empty($user_id) || empty($comment_text)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Missing required fields: video_id, user_id, comment_text.']);
    exit;
}

try {
    $sql = "INSERT INTO comments (video_id, user_id, comment_text, parent_comment_id) VALUES (:video_id, :user_id, :comment_text, :parent_comment_id)";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':video_id', $video_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':comment_text', $comment_text, PDO::PARAM_STR);
    
    // parent_comment_id قد يكون null
    if ($parent_comment_id === 0 || $parent_comment_id === null) {
        $stmt->bindValue(':parent_comment_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindParam(':parent_comment_id', $parent_comment_id, PDO::PARAM_INT);
    }

    if ($stmt->execute()) {
        // تم إضافة التعليق بنجاح
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully.',
            'comment_id' => $pdo->lastInsertId() // إرجاع ID التعليق الجديد
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to execute statement.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    // التحقق من خطأ المفتاح الخارجي (إذا كان user_id أو video_id غير موجود)
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'error' => 'Invalid video_id or user_id.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>