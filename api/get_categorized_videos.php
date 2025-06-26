<?php
// /api/get_categorized_videos.php

header('Content-Type: application/json');
require '../config.php'; 

try {
    $base_url_server = "http://192.168.0.249:8081/";

    $stmt_videos = $pdo->query("
        SELECT id, title, category, views, duration, thumb_url, description, release_year, rating, is_vimeo, is_featured
        FROM videos
        WHERE category IS NOT NULL AND category != ''
        ORDER BY category, views DESC
    "); 
    $videos = $stmt_videos->fetchAll(PDO::FETCH_ASSOC);

    $categorized_videos = [];
    foreach ($videos as $video) {
        // بناء الرابط الكامل للصورة
        if (!empty($video['thumb_url']) && strpos($video['thumb_url'], 'http') !== 0) {
            $video['thumb_url'] = $base_url_server . $video['thumb_url'];
        }

        // ✅ تصحيح: تحويل قيم tinyint إلى boolean
        $video['is_vimeo'] = (bool)$video['is_vimeo'];
        $video['is_featured'] = (bool)$video['is_featured'];
        
        $category = $video['category'];
        if (!isset($categorized_videos[$category])) {
            $categorized_videos[$category] = [];
        }
        $categorized_videos[$category][] = $video;
    }

    echo json_encode($categorized_videos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>