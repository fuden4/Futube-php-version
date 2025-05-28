<?php
require 'config.php';

// تفعيل عرض الأخطاء للتطوير
error_reporting(E_ALL);
ini_set('display_errors', 1);

// التحقق من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // 1. استلام وتنظيف البيانات
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $vimeo_link = trim($_POST['vimeo_link'] ?? '');

    // التحقق من البيانات المطلوبة
    if (empty($title) || empty($category) || empty($duration)) {
        throw new Exception('البيانات المطلوبة مفقودة');
    }

    $isVimeo = !empty($vimeo_link);
    $videoUrl = '';
    $thumbUrl = '';

    // إنشاء مجلدات الرفع إذا لم تكن موجودة
    if (!is_dir('uploads/thumbs')) {
        mkdir('uploads/thumbs', 0755, true);
    }
    if (!is_dir('uploads/videos')) {
        mkdir('uploads/videos', 0755, true);
    }

    // 2. رفع الصورة المصغّرة (مطلوبة)
    if (!isset($_FILES['thumb']) || $_FILES['thumb']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('الصورة المصغرة مطلوبة');
    }

    // التحقق من نوع الصورة
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $thumbFileType = $_FILES['thumb']['type'];
    
    if (!in_array($thumbFileType, $allowedImageTypes)) {
        throw new Exception('نوع الصورة غير مدعوم');
    }

    // رفع الصورة المصغرة
    $thumbExtension = pathinfo($_FILES['thumb']['name'], PATHINFO_EXTENSION);
    $thumbName = uniqid() . '_' . time() . '.' . $thumbExtension;
    $thumbPath = "uploads/thumbs/$thumbName";
    
    if (!move_uploaded_file($_FILES['thumb']['tmp_name'], $thumbPath)) {
        throw new Exception('فشل في رفع الصورة المصغرة');
    }
    $thumbUrl = $thumbPath;

    // 3. معالجة الفيديو
    if ($isVimeo) {
        // التحقق من صحة رابط Vimeo
        if (!filter_var($vimeo_link, FILTER_VALIDATE_URL)) {
            throw new Exception('رابط Vimeo غير صحيح');
        }
        $videoUrl = $vimeo_link;
    } else {
        // رفع فيديو محلي
        if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ملف الفيديو مطلوب إذا لم يتم توفير رابط Vimeo');
        }

        // التحقق من نوع الفيديو
        $allowedVideoTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm'];
        $videoFileType = $_FILES['video_file']['type'];
        
        if (!in_array($videoFileType, $allowedVideoTypes)) {
            throw new Exception('نوع الفيديو غير مدعوم');
        }

        $videoExtension = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
        $videoName = uniqid() . '_' . time() . '.' . $videoExtension;
        $videoPath = "uploads/videos/$videoName";
        
        if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $videoPath)) {
            throw new Exception('فشل في رفع الفيديو');
        }
        $videoUrl = $videoPath;
    }

    // 4. حفظ في قاعدة البيانات
    $stmt = $pdo->prepare(
        "INSERT INTO videos (title, description, category, thumb_url, video_url, is_vimeo, duration, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    
    $result = $stmt->execute([
        $title, 
        $description, 
        $category, 
        $thumbUrl, 
        $videoUrl, 
        $isVimeo ? 1 : 0, 
        $duration
    ]);

    if (!$result) {
        throw new Exception('فشل في حفظ البيانات في قاعدة البيانات');
    }

    // إرسال استجابة ناجحة
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'تم رفع الفيديو بنجاح',
        'video_id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    // في حالة وجود خطأ، احذف الملفات المرفوعة
    if (!empty($thumbUrl) && file_exists($thumbUrl)) {
        unlink($thumbUrl);
    }
    if (!empty($videoUrl) && !$isVimeo && file_exists($videoUrl)) {
        unlink($videoUrl);
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>