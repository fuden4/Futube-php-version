<?php
require 'config.php'; // مسار ملف الاتصال بقاعدة البيانات
// session_start(); // يمكنك تفعيله إذا أردت ربط المشاهدات بالجلسات أو المستخدمين

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $video_id = $_POST['video_id'] ?? 0;
    $video_id = (int)$video_id;

    if ($video_id > 0) {
        try {
            $stmt_views = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
            $stmt_views->execute([$video_id]);

            if ($stmt_views->rowCount() > 0) {
                // تم التحديث بنجاح، يمكن إرسال عدد المشاهدات الجديد (اختياري)
                $stmt_get_views = $pdo->prepare("SELECT views FROM videos WHERE id = ?");
                $stmt_get_views->execute([$video_id]);
                $new_views = $stmt_get_views->fetchColumn();
                echo json_encode(['success' => true, 'message' => 'View recorded.', 'views' => $new_views]);
            } else {
                // لم يتم العثور على الفيديو أو لم يحدث تحديث
                echo json_encode(['success' => false, 'message' => 'Video not found or view not updated.']);
            }
        } catch (PDOException $e) {
            // يُفضل تسجيل الخطأ في سجلات الخادم بدلاً من طباعته مباشرة للمستخدم
            // error_log("Database error recording view: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid video ID.']);
    }
} else {
    // طريقة الطلب غير مسموح بها
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>