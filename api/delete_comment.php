<?php
// /api/delete_comment.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// نفترض أن البيانات تأتي كـ JSON body
$data = json_decode(file_get_contents('php://input'), true);

$comment_id = $data['comment_id'] ?? null;
$user_id = $data['user_id'] ?? null;

if (empty($comment_id) || empty($user_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Comment ID and User ID are required.']);
    exit;
}

try {
    // نحذف التعليق فقط إذا كان comment_id و user_id متطابقين
    // هذا إجراء أمني لمنع مستخدم من حذف تعليقات مستخدم آخر
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = :comment_id AND user_id = :user_id");
    $stmt->execute(['comment_id' => $comment_id, 'user_id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        // تم الحذف بنجاح
        echo json_encode(['success' => true, 'message' => 'Comment deleted successfully.']);
    } else {
        // لم يتم العثور على التعليق أو المستخدم لا يملكه
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Could not delete comment.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>