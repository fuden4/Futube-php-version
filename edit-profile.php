<?php
session_start();
require 'config.php'; // استدعاء ملف الاتصال بقاعدة البيانات

// 1. التحقق من الأمان: هل المستخدم مسجل دخوله؟
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // إذا لم يكن مسجلاً، قم بتوجيهه لصفحة الدخول
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// 2. جلب بيانات المستخدم الحالية لعرضها في النموذج
$stmt = $pdo->prepare("SELECT username, profile_image_url FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. معالجة النموذج عند إرساله (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // استلام البيانات من النموذج
    $new_username = trim($_POST['username']);
    $new_password = $_POST['password'];
    $password_confirmation = $_POST['password_confirmation'];

    $update_fields = [];
    $update_values = [];

    // التحقق من اسم المستخدم الجديد
    if (!empty($new_username) && $new_username !== $user['username']) {
        // تأكد من أن اسم المستخدم الجديد غير محجوز
        $stmt_check_username = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check_username->execute([$new_username]);
        if ($stmt_check_username->fetch()) {
            $errors[] = "اسم المستخدم هذا محجوز بالفعل.";
        } else {
            $update_fields[] = "username = ?";
            $update_values[] = $new_username;
        }
    }

    // التحقق من كلمة المرور الجديدة
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $errors[] = "يجب أن تكون كلمة المرور 8 أحرف على الأقل.";
        } elseif ($new_password !== $password_confirmation) {
            $errors[] = "كلمتا المرور غير متطابقتين.";
        } else {
            // قم بتجزئة (hashing) كلمة المرور الجديدة
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password_hash = ?";
            $update_values[] = $hashed_password;
        }
    }

    // التحقق من الصورة الشخصية الجديدة
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $upload_dir = 'uploads/profile_pictures/'; // تأكد من وجود هذا المجلد
        
        if (!in_array($image['type'], $allowed_types)) {
            $errors[] = "نوع الملف غير مسموح به. (فقط jpeg, png, gif)";
        } elseif ($image['size'] > 2 * 1024 * 1024) { // 2MB limit
            $errors[] = "حجم الملف كبير جدًا. (الحد الأقصى 2MB)";
        } else {
            // إنشاء اسم فريد للملف لمنع الكتابة فوق الملفات الموجودة
            $file_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('avatar_', true) . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($image['tmp_name'], $destination)) {
                $update_fields[] = "profile_image_url = ?";
                $update_values[] = $destination;
            } else {
                $errors[] = "حدث خطأ أثناء رفع الصورة.";
            }
        }
    }

    // 4. تنفيذ التحديث في قاعدة البيانات إذا لم تكن هناك أخطاء
    if (empty($errors) && !empty($update_fields)) {
        $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $update_values[] = $user_id;
        
        $stmt_update = $pdo->prepare($sql);
        if ($stmt_update->execute($update_values)) {
            $success_message = "تم تحديث ملفك الشخصي بنجاح!";
            // إعادة جلب البيانات المحدثة لعرضها فوراً
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors[] = "حدث خطأ أثناء تحديث البيانات في قاعدة البيانات.";
        }
    } elseif (empty($update_fields) && empty($errors)) {
        $errors[] = "لم تقم بإجراء أي تغييرات.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الملف الشخصي</title>
    <style>
        :root {
            --primary-color-1: #ff6b6b;
            --primary-color-2: #4ecdc4;
            --primary-color-3: #45b7d1;
            --background-dark-1: #0a0e27;
            --background-dark-2: #1a1a2e;
            --text-light: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.8);
            --border-light: rgba(255, 255, 255, 0.2);
            --card-background: rgba(255, 255, 255, 0.05);
            --success-color: #28a745;
            --error-color: #dc3545;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background-dark-1) 0%, var(--background-dark-2) 100%);
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .edit-profile-container {
            width: 100%;
            max-width: 500px;
            background: var(--card-background);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-light);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .form-header { text-align: center; margin-bottom: 2rem; }
        .form-header h1 {
            font-size: 2rem;
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            color: var(--text-light);
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color-2);
            box-shadow: 0 0 10px rgba(78, 205, 196, 0.3);
        }
        .profile-image-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .profile-image-group img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color-2);
        }
        .profile-image-group input[type="file"] {
             border: none; background: transparent; padding: 0;
        }
        .submit-btn {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        .messages {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            list-style-type: none;
        }
        .messages.success { background-color: rgba(40, 167, 69, 0.3); border: 1px solid var(--success-color); }
        .messages.error { background-color: rgba(220, 53, 69, 0.3); border: 1px solid var(--error-color); }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--primary-color-3); text-decoration: none; }
    </style>
</head>
<body>

    <div class="edit-profile-container">
        <div class="form-header">
            <h1>تعديل الملف الشخصي</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <ul class="messages error">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="messages success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <form action="edit-profile.php" method="POST" enctype="multipart/form-data">
            <div class="profile-image-group">
                <img src="<?= htmlspecialchars($user['profile_image_url'] ?? 'images/default_avatar.png') ?>" alt="الصورة الشخصية الحالية">
                <div>
                    <label for="profile_image">تغيير الصورة الشخصية</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                </div>
            </div>

            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور الجديدة (اتركه فارغًا لعدم التغيير)</label>
                <input type="password" id="password" name="password">
            </div>

            <div class="form-group">
                <label for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
                <input type="password" id="password_confirmation" name="password_confirmation">
            </div>

            <button type="submit" class="submit-btn">حفظ التغييرات</button>
        </form>
        <a href="index.php" class="back-link">العودة إلى الرئيسية</a>
    </div>

</body>
</html>