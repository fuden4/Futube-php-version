<?php
// ابدأ الجلسة في أعلى الصفحة دائماً
session_start();

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'config.php'; // تأكد أن ملف config.php موجود في نفس المجلد أو قم بتعديل المسار

$error_message = ''; // متغير لتخزين رسالة الخطأ

// التحقق إذا كان المستخدم قد سجل دخوله بالفعل، إذا كان كذلك، قم بتوجيهه لصفحة أخرى (مثلاً index.php)
if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); // افترض أن لديك صفحة index.php رئيسية
    exit;
}

// التحقق إذا تم إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // التحقق الأساسي من المدخلات
    if (empty($username) || empty($password)) {
        $error_message = "الرجاء إدخال اسم المستخدم وكلمة المرور.";
    } else {
        try {
            // استعلام لجلب المستخدم من قاعدة البيانات
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // التحقق من كلمة المرور
                if (password_verify($password, $user['password_hash'])) {
                    // كلمة المرور صحيحة, قم بإنشاء الجلسة
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // توجيه المستخدم إلى صفحة رئيسية أو لوحة تحكم
                    header("Location: index.php"); // يمكنك تغييرها إلى dashboard.php أو أي صفحة أخرى
                    exit;
                } else {
                    $error_message = "اسم المستخدم أو كلمة المرور غير صحيحة.";
                }
            } else {
                $error_message = "اسم المستخدم أو كلمة المرور غير صحيحة.";
            }
        } catch (PDOException $e) {
            // يمكنك تسجيل الخطأ في ملف أو عرضه بطريقة مناسبة للمطور
            // $error_message = "حدث خطأ أثناء الاتصال بقاعدة البيانات: " . $e->getMessage();
            $error_message = "حدث خطأ ما، يرجى المحاولة مرة أخرى لاحقًا.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="assests/style.css"> <style>
        /* --- نفس التنسيقات من register.php --- */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding-top: 80px; /* مساحة للهيدر إذا كان ثابتاً */
            padding-bottom: 20px;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1a2e 50%, #16213e 100%); 
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }

        .auth-container {
            background: rgba(26, 26, 46, 0.85); /* لون أغمق قليلاً من خلفية الجسم الرئيسية */
            backdrop-filter: blur(15px);
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 400px;
            max-width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .auth-container h2 {
            font-size: 2.2rem;
            margin-bottom: 25px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-container label {
            display: block;
            text-align: right;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }

        .auth-container input[type="text"],
        .auth-container input[type="password"] {
            width: 100%;
            padding: 12px 18px;
            margin-bottom: 18px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            color: #ffffff;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .auth-container input[type="text"]:focus,
        .auth-container input[type="password"]:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: #4ecdc4;
            box-shadow: 0 0 15px rgba(78, 205, 196, 0.25);
        }

        .auth-container input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .auth-container input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
        }
        .error-message { /* تم تعديل اسم الكلاس ليكون محدداً للخطأ */
            color: #ff6b6b;
            background-color: rgba(255, 107, 107, 0.15);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }
        
        .link-container { /* تم تعديل اسم الكلاس ليكون موحداً */
            display: block;
            margin-top: 20px;
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.7);
        }
        .link-container a {
            color: #4ecdc4;
            text-decoration: none;
            font-weight: bold;
        }
        .link-container a:hover {
            text-decoration: underline;
            color: #ff6b6b;
        }
    </style>
</head>
<body>
     <div class="auth-container">
        <h2>تسجيل الدخول</h2>

        <?php
        // هذا الجزء الخاص بالـ PHP يجب أن يكون في بداية الملف `login.php` كما تم شرحه سابقاً
        // if (!empty($error_message)):
        // echo '<div class="message error-message">' . htmlspecialchars($error_message) . '</div>';
        // endif;
        // هذا مجرد تذكير بمكان عرض رسالة الخطأ التي يتم التحكم بها من خلال PHP
        // تأكد أن متغير $error_message معرف ومستخدم في الجزء العلوي من ملف login.php
        if (isset($error_message) && !empty($error_message)) {
            echo '<div class="message error-message">' . htmlspecialchars($error_message) . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label for="username">اسم المستخدم:</label>
                <input type="text" name="username" id="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            <div>
                <label for="password">كلمة المرور:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div>
                <input type="submit" value="تسجيل الدخول">
            </div>
        </form>
        <div class="link-container">
            ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a>
        </div>
    </div>
</body>
</html>