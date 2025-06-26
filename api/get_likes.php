<?php
header('Content-Type: application/json');
require '../config.php'; 

if (!isset($_GET['video_id']) || !is_numeric($_GET['video_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Video ID is required and must be a number."]);
    exit;
}

$video_id = (int)$_GET['video_id'];

session_start(); 
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    $stmt_likes = $pdo->prepare("SELECT COUNT(*) AS total_likes FROM likes WHERE video_id = :video_id");
    $stmt_likes->bindParam(':video_id', $video_id, PDO::PARAM_INT);
    $stmt_likes->execute();
    $total_likes = (int)$stmt_likes->fetch(PDO::FETCH_ASSOC)['total_likes'];

    $user_liked = false;
    if ($user_id !== null) {
        $stmt_user_liked = $pdo->prepare("SELECT 1 FROM likes WHERE video_id = :video_id AND user_id = :user_id LIMIT 1");
        $stmt_user_liked->bindParam(':video_id', $video_id, PDO::PARAM_INT);
        $stmt_user_liked->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_user_liked->execute();
        $user_liked = ($stmt_user_liked->fetch() !== false);
    }

    echo json_encode([
        "success" => true,
        "total_likes" => $total_likes,
        "user_liked" => $user_liked
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
