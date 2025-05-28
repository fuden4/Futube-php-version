<?php
// إعداد الإيميل المستقبل
$to = "mohammeninja@gmail.com";

// استلام بيانات النموذج
$name    = htmlspecialchars(trim($_POST['name']));
$email   = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$message = htmlspecialchars(trim($_POST['message']));

// إعداد العنوان (subject) والـ headers
$subject = "رسالة جديدة من Fuden - من: $name";
$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// الرسالة الكاملة
$body = "الاسم: $name\n";
$body .= "البريد الإلكتروني: $email\n\n";
$body .= "الرسالة:\n$message\n";

// إرسال الإيميل
$sent = mail($to, $subject, $body, $headers);

// التوجيه بعد الإرسال
if ($sent) {
    echo "<script>alert('تم إرسال رسالتك بنجاح. شكراً لتواصلك!'); window.history.back();</script>";
} else {
    echo "<script>alert('حدث خطأ أثناء إرسال الرسالة. حاول مرة أخرى لاحقاً.'); window.history.back();</script>";
}
?>
