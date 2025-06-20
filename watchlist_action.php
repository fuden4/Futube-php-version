<?php
// watchlist_action.php
session_start();
require 'config.php';

header('Content-Type: application/json');

// هذه الميزة للمستخدمين المسجلين فقط
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$video_id = filter_input(INPUT_POST, 'video_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? ''; // 'add' or 'remove'

if (!$video_id || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
    exit;
}

try {
    if ($action === 'add') {
        // نستخدم INSERT IGNORE للاضافة فقط في حال لم يكن السجل موجودًا بالفعل
        $stmt = $pdo->prepare("INSERT IGNORE INTO watchlist (user_id, video_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $video_id]);
        $message = 'تمت الإضافة إلى قائمة المشاهدة.';
        
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM watchlist WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
        $message = 'تمت الإزالة من قائمة المشاهدة.';
    }
    
    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    error_log('Watchlist error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات.']);
}