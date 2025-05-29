<?php
session_start(); // تأكد أنها في أول سطر
require 'config.php';                // PDO connection

$id = (int)($_GET['id'] ?? 0);       // SQL-Injection safe

// --- تحديد حالة تسجيل الدخول ومعلومات المستخدم ---
$is_logged_in = false;
$current_user_id = null;
$current_user_profile_image = 'images/default_avatar.png'; // مسار افتراضي تأكد من وجوده

if (isset($_SESSION['user_id'])) { //  <--- تفعيل هذا الشرط
    $is_logged_in = true;
    $current_user_id = $_SESSION['user_id'];

    // جلب معلومات المستخدم (الصورة والاسم إذا احتجت) من قاعدة البيانات
    $stmt_curr_user = $pdo->prepare("SELECT username, profile_image_url FROM users WHERE id = :id");
    $stmt_curr_user->bindParam(':id', $current_user_id);
    $stmt_curr_user->execute();
    $current_user_data = $stmt_curr_user->fetch(PDO::FETCH_ASSOC);

    if ($current_user_data && !empty($current_user_data['profile_image_url']) && file_exists($current_user_data['profile_image_url'])) {
        $current_user_profile_image = $current_user_data['profile_image_url'];
    } elseif ($current_user_data) {
        // المستخدم موجود ولكن لا توجد صورة مخصصة، سيتم استخدام الصورة الافتراضية
    } else {
        // لم يتم العثور على بيانات المستخدم في قاعدة البيانات رغم وجود user_id في الجلسة
        // قد يكون هذا خطأ أو أن المستخدم تم حذفه، يمكنك معالجة هذه الحالة
        // مثلاً، تدمير الجلسة وإعادة التوجيه لصفحة تسجيل الدخول
        // session_destroy();
        // header('Location: login.php?error=user_not_found');
        // exit;
        // أو على الأقل إعادة is_logged_in إلى false
        $is_logged_in = false;
    }
}
// --- نهاية تحديد حالة تسجيل الدخول ---

// 1) Fetch current video data
$stmt_video = $pdo->prepare("SELECT * FROM videos WHERE id = ?"); // تم تغيير اسم المتغير لتجنب التضارب
$stmt_video->execute([$id]);
$video = $stmt_video->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    header('Location: index.php?error=video_not_found');
    exit;
}

$video_id = $video['id'];

// 2) If Vimeo video, adjust URL (الكود كما هو)
if ($video['is_vimeo'] && strpos($video['video_url'], 'player.vimeo.com') === false) {
    preg_match('/vimeo\.com\/(\d+)/', $video['video_url'], $match);
    if (isset($match[1])) {
        $video['video_url'] = "https://player.vimeo.com/video/" . $match[1];
    }
}

// 3) Fetch number of likes (الكود كما هو)
$stmt_likes = $pdo->prepare("SELECT COUNT(*) AS total FROM likes WHERE video_id = ?");
$stmt_likes->execute([$video_id]);
$likes = $stmt_likes->fetch(PDO::FETCH_ASSOC)['total'];

// 4) Increment views counter (الكود كما هو)
$stmt_views = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
$stmt_views->execute([$video_id]);

// 5) Fetch recommended videos (الكود كما هو)
$current_category = $video['category'] ?? '';
// ... (بقية كود جلب الفيديوهات المقترحة) ...
$recommended_videos_data = $stmt_rec->fetchAll(PDO::FETCH_ASSOC); // تأكد أن $stmt_rec مُعرّف بشكل صحيح من الشرط أعلاه

if (empty($recommended_videos_data)) {
    $stmt_rec_fallback = $pdo->prepare("SELECT id, title, views, duration, thumb_url, category FROM videos WHERE id != :current_id ORDER BY RAND() LIMIT 4");
    $stmt_rec_fallback->execute(['current_id' => $video_id]);
    $recommended_videos_data = $stmt_rec_fallback->fetchAll(PDO::FETCH_ASSOC);
}


// 6) Fetch comments for the video
// هذا الجزء صحيح في الكود الذي قدمته سابقًا (الملف الأول) ويجب أن يكون هنا
$stmt_comments = $pdo->prepare(
    "SELECT c.*, u.username, u.profile_image_url AS user_profile_image
     FROM comments c
     JOIN users u ON c.user_id = u.id
     WHERE c.video_id = :video_id
     ORDER BY c.created_at DESC"
);
$stmt_comments->execute(['video_id' => $video_id]);
$comments_data = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
// إذا كان الكود أعلاه موجودًا، فمتغير $comments_data سيكون مُهيأ بالتعليقات الفعلية أو مصفوفة فارغة.

?>