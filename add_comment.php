<?php
session_start();
require_once 'config.php';

// التحقق إذا كان المستخدم مسجلاً دخوله
if (!isset($_SESSION['user_id'])) {
    // يمكنك توجيهه لصفحة تسجيل الدخول أو عرض رسالة خطأ
    // header('Location: login.php?error=not_logged_in');
    // exit;
    // للتبسيط، سنفترض أن الواجهة الأمامية تمنع الإرسال إذا لم يكن مسجلاً
    // ولكن يجب دائماً التحقق من جانب الخادم
    die(json_encode(['success' => false, 'message' => 'يجب عليك تسجيل الدخول للتعليق.']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment_text = trim($_POST['comment_text']);
    $video_id = filter_input(INPUT_POST, 'video_id', FILTER_VALIDATE_INT);
    $parent_comment_id = filter_input(INPUT_POST, 'parent_comment_id', FILTER_VALIDATE_INT);
    if (empty($parent_comment_id)) { // إذا كانت القيمة فارغة أو ليست رقم صحيح، اجعلها NULL
        $parent_comment_id = NULL;
    }

    $user_id = $_SESSION['user_id'];

    if (empty($comment_text)) {
        // خطأ: التعليق فارغ
        // يمكنك حفظ رسالة خطأ في الجلسة وعرضها
        $_SESSION['comment_error'] = 'لا يمكن أن يكون التعليق فارغًا.';
        header('Location: watch.php?id=' . $video_id . '#comments-section');
        exit;
    }

    if (!$video_id) {
        $_SESSION['comment_error'] = 'معرّف الفيديو غير صالح.';
        header('Location: index.php'); // أو صفحة خطأ عامة
        exit;
    }


    try {
        $stmt = $pdo->prepare("INSERT INTO comments (video_id, user_id, comment_text, parent_comment_id) VALUES (:video_id, :user_id, :comment_text, :parent_comment_id)");
        $stmt->bindParam(':video_id', $video_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':comment_text', $comment_text);
        $stmt->bindParam(':parent_comment_id', $parent_comment_id, $parent_comment_id === NULL ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if ($stmt->execute()) {
            // تم إضافة التعليق بنجاح
            $_SESSION['comment_success'] = 'تم إضافة تعليقك بنجاح!';
        } else {
            $_SESSION['comment_error'] = 'حدث خطأ أثناء إضافة التعليق.';
        }
    } catch (PDOException $e) {
        // $_SESSION['comment_error'] = 'خطأ في قاعدة البيانات: ' . $e->getMessage(); // للمطور فقط
        $_SESSION['comment_error'] = 'حدث خطأ ما. الرجاء المحاولة مرة أخرى.';
    }

    // إعادة التوجيه إلى صفحة الفيديو مع الانتقال إلى قسم التعليقات
    header('Location: watch.php?id=' . $video_id . '#comments-section');
    exit;

} else {
    // إذا لم يكن الطلب POST، وجه إلى الصفحة الرئيسية أو صفحة خطأ
    header('Location: index.php');
    exit;
}
?>