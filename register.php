<?php
session_start();
require_once 'config.php';

$error_message = '';
$success_message = '';

// إذا كان المستخدم مسجل دخوله بالفعل، وجهه إلى الصفحة الرئيسية
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $profile_image_url = NULL; // القيمة الافتراضية لمسار الصورة

    // ... (التحققات السابقة: الحقول الفارغة، تطابق كلمة المرور، طول كلمة المرور) ...
    // سأضيف الكود الجديد ضمن السياق الصحيح

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = "الرجاء ملء جميع الحقول المطلوبة (اسم المستخدم وكلمات المرور).";
    } elseif ($password !== $confirm_password) {
        $error_message = "كلمتا المرور غير متطابقتين.";
    } elseif (strlen($password) < 6) {
        $error_message = "يجب أن تتكون كلمة المرور من 6 أحرف على الأقل.";
    } else {
        // معالجة رفع الصورة
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/profile_pictures/'; // قم بإنشاء هذا المجلد!
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); // أنشئ المجلد إذا لم يكن موجوداً
            }

            $file_tmp_path = $_FILES['profile_image']['tmp_name'];
            $file_name = $_FILES['profile_image']['name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_type = $_FILES['profile_image']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $error_message = "صيغة الملف غير مسموح بها. الصيغ المسموحة: JPG, JPEG, PNG, GIF.";
            } elseif ($file_size > $max_file_size) {
                $error_message = "حجم الملف كبير جداً. الحد الأقصى 2MB.";
            } else {
                // إنشاء اسم فريد للملف لتجنب الكتابة فوق الملفات الموجودة
                $new_file_name = uniqid('user_', true) . '.' . $file_ext;
                $destination_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_path, $destination_path)) {
                    $profile_image_url = $destination_path; // حفظ المسار النسبي للصورة
                } else {
                    $error_message = "حدث خطأ أثناء رفع الصورة. حاول مرة أخرى.";
                }
            }
        } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['profile_image']['error'] != UPLOAD_ERR_OK) {
            // إذا كان هناك ملف ولكن حدث خطأ آخر غير "لم يتم رفع ملف"
            $error_message = "حدث خطأ غير متوقع أثناء تحميل الصورة. رمز الخطأ: " . $_FILES['profile_image']['error'];
        }

        // متابعة عملية التسجيل فقط إذا لم يكن هناك خطأ في رفع الصورة (أو لم يتم رفع صورة)
        if (empty($error_message)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
                $stmt->bindParam(':username', $username);
                $stmt->execute();

                if ($stmt->fetch()) {
                    $error_message = "اسم المستخدم هذا مسجل بالفعل. الرجاء اختيار اسم آخر.";
                    // إذا فشل التحقق من اسم المستخدم وكان هناك صورة مرفوعة، قد ترغب في حذفها
                    if ($profile_image_url && file_exists($profile_image_url)) {
                        unlink($profile_image_url);
                    }
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    // تعديل جملة الإدخال لتشمل profile_image_url
                    $insert_stmt = $pdo->prepare("INSERT INTO users (username, password_hash, profile_image_url) VALUES (:username, :password_hash, :profile_image_url)");
                    $insert_stmt->bindParam(':username', $username);
                    $insert_stmt->bindParam(':password_hash', $password_hash);
                    $insert_stmt->bindParam(':profile_image_url', $profile_image_url); // ربط قيمة مسار الصورة

                    if ($insert_stmt->execute()) {
                        $success_message = "تم إنشاء الحساب بنجاح! يمكنك الآن <a href='login.php'>تسجيل الدخول</a>.";
                    } else {
                        $error_message = "حدث خطأ أثناء إنشاء الحساب. الرجاء المحاولة مرة أخرى.";
                        if ($profile_image_url && file_exists($profile_image_url)) {
                            unlink($profile_image_url); // حذف الصورة إذا فشل الإدخال
                        }
                    }
                }
            } catch (PDOException $e) {
                $error_message = "حدث خطأ ما. الرجاء المحاولة مرة أخرى لاحقًا.";
                // $error_message = "خطأ في قاعدة البيانات: " . $e->getMessage(); // للمطور فقط
                if ($profile_image_url && file_exists($profile_image_url)) {
                    unlink($profile_image_url); // حذف الصورة إذا حدث خطأ في قاعدة البيانات
                }
            }
        }
    }
}
// ... (بقية كود PHP و HTML) ...
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد</title>
    <link rel="stylesheet" href="assests/style.css"> <style>
        /* تنسيقات إضافية خاصة بصفحة التسجيل لتتناسب مع style.css */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* لضمان أن الجسم يملأ الشاشة حتى لو المحتوى قليل */
            padding-top: 80px; /* لإعطاء مساحة للهيدر الثابت إذا كان موجوداً */
            padding-bottom: 20px;
        }

        .auth-container {
            background: rgba(26, 26, 46, 0.85); /* لون أغمق قليلاً من خلفية الجسم الرئيسية */
            backdrop-filter: blur(15px);
            padding: 30px 40px;
            border-radius: 20px; /* نفس الـ border-radius المستخدم في .video-card */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 400px;
            max-width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1); /* نفس حدود .video-card */
            text-align: center;
        }

        .auth-container h2 {
            font-size: 2.2rem; /* قريب من .section h2 */
            margin-bottom: 25px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4); /* نفس تدرج .logo و .section h2 */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-container label {
            display: block;
            text-align: right;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.8); /* لون نص فاتح */
            font-size: 0.95rem;
        }

        .auth-container input[type="text"],
        .auth-container input[type="password"] {
            width: 100%;
            padding: 12px 18px;
            margin-bottom: 18px;
            background: rgba(255, 255, 255, 0.1); /* مشابه لـ .search-input */
            border: 1px solid rgba(255, 255, 255, 0.2); /* مشابه لـ .search-input */
            border-radius: 25px; /* نفس .search-input و .category-button */
            color: #ffffff;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .auth-container input[type="text"]:focus,
        .auth-container input[type="password"]:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: #4ecdc4; /* لون التمييز من .search-input:focus */
            box-shadow: 0 0 15px rgba(78, 205, 196, 0.25); /* مشابه لـ .search-input:focus */
        }

        .auth-container input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4); /* نفس تدرج الأزرار الأخرى */
            color: white;
            border: none;
            border-radius: 25px; /* نفس الأزرار الأخرى */
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .auth-container input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4); /* نفس تأثير hover للأزرار */
        }

        .message {
            padding: 12px;
            border-radius: 10px; /* أقل دائرية من الحقول */
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
        }
        .error-message {
            color: #ff6b6b; /* لون خطأ يتناسب مع الثيم */
            background-color: rgba(255, 107, 107, 0.15);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }
        .success-message {
            color: #4ecdc4; /* لون نجاح يتناسب مع الثيم */
            background-color: rgba(78, 205, 196, 0.15);
            border: 1px solid rgba(78, 205, 196, 0.3);
        }
        .success-message a {
            color: #ff6b6b; /* لون رابط مميز داخل رسالة النجاح */
            font-weight: bold;
            text-decoration: none;
        }
        .success-message a:hover {
            text-decoration: underline;
        }

        .login-link {
            display: block;
            margin-top: 20px;
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.7);
        }
        .login-link a {
            color: #4ecdc4; /* لون الروابط المميز */
            text-decoration: none;
            font-weight: bold;
        }
        .login-link a:hover {
            text-decoration: underline;
            color: #ff6b6b;
        }
    </style>
</head>
<body >
    <div class="auth-container">
        <h2>إنشاء حساب جديد</h2>

        <?php if (!empty($error_message)): ?>
            <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (empty($success_message)): ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data"> {/* تعديل هنا */}
            <div>
                <label for="username">اسم المستخدم:</label>
                <input type="text" name="username" id="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            <div>
                <label for="password">كلمة المرور:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div>
                <label for="confirm_password">تأكيد كلمة المرور:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <div> {/* إضافة حقل الصورة هنا */}
                <label for="profile_image">صورة الملف الشخصي (اختياري):</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/png, image/jpeg, image/gif">
            </div>
            <div>
                <input type="submit" value="إنشاء الحساب">
            </div>
        </form>
        <?php endif; ?>

        <div class="login-link">
            لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
        </div>
    </div>
</body>
</html>