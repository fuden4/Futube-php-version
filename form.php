<?php
require 'config.php';

// تفعيل عرض الأخطاء للتطوير
error_reporting(E_ALL);
ini_set('display_errors', 1);

// التحقق من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// متغيرات لتتبع الملفات المرفوعة لحذفها عند الخطأ
$thumbUrl = '';
$videoUrl = '';
$isVimeo = false;

try {
    // 1. استلام وتنظيف البيانات الأساسية
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $vimeo_link = trim($_POST['vimeo_link'] ?? '');

    // --- جديد: استلام البيانات الجديدة ---
    $release_year = !empty($_POST['release_year']) ? (int)$_POST['release_year'] : null;
    $rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
    // التحقق من مربع "التميز". إذا تم تحديده، ستكون قيمته '1'، وإلا فلن يتم إرساله
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // التحقق من البيانات المطلوبة
    if (empty($title) || empty($category) || empty($duration)) {
        throw new Exception('البيانات الأساسية مفقودة (العنوان، الفئة، المدة)');
    }

    $isVimeo = !empty($vimeo_link);

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
    // ... (كود رفع الصورة المصغرة يبقى كما هو)
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($_FILES['thumb']['type'], $allowedImageTypes)) {
        throw new Exception('نوع الصورة غير مدعوم');
    }
    $thumbExtension = pathinfo($_FILES['thumb']['name'], PATHINFO_EXTENSION);
    $thumbName = uniqid() . '_' . time() . '.' . $thumbExtension;
    $thumbPath = "uploads/thumbs/$thumbName";
    if (!move_uploaded_file($_FILES['thumb']['tmp_name'], $thumbPath)) {
        throw new Exception('فشل في رفع الصورة المصغرة');
    }
    $thumbUrl = $thumbPath;


    // 3. معالجة الفيديو (كما هو)
    if ($isVimeo) {
        if (!filter_var($vimeo_link, FILTER_VALIDATE_URL)) { throw new Exception('رابط Vimeo غير صحيح'); }
        $videoUrl = $vimeo_link;
    } else {
        if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ملف الفيديو مطلوب');
        }
        // ... (كود رفع الفيديو المحلي يبقى كما هو)
        $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg']; // أنواع مدعومة بشكل أفضل على الويب
        if (!in_array($_FILES['video_file']['type'], $allowedVideoTypes)) {
            throw new Exception('نوع الفيديو غير مدعوم. استخدم mp4, webm, ogg');
        }
        $videoExtension = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
        $videoName = uniqid() . '_' . time() . '.' . $videoExtension;
        $videoPath = "uploads/videos/$videoName";
        if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $videoPath)) {
            throw new Exception('فشل في رفع الفيديو');
        }
        $videoUrl = $videoPath;
    }

    // --- جديد: التحقق إذا كان هناك فيديو آخر مميز بالفعل ---
    if ($is_featured === 1) {
        // قبل تمييز الفيديو الجديد، قم بإلغاء تمييز أي فيديو آخر
        $pdo->exec("UPDATE videos SET is_featured = 0 WHERE is_featured = 1");
    }

    // --- تعديل: تحديث جملة الإدخال في قاعدة البيانات ---
    $stmt = $pdo->prepare(
        "INSERT INTO videos (
            title, description, category, thumb_url, video_url, is_vimeo, duration, 
            release_year, rating, is_featured, created_at
         ) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    
    // --- تعديل: إضافة المتغيرات الجديدة إلى مصفوفة التنفيذ ---
    $result = $stmt->execute([
        $title, 
        $description, 
        $category, 
        $thumbUrl, 
        $videoUrl, 
        $isVimeo ? 1 : 0, 
        $duration,
        $release_year, // جديد
        $rating,       // جديد
        $is_featured   // جديد
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
    if (!empty($thumbUrl) && file_exists($thumbUrl)) { unlink($thumbUrl); }
    if (!empty($videoUrl) && !$isVimeo && file_exists($videoUrl)) { unlink($videoUrl); }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>