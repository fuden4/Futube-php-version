<?php
// /api/get_video_details.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$base_url_server = "http://192.168.0.249:8081/";

if (isset($_GET['id'])) {
    $video_id = $_GET['id'];

    try {
        $sql = "SELECT id, title, description, category, thumb_url, video_url, is_featured, is_vimeo, duration, release_year, rating, views, created_at FROM videos WHERE id = :video_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':video_id', $video_id, PDO::PARAM_INT);
        $stmt->execute();
        $video = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($video) {
            // ✅ تأكيد: هذا الجزء يحول tinyint إلى boolean بشكل صحيح
            $video['is_featured'] = (bool)$video['is_featured'];
            $video['is_vimeo'] = (bool)$video['is_vimeo'];

            if (!empty($video['thumb_url']) && strpos($video['thumb_url'], 'http') !== 0) {
                $video['thumb_url'] = $base_url_server . $video['thumb_url'];
            }
            if (!empty($video['video_url']) && strpos($video['video_url'], 'http') !== 0) {
                $video['video_url'] = $base_url_server . $video['video_url'];
            }
            
            echo json_encode($video, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } else {
            http_response_code(404);
            echo json_encode(["error" => "Video not found."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database query failed: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Required parameter 'id' is missing."]);
}
?>