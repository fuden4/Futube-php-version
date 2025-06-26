<?php
// /api/get_user_details.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

// التأكد من أن user_id تم إرساله
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$user_id = (int)$_GET['user_id'];
$base_url_server = "http://192.168.0.249:8081/"; // الرابط الأساسي للخادم

try {
    // جلب بيانات المستخدم من قاعدة البيانات
    $stmt = $pdo->prepare("SELECT id, username, profile_image_url FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // إذا تم العثور على المستخدم
        // بناء الرابط الكامل للصورة الشخصية
        if (!empty($user['profile_image_url']) && strpos($user['profile_image_url'], 'http') !== 0) {
            $user['profile_image_url'] = $base_url_server . $user['profile_image_url'];
        }
        
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        // إذا لم يتم العثور على المستخدم
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>