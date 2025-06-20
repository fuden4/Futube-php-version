<?php
// record_view.php
session_start();
require 'config.php'; // تأكد من أن هذا المسار صحيح

header('Content-Type: application/json');

// تحقق من أن الطلب من نوع POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$video_id = filter_input(INPUT_POST, 'video_id', FILTER_VALIDATE_INT);

if (!$video_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid video ID.']);
    exit;
}

try {
    // 1. زيادة عداد المشاهدات
    $stmt = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $stmt->execute([$video_id]);

    if ($stmt->rowCount() > 0) {
        // 2. (اختياري) جلب عدد المشاهدات الجديد لإرجاعه
        $stmt_views = $pdo->prepare("SELECT views FROM videos WHERE id = ?");
        $stmt_views->execute([$video_id]);
        $new_views = $stmt_views->fetchColumn();

        echo json_encode(['success' => true, 'message' => 'View recorded.', 'views' => $new_views]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Video not found or view not updated.']);
    }

} catch (PDOException $e) {
    // يمكنك تسجيل الخطأ في ملف logs بدلاً من عرضه
    error_log('Record view error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}