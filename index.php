<?php
session_start(); // ابدأ الجلسة أولاً
require 'config.php'; // ملف الاتصال بقاعدة البيانات

$is_logged_in = false;
$user_profile_image = 'images/default_avatar.png'; // مسار لصورة افتراضية (أنشئ هذا الملف أو غيّره)
$user_id = null;

if (isset($_SESSION['user_id'])) {
    $is_logged_in = true;
    $user_id = $_SESSION['user_id'];

    // جلب معلومات المستخدم (الصورة) من قاعدة البيانات
    $stmt_user = $pdo->prepare("SELECT username, profile_image_url FROM users WHERE id = :id");
    $stmt_user->bindParam(':id', $user_id);
    $stmt_user->execute();
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($user_data && !empty($user_data['profile_image_url']) && file_exists($user_data['profile_image_url'])) {
        $user_profile_image = $user_data['profile_image_url'];
    }
}

/*-- جلب الفيديوهات الأكثر مشاهدة --*/
$stmt_videos = $pdo->query("
    SELECT id, title, category, views, duration, thumb_url
    FROM videos
    ORDER BY views DESC
    LIMIT 50
"); //
$videos = $stmt_videos->fetchAll(PDO::FETCH_ASSOC); //

/*-- حوِّل النتائج لـ JSON --*/
$videosJson = json_encode($videos, JSON_UNESCAPED_UNICODE); //
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuden</title>
    <style>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1a2e 50%, #16213e 100%);
            color: #ffffff;
            overflow-x: hidden;
        }

        /* Header */
        .header {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { filter: drop-shadow(0 0 5px rgba(255, 107, 107, 0.5)); }
            to { filter: drop-shadow(0 0 20px rgba(78, 205, 196, 0.8)); }
        }

        .nav-links { /* Base style for nav links, used in mobile menu */
            display: flex;
            list-style: none;
            gap: 2rem; /* Will be overridden for column layout in mobile menu */
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-links a:hover {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        .search-bar {
            position: relative;
            /* Default margin for desktop, will be overridden in media query for mobile */
            margin-left: 2rem; 
        }

        .search-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 0.8rem 1.5rem;
            color: #ffffff;
            font-size: 1rem;
            width: 300px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: #4ecdc4;
            box-shadow: 0 0 20px rgba(78, 205, 196, 0.3);
        }



        /* Content Sections */
        .section {
            padding: 5rem 2rem;
            max-width: 1400px;
            margin: 80px auto 0; /* Added top margin to account for fixed header */
        }


        .section h2 {
            font-size: 2.5rem;
            margin-bottom: 3rem;
            text-align: center;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Video Grid */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .video-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            cursor: pointer;
        }

        .video-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: rgba(78, 205, 197, 0.17);
        }

        .video-thumbnail {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .play-button {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .play-button:hover {
            transform: scale(1.1);
            background: #ffffff;
        }

        .play-button::after {
            content: '▶';
            color: #333;
            font-size: 1.5rem;
            margin-left: 3px;
        }

        .video-info {
            padding: 1.5rem;
        }

        .video-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .video-meta {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        /* Categories */
        .categories {
            display: flex;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .category-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .category-button:hover,
        .category-button.active {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        /* Features Section */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            margin-top: 4rem;
        }

        .feature {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(78, 205, 196, 0.3);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feature h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem 2rem 1rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #4ecdc4;
        }

        /* Responsive */
        @media (max-width: 768px) {
            /* .nav-links {
                /* This rule was problematic as .nav-links is used inside mobile menu too.
                   Hiding specific desktop links is handled by hiding .user-actions-desktop
                   and the absence of explicit desktop .nav-links outside mobile menu in current HTML.
                */
                /* display: none; */ 
            /* } */

            .search-input {
                width: 150px; /* تصغير إضافي إذا لزم الأمر */
                 font-size: 0.9rem;
                 padding: 0.6rem 1rem;
            }
            
            .hero h1 { /* Assuming .hero exists elsewhere, kept for context if added */
                font-size: 2.5rem;
            }

            .hero p { /* Assuming .hero exists elsewhere */
                font-size: 1.1rem;
            }

            .video-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #4ecdc4;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
      .header .nav {
            position: relative; /* للسماح بتمركز القائمة المنسدلة */
        }

        .user-actions { /* This is for the user actions inside the mobile menu */
            display: flex;
            align-items: center;
            margin-left: 1rem; 
        }
        html[dir="rtl"] .user-actions {
            margin-left: 0;
            margin-right: 1rem; /* تعديل المسافة في RTL */
        }


        .profile-pic-header { /* Used in mobile slide-out menu */
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.5);
            transition: border-color 0.3s ease;
        }

        .profile-pic-header:hover {
            border-color: #4ecdc4;
        }
        
        /* Added style for desktop profile picture */
        .profile-pic-header-desktop {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.5);
            transition: border-color 0.3s ease;
        }
        .profile-pic-header-desktop:hover {
            border-color: #4ecdc4;
        }


        .dropdown-menu { /* This is for the "dropdown" inside mobile menu, styled to be always open */
            display: none; /* Default state, shown by .show or direct CSS */
            position: absolute;
            top: 60px; 
            left: 0; 
            background: rgba(20, 30, 50, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1001; 
            width: 200px;
            overflow: hidden;
        }
        /* Styles for the always-visible "dropdown" items within the mobile slide-out menu */
        .mobile-nav-menu .dropdown-menu {
            position: static; 
            display: block; /* Always visible inside mobile menu */
            width: 100%;
            background: transparent;
            backdrop-filter: none;
            border: none;
            box-shadow: none;
            margin-top: 0.5rem;
        }


        .dropdown-menu.show { /* For actual dropdown behavior if used elsewhere */
            display: block;
        }

        .dropdown-menu a {
            display: block;
            padding: 12px 20px;
            color: #ffffff;
            text-decoration: none;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-size: 0.95rem;
        }
        /* Specific styling for links within the mobile menu's "dropdown" section */
        .mobile-nav-menu .dropdown-menu a {
            padding: 10px 0; 
            font-size: 1rem;
        }


        .dropdown-menu a:hover {
            background-color: rgba(78, 205, 196, 0.2);
            color: #4ecdc4;
        }

        .auth-links-header a { /* For login/signup buttons */
            color: #ffffff;
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            margin-left: 1rem; 
            font-size: 0.9rem;
        }

        .auth-links-header a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        html[dir="rtl"] .dropdown-menu { /* For generic dropdown, if used elsewhere */
            left: auto; 
            right: 0; 
        }
        
        html[dir="rtl"] .auth-links-header a {
            margin-left: 0;
            margin-right: 1rem; 
        }
        

/* --- تنسيقات جديدة أو معدلة للتجاوب والهمبرغر --- */

.hamburger-menu {
    display: none; 
    background: none;
    border: none;
    cursor: pointer;
    padding: 10px;
    z-index: 1005; 
}

.hamburger-menu span {
    display: block;
    width: 25px;
    height: 3px;
    background-color: #ffffff;
    margin: 5px 0;
    transition: all 0.3s ease-in-out;
}

.mobile-nav-menu {
    display: none; 
    position: fixed;
    top: 0; 
    left: 0; 
    width: 80%; 
    max-width: 300px;
    height: 100vh;
    background: rgba(10, 14, 39, 0.98); 
    backdrop-filter: blur(15px);
    padding-top: 70px; /* Adjusted for header height */
    z-index: 1002; 
    overflow-y: auto;
    transition: transform 0.3s ease-in-out;
    transform: translateX(-100%); 
    box-shadow: 5px 0 15px rgba(0,0,0,0.2);
}
html[dir="rtl"] .mobile-nav-menu {
    left: auto;
    right: 0;
    transform: translateX(100%); 
    box-shadow: -5px 0 15px rgba(0,0,0,0.2);
}

.mobile-nav-menu.open {
    transform: translateX(0);
    display: block; /* Ensure it's displayed when open */
}

.mobile-nav-menu .nav-links { /* Nav links inside mobile menu */
    display: flex; 
    flex-direction: column;
    align-items: center; 
    gap: 1rem;
    padding: 2rem 0;
}
html[dir="rtl"] .mobile-nav-menu .nav-links {
    align-items: flex-start; 
    padding-right: 2rem;
}

.mobile-nav-menu .nav-links a {
    padding: 0.8rem 1.5rem;
    font-size: 1.1rem;
    width: 100%;
    text-align: center; 
}
html[dir="rtl"] .mobile-nav-menu .nav-links a {
    text-align: right;
}


.mobile-nav-menu .user-actions { /* User actions wrapper inside mobile menu */
    padding: 1rem 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex; 
    flex-direction: column; 
    align-items: center;
}
html[dir="rtl"] .mobile-nav-menu .user-actions {
    align-items: flex-end;
}

.mobile-nav-menu .profile-dropdown-container {
    position: relative; 
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center; 
}
html[dir="rtl"] .mobile-nav-menu .profile-dropdown-container {
    align-items: flex-end;
}

.mobile-nav-menu .profile-pic-header { 
    margin-bottom: 10px; 
}


.mobile-nav-menu .auth-links-header a { /* Login/signup inside mobile menu */
    display: block;
    width: fit-content; 
    margin: 10px auto; 
}
html[dir="rtl"] .mobile-nav-menu .auth-links-header a {
    margin: 10px 0 10px auto; 
}


/* تعديل موضع القائمة المنسدلة لسطح المكتب (Desktop) */
.user-actions-desktop .profile-dropdown-container {
    position: relative; 
}

.dropdown-menu-desktop { 
    display: none;
    position: absolute;
    top: calc(100% + 10px); 
    background: rgba(20, 30, 50, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    z-index: 1001;
    width: 200px;
    overflow: hidden;
}
html[dir="rtl"] .dropdown-menu-desktop {
    left: 0; 
    right: auto;
}
html[dir="ltr"] .dropdown-menu-desktop {
    right: 0; 
    left: auto;
}


.dropdown-menu-desktop.show {
    display: block;
}
.dropdown-menu-desktop a { 
    display: block;
    padding: 12px 20px;
    color: #ffffff;
    text-decoration: none;
    transition: background-color 0.3s ease, color 0.3s ease;
    font-size: 0.95rem;
}
.dropdown-menu-desktop a:hover {
    background-color: rgba(78, 205, 196, 0.2);
    color: #4ecdc4;
}


/* --- Media Query الرئيسية للتجاوب --- */
@media (max-width: 992px) { 
    /* This rule for .header .nav .nav-links is not needed if desktop links are not in main flow */
    /* .header .nav .nav-links { display: none; } */

    .user-actions-desktop { /* إخفاء قسم المستخدم لسطح المكتب */
        display: none;
    }

    .hamburger-menu { /* إظهار زر الهمبرغر */
        display: block; /* Changed to block for layout participation */
    }
    
    .search-bar {
        flex-grow: 1; /* اسمح لشريط البحث بأخذ المساحة المتاحة */
        margin-left: 1rem; /* Space after logo (LTR) / before hamburger (RTL) */
        margin-right: 1rem; /* Space before hamburger (LTR) / after logo (RTL) */
    }
    /* Removed: html[dir="rtl"] .search-bar specific margin overrides, base rule handles it */

    .logo {
        font-size: 1.8rem; /* تصغير الشعار قليلاً */
    }
     .search-input {
        width: 100%; /* اجعل شريط البحث يأخذ عرض الحاوية */
        max-width: 250px; /* أو حد أقصى مناسب */
    }

}

@media (max-width: 768px) {
    .search-input {
        width: 100%; /* Let flex-grow on parent and max-width handle it */
        max-width: 150px; /* Further reduce max width if needed */
        font-size: 0.9rem;
        padding: 0.6rem 1rem;
    }
     .header {
        padding: 0.8rem 1rem; /* تقليل حشو الهيدر */
    }
    .section {
        padding: 4rem 1rem; /* تقليل حشو الأقسام */
         margin-top: 60px; /* Adjust for smaller header */
    }
    .section h2 {
        font-size: 2rem; /* تصغير العناوين */
        margin-bottom: 2rem;
    }
    .video-grid {
        grid-template-columns: 1fr; /* عمود واحد لبطاقات الفيديو */
        gap: 1.5rem;
    }
     .video-thumbnail {
        height: 180px; /* تصغير الصورة المصغرة للفيديو */
    }
    .video-info {
        padding: 1rem;
    }
    .video-title {
        font-size: 1.1rem;
    }
    .category-button {
        padding: 0.6rem 1.5rem;
        font-size: 0.9rem;
    }
    .categories {
        gap: 0.5rem; /* تقليل الفجوة بين أزرار الفئات */
    }

}


/* تحريك زر الهمبرغر عند الفتح (X) */
.hamburger-menu.open span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}
.hamburger-menu.open span:nth-child(2) {
    opacity: 0;
}
.hamburger-menu.open span:nth-child(3) {
    transform: rotate(-45deg) translate(5px, -5px);
}

    </style>
</head>


<body>

<header class="header">
    <nav class="nav">
        <div class="logo">FUDEN</div>

        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="ابحث عن الفيديوهات...">
        </div>

        <button class="hamburger-menu" id="hamburgerMenu" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="mobile-nav-menu" id="mobileNavMenu">
            <ul class="nav-links">
                <li><a href="#home">الرئيسية</a></li>
                <li><a href="#trending">الأكثر مشاهدة</a></li>
                <li><a href="#categories">الفئات</a></li>
                <li><a href="about.html">حولنا</a></li>
            </ul>

            <div class="user-actions"> <?php if ($is_logged_in): ?>
                    <div class="profile-dropdown-container">
                        <img src="<?php echo htmlspecialchars($user_profile_image); ?>" alt="ملف شخصي" class="profile-pic-header" id="userMenuTriggerMobile"> <div class="dropdown-menu" id="userDropdownMobile"> <a href="watch_later.php">المشاهدة لاحقاً</a>
                            <a href="my_comments.php">تعليقاتي</a>
                            <a href="edit_profile.php">تعديل الملف الشخصي</a>
                            <a href="logout.php">تسجيل الخروج</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-links-header">
                        <a href="login.php">تسجيل الدخول</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="user-actions-desktop">
             <?php if ($is_logged_in): ?>
                <div class="profile-dropdown-container">
                    <img src="<?php echo htmlspecialchars($user_profile_image); ?>" alt="ملف شخصي" class="profile-pic-header-desktop" id="userMenuTriggerDesktop">
                    <div class="dropdown-menu-desktop" id="userDropdownDesktop">
                        <a href="watch_later.php">المشاهدة لاحقاً</a>
                        <a href="my_comments.php">تعليقاتي</a>
                        <a href="edit_profile.php">تعديل الملف الشخصي</a>
                        <a href="logout.php">تسجيل الخروج</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-links-header">
                    <a href="login.php">تسجيل الدخول</a>
                </div>
            <?php endif; ?>
        </div>

    </nav>
</header>

<section class="section" id="trending">
    <h2>الأكثر مشاهدة</h2>

    <div class="categories">
        <button class="category-button active" data-cat="all">الكل</button>
        <button class="category-button" data-cat="movies">أفلام</button>
        <button class="category-button" data-cat="series">مسلسلات</button>
        <button class="category-button" data-cat="documentaries">وثائقيات</button>
        <button class="category-button" data-cat="anime">أنمي</button>
        <button class="category-button" data-cat="live">بث مباشر</button>
    </div>

    <div class="video-grid" id="videoGrid"></div>
</section>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-links">
            <a href="privacy.html">سياسة الخصوصية</a>
            <a href="terms.html">شروط الاستخدام</a>
            <a href="connect.html">اتصل بنا</a>
        </div>
        <p>&copy; <?php echo date("Y"); ?> Fuden. جميع الحقوق محفوظة.</p>
    </div>
</footer>

<script>
/*--- البيانات التي جاءت من PHP ---*/
const videos = <?= $videosJson ?>; //

/*--- عناصر DOM ---*/
const videoGrid   = document.getElementById('videoGrid');
const searchInput = document.getElementById('searchInput');

/*--- وظائف مساعدة ---*/
function createCard(v) {
    const thumbUrl = v.thumb_url && v.thumb_url.trim() !== '' ? v.thumb_url : ''; //
    return `
      <div class="video-card" onclick="location.href='watch.php?id=${v.id}'">
        <div class="video-thumbnail">
            ${thumbUrl ? `<img src="${thumbUrl}" alt="${v.title}" onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #444 0%, #222 100%)';">` : '<div style="width:100%;height:100%;background:linear-gradient(135deg, #555 0%, #333 100%);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.3);">No Thumbnail</div>'}
            <div class="play-button"></div>
        </div>
        <div class="video-info">
            <div class="video-title">${v.title}</div>
            <div class="video-meta">${Intl.NumberFormat().format(v.views)} مشاهدة • ${v.duration}</div>
        </div>
      </div>`; //
}


function renderVideos(arr) {
    videoGrid.innerHTML = arr.map(createCard).join(''); //
}

/*--- فِلترة حسب الفئة ---*/
document.querySelectorAll('.category-button').forEach(btn => {
    btn.addEventListener('click', e => {
        document.querySelectorAll('.category-button').forEach(b=>b.classList.remove('active')); //
        btn.classList.add('active'); //

        const cat = btn.dataset.cat; //
        const filtered = (cat === 'all') ? videos : videos.filter(v => v.category === cat); //
        renderVideos(filtered); //
    });
});

/*--- البحث الفوري ---*/
searchInput.addEventListener('input', e => {
    const q = e.target.value.toLowerCase(); //
    const shown = videos.filter(v => v.title.toLowerCase().includes(q)); //
    renderVideos(shown); //
});

/*--- تلوين الهيدر عند التمرير ---*/
window.addEventListener('scroll', () => {
    const header = document.querySelector('.header'); //
    header.style.background = window.scrollY > 100
        ? 'rgba(0,0,0,0.95)'
        : 'rgba(0,0,0,0.9)'; //
});

/*--- تشغيل أولي ---*/
if (videos && videoGrid) { // Added a check for videoGrid existence
    renderVideos(videos);
}


/* --- JavaScript للقائمة المنسدلة للمستخدم (سطح المكتب) --- */
const userMenuTriggerDesktop = document.getElementById('userMenuTriggerDesktop');
const userDropdownDesktop = document.getElementById('userDropdownDesktop');

if (userMenuTriggerDesktop && userDropdownDesktop) {
    userMenuTriggerDesktop.addEventListener('click', (event) => {
        event.stopPropagation();
        userDropdownDesktop.classList.toggle('show');
    });

    window.addEventListener('click', (event) => {
        if (userDropdownDesktop.classList.contains('show') &&
            !userDropdownDesktop.contains(event.target) &&
            !userMenuTriggerDesktop.contains(event.target)) {
            userDropdownDesktop.classList.remove('show');
        }
    });
}

/* --- JavaScript لقائمة الهمبرغر للهاتف --- */
const hamburgerMenu = document.getElementById('hamburgerMenu');
const mobileNavMenu = document.getElementById('mobileNavMenu');
// const userMenuTriggerMobile = document.getElementById('userMenuTriggerMobile'); // ID on img tag inside mobile menu
// const userDropdownMobile = document.getElementById('userDropdownMobile');     // ID on dropdown div inside mobile menu

if (hamburgerMenu && mobileNavMenu) {
    hamburgerMenu.addEventListener('click', () => {
        hamburgerMenu.classList.toggle('open');
        mobileNavMenu.classList.toggle('open');
        // Optional: Toggle body scroll
        // document.body.style.overflow = mobileNavMenu.classList.contains('open') ? 'hidden' : '';
    });
}

// The dropdown inside the mobile menu is styled via CSS to be always visible when the mobile menu is open.
// So, no specific JS is needed to toggle it, unless that design changes.
// if (userMenuTriggerMobile && userDropdownMobile) {
    /*
    userMenuTriggerMobile.addEventListener('click', (event) => {
        event.stopPropagation();
        // userDropdownMobile.classList.toggle('show'); // Only if it's meant to be collapsible
    });
    */
// }


// إغلاق قائمة الهاتف عند الضغط على رابط فيها
if (mobileNavMenu) { // Check if mobileNavMenu exists
    mobileNavMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (mobileNavMenu.classList.contains('open')) {
                hamburgerMenu.classList.remove('open');
                mobileNavMenu.classList.remove('open');
                // document.body.style.overflow = ''; // Restore body scroll
            }
        });
    });
}


</script>
</body>
</html>