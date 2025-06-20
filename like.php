<?php
// like.php (النسخة المطورة)

// --- جديد: نبدأ الجلسة للوصول لبيانات المستخدم المسجل ---
session_start();
require 'config.php';

// نحدد أن الرد سيكون دائمًا بصيغة JSON
header('Content-Type: application/json');


// --- جديد: التحقق من هوية المستخدم ---
$is_logged_in = isset($_SESSION['user_id']);
$user_id      = $is_logged_in ? (int)$_SESSION['user_id'] : null;
$user_ip      = !$is_logged_in ? $_SERVER['REMOTE_ADDR'] : null;


// استلام البيانات وتأمينها (كما في الكود السابق)
$video_id = filter_input(INPUT_POST, 'video_id', FILTER_VALIDATE_INT);
// --- جديد: استلام نوع الإجراء (like أو unlike) من الواجهة الأمامية ---
$action   = $_POST['action'] ?? '';


// التحقق من صحة البيانات المستلمة
if (!$video_id || !in_array($action, ['like', 'unlike'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

try {
    // --- جديد: تحديد الحقل والقيمة التي سنتعامل معها بناءً على حالة تسجيل الدخول ---
    $condition_col = $is_logged_in ? 'user_id' : 'user_ip';
    $condition_val = $is_logged_in ? $user_id : $user_ip;

    // --- جديد: التعامل مع كل إجراء على حدة ---
    if ($action === 'like') {
        // نستخدم نفس طريقتك المفضلة INSERT IGNORE ولكن بشكل ديناميكي
        // سيتم تجاهل الإدخال إذا كان موجودًا بالفعل (بسبب UNIQUE KEY في قاعدة البيانات)
        $stmt = $pdo->prepare("INSERT IGNORE INTO likes (video_id, {$condition_col}) VALUES (:video_id, :condition_val)");
        $stmt->execute(['video_id' => $video_id, 'condition_val' => $condition_val]);

    } elseif ($action === 'unlike') {
        // هذا هو منطق إلغاء الإعجاب الذي كان مفقودًا
        $stmt = $pdo->prepare("DELETE FROM likes WHERE video_id = :video_id AND {$condition_col} = :condition_val");
        $stmt->execute(['video_id' => $video_id, 'condition_val' => $condition_val]);
    }

    // نرجّع عدد اللايكات الجديد (نفس الكود الأصلي لديك)
    $stmt_count = $pdo->prepare("SELECT COUNT(*) AS total FROM likes WHERE video_id = ?");
    $stmt_count->execute([$video_id]);
    $total_likes = (int)$stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

    // نرسل الرد النهائي للواجهة الأمامية
    echo json_encode(['success' => true, 'likes' => $total_likes]);

} catch (PDOException $e) {
    error_log('Like/Unlike error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}