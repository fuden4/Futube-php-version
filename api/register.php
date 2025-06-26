<?php
// /api/register.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// --- استلام البيانات النصية ---
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

// التحقق إذا كان اسم المستخدم موجودًا بالفعل
$stmt_check = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
$stmt_check->execute([$username]);
if ($stmt_check->fetch()) {
    http_response_code(409); // Conflict
    echo json_encode(['success' => false, 'message' => 'Username already exists.']);
    exit;
}

// --- معالجة الصورة المرفوعة (الجزء الجديد) ---
$profile_image_url = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $target_dir = "../uploads/profile_pictures/"; // المسار الصحيح خارج مجلد api
    $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid('user_', true) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // التحقق من نوع الملف (اختياري لكن موصى به)
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($file_extension, $allowed_types)) {
        // محاولة نقل الملف المرفوع
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // تم الرفع بنجاح، قم بتخزين المسار النسبي
            $profile_image_url = "uploads/profile_pictures/" . $new_filename;
        }
    }
}

// --- تسجيل المستخدم في قاعدة البيانات ---
$pdo->beginTransaction(); // بدء معاملة لضمان تنفيذ العمليتين معًا

try {
    // 1. تشفير كلمة المرور وإضافة المستخدم بدون صورة أولاً
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt_insert = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt_insert->execute([$username, $password_hash]);
    
    // 2. إذا تم رفع صورة، قم بتحديث سجل المستخدم بالمسار
    if ($profile_image_url) {
        $user_id = $pdo->lastInsertId(); // الحصول على ID المستخدم الجديد
        $stmt_update = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
        $stmt_update->execute([$profile_image_url, $user_id]);
    }

    $pdo->commit(); // تأكيد المعاملة
    echo json_encode(['success' => true, 'message' => 'User registered successfully.']);

} catch (Exception $e) {
    $pdo->rollBack(); // التراجع عن المعاملة في حال حدوث خطأ
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to register user.', 'error' => $e->getMessage()]);
}
?>