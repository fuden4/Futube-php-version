<?php
header('Content-Type: application/json');
require '../config.php';

try {
    $stmt_featured = $pdo->query("
        SELECT id, title, description, thumb_url
        FROM videos
        WHERE is_featured = 1
        ORDER BY RAND()
    ");
    $featured_videos = $stmt_featured->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($featured_videos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>