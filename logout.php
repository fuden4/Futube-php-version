<?php
session_start(); // ابدأ الجلسة للوصول إلى متغيراتها

// إلغاء تعيين جميع متغيرات الجلسة
$_SESSION = array();

// إذا كنت ترغب في تدمير الجلسة بالكامل، قم أيضًا بحذف ملف تعريف الارتباط الخاص بالجلسة.
// ملاحظة: هذا سيدمر الجلسة، وليس فقط بيانات الجلسة!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// أخيرًا، تدمير الجلسة.
session_destroy();

// توجيه المستخدم إلى الصفحة الرئيسية
header("Location: index.php");
exit;
?>