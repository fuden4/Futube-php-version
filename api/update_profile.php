<?php
// /api/update_profile.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// --- استلام البيانات ---
$user_id = $_POST['user_id'] ?? null;
$username = $_POST['username'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($user_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$update_fields = [];
$params = ['user_id' => $user_id];

// --- 1. التحقق من تحديث اسم المستخدم ---
if (!empty($username)) {
    // تحقق إذا كان اسم المستخدم الجديد مأخوذًا من قبل مستخدم آخر
    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt_check->execute([$username, $user_id]);
    if ($stmt_check->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'This username is already taken.']);
        exit;
    }
    $update_fields[] = "username = :username";
    $params['username'] = $username;
}

// --- 2. التحقق من تحديث كلمة المرور ---
if (!empty($new_password)) {
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $update_fields[] = "password_hash = :password_hash";
    $params['password_hash'] = $password_hash;
}

// --- 3. التحقق من تحديث صورة الملف الشخصي ---
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    // (يمكنك إضافة منطق لحذف الصورة القديمة من الخادم هنا إذا أردت)
    $target_dir = "../uploads/profile_pictures/";
    $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid('user_', true) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        $profile_image_url = "uploads/profile_pictures/" . $new_filename;
        $update_fields[] = "profile_image_url = :profile_image_url";
        $params['profile_image_url'] = $profile_image_url;
    }
}

// إذا لم يكن هناك أي شيء لتحديثه، أرجع رسالة نجاح
if (empty($update_fields)) {
    echo json_encode(['success' => true, 'message' => 'No changes were made.']);
    exit;
}

// --- بناء وتنفيذ استعلام التحديث ---
$sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = :user_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // بعد التحديث، قم بإرجاع بيانات المستخدم الجديدة
    $stmt_user = $pdo->prepare("SELECT id, username, profile_image_url FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $updatedUser = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // بناء الرابط الكامل للصورة
     if (!empty($updatedUser['profile_image_url']) && strpos($updatedUser['profile_image_url'], 'http') !== 0) {
        $base_url_server = "http://192.168.0.249:8081/";
        $updatedUser['profile_image_url'] = $base_url_server . $updatedUser['profile_image_url'];
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Profile updated successfully.',
        'user' => $updatedUser
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>