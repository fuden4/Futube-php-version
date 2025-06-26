<?php
// /api/login.php

header('Content-Type: application/json; charset=utf-8');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

// البحث عن المستخدم
$stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// التحقق من كلمة المرور
if ($user && password_verify($password, $user['password_hash'])) {
    // كلمة المرور صحيحة
    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username']
        ]
    ]);
} else {
    // بيانات الدخول غير صحيحة
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
}
?>