<?php
// /api/get_featured_videos.php

header('Content-Type: application/json');
require '../config.php';

try {
    $base_url_server = "http://192.168.0.249:8081/";

    $stmt_featured = $pdo->query("
        SELECT id, title, description, thumb_url, duration, views, category, release_year, rating, is_vimeo, is_featured
        FROM videos
        WHERE is_featured = 1
        ORDER BY RAND()
    ");
    $featured_videos = $stmt_featured->fetchAll(PDO::FETCH_ASSOC);

    // حلقة لتحديث الروابط والقيم المنطقية
    foreach ($featured_videos as &$video) { // استخدام '&' لتعديل المصفوفة الأصلية
        // بناء الرابط الكامل للصورة
        if (!empty($video['thumb_url']) && strpos($video['thumb_url'], 'http') !== 0) {
            $video['thumb_url'] = $base_url_server . $video['thumb_url'];
        }

        // ✅ تصحيح: تحويل قيم tinyint إلى boolean
        $video['is_vimeo'] = (bool)$video['is_vimeo'];
        $video['is_featured'] = (bool)$video['is_featured'];
    }

    echo json_encode($featured_videos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>