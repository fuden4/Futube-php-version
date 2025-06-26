<?php
// /api/record_view.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Only POST method is accepted.']);
    exit;
}

$video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : null;

if (empty($video_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing video_id parameter.']);
    exit;
}

try {
    // زيادة عدد المشاهدات بواحد
    $stmt = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = :video_id");
    $stmt->execute(['video_id' => $video_id]);
    
    // التحقق مما إذا كان الصف قد تأثر
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'View recorded.']);
    } else {
        // هذا يعني أن video_id غير موجود
        echo json_encode(['success' => false, 'message' => 'Video not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>