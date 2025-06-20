<?php
// 1. إعداد رأس HTTP للإشارة إلى أن الاستجابة هي JSON
header('Content-Type: application/json');

// 2. تضمين ملف الاتصال بقاعدة البيانات
// تأكد أن المسار صحيح بناءً على مكان ملف config.php
// إذا كان config.php في الجذر، والتطبيق في api/، فالمسار سيكون كالتالي:
require '../config.php'; // ارجع خطوة للخلف للوصول إلى config.php

// 3. جلب الفيديوهات من قاعدة البيانات
try {
    $stmt_videos = $pdo->query("
        SELECT id, title, category, views, duration, thumb_url
        FROM videos
        WHERE category IS NOT NULL AND category != ''
        ORDER BY category, views DESC
    "); 
    $videos = $stmt_videos->fetchAll(PDO::FETCH_ASSOC);

    // 4. تصنيف الفيديوهات بنفس المنطق المستخدم في index.php
    $categorized_videos = [];
    foreach ($videos as $video) {
        $category = $video['category'];
        if (!isset($categorized_videos[$category])) {
            $categorized_videos[$category] = [];
        }
        $categorized_videos[$category][] = $video;
    }

    // 5. إرجاع البيانات المصنفة كـ JSON
    // json_encode() ستقوم بتحويل مصفوفة PHP إلى JSON
    echo json_encode($categorized_videos);

} catch (PDOException $e) {
    // 6. التعامل مع الأخطاء: إرجاع رسالة خطأ بصيغة JSON
    http_response_code(500); // إعداد رمز حالة HTTP 500 (Internal Server Error)
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}

// لا داعي لإغلاق الاتصال إذا كنت تستخدم PDO، فهو يتعامل مع ذلك تلقائيًا عند انتهاء السكربت
// $pdo = null;

?>