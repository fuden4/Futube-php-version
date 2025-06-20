<?php
// إعداد رأس HTTP للإشارة إلى أن الاستجابة هي JSON
header('Content-Type: application/json');

// تضمين ملف الاتصال بقاعدة البيانات
require '../config.php'; // تأكد من المسار الصحيح

// منع الوصول إذا لم يتم إرسال معرف الفيديو
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Video ID is required and must be a number."]);
    exit;
}

$id = (int)$_GET['id']; // تنظيف المدخلات

try {
    // 1) جلب بيانات الفيديو الحالي
    $stmt_video = $pdo->prepare("SELECT * FROM videos WHERE id = :id");
    $stmt_video->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_video->execute();
    $video = $stmt_video->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        http_response_code(404); // Not Found
        echo json_encode(["error" => "Video not found."]);
        exit;
    }

    // تهيئة رابط Vimeo إن وُجد (نفس المنطق من ملفك)
    if ($video['is_vimeo'] && strpos($video['video_url'], 'player.vimeo.com') === false) {
        if (preg_match('/vimeo\.com\/(\d+)/', $video['video_url'], $m)) {
            $video['video_url'] = "https://player.vimeo.com/video/" . $m[1];
        }
    }
    
    // إزالة بيانات حساسة أو غير ضرورية لتطبيق الجوال (اختياري)
    // unset($video['created_at']); // مثال: إذا كان لديك أعمدة لا تريد إرسالها

    // إرجاع بيانات الفيديو كـ JSON
    echo json_encode($video);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>