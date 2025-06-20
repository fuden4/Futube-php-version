<?php
session_start();
require 'config.php';

// --- الخطوة 1: الأمان والتحقق من تسجيل الدخول ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// --- الخطوة 2: جلب بيانات قائمة المشاهدة من قاعدة البيانات ---
$stmt = $pdo->prepare("
    SELECT 
        v.id, v.title, v.thumb_url, v.duration, v.category
    FROM 
        watchlist AS w
    JOIN 
        videos AS v ON w.video_id = v.id
    WHERE 
        w.user_id = ?
    ORDER BY 
        w.created_at DESC
");
$stmt->execute([$user_id]);
$watchlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- جلب معلومات الهيدر (مثل الصورة الشخصية) ---
$user_profile_image = 'images/default_avatar.png';
$stmt_user = $pdo->prepare("SELECT profile_image_url FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
if ($user_data && !empty($user_data['profile_image_url']) && file_exists($user_data['profile_image_url'])) {
    $user_profile_image = $user_data['profile_image_url'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة المشاهدة</title>
    <style>
        :root {
            --primary-color-1: #ff6b6b;
            --primary-color-2: #4ecdc4;
            --background-dark-1: #0a0e27;
            --text-light: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.7);
            --card-background: rgba(255, 255, 255, 0.05);
            --border-light: rgba(255, 255, 255, 0.2);
            --error-color: #ff6b6b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: var(--background-dark-1);
            color: var(--text-light);
        }
        /* --- أنماط الهيدر (للتناسق مع باقي الموقع) --- */
        .header {
            background: rgba(10, 14, 39, 0.85);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        .logo { font-size: 2rem; font-weight: bold; color: var(--primary-color-2); cursor: pointer; }
        
        /* --- أنماط المحتوى الرئيسي --- */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .page-header {
            text-align: center;
            margin: 2rem 0 3rem;
            font-size: 2.5rem;
            color: var(--primary-color-2);
        }

        /* --- أنماط شبكة العرض --- */
        .watchlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        /* --- بطاقة الفيديو --- */
        .video-card {
            background: var(--card-background);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border-light);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .thumbnail-container {
            position: relative;
            cursor: pointer;
        }
        .video-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
        }
        .video-info {
            padding: 1rem;
        }
        .video-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .video-meta {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .remove-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 107, 107, 0.8);
            color: #fff;
            border: none;
            font-size: 1.2rem;
            line-height: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            opacity: 0;
        }
        .video-card:hover .remove-btn {
            opacity: 1;
        }
        .remove-btn:hover {
            background: var(--error-color);
            transform: scale(1.1);
        }
        
        /* رسالة القائمة الفارغة */
        .empty-watchlist {
            text-align: center;
            padding: 4rem;
            background: var(--card-background);
            border-radius: 10px;
            border: 1px dashed var(--border-light);
        }
        .empty-watchlist p {
            font-size: 1.2rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        .empty-watchlist a {
            background: var(--primary-color-2);
            color: #000;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .empty-watchlist a:hover {
            background: #fff;
        }

    </style>
</head>
<body>

<header class="header">
    <nav class="nav">
        <div class="logo" onclick="location.href='index.php'">FUDEN</div>
        </nav>
</header>

<div class="container">
    <h1 class="page-header">قائمة المشاهدة الخاصة بي</h1>

    <?php if (empty($watchlist_items)): ?>
        <div class="empty-watchlist">
            <p>قائمة المشاهدة فارغة حاليًا. تصفح الموقع وأضف ما يعجبك!</p>
            <a href="index.php">العودة إلى الرئيسية</a>
        </div>
    <?php else: ?>
        <div class="watchlist-grid">
            <?php foreach ($watchlist_items as $item): ?>
                <div class="video-card" id="video-card-<?= $item['id'] ?>">
                    <div class="thumbnail-container" onclick="location.href='watch.php?id=<?= $item['id'] ?>'">
                        <img src="<?= htmlspecialchars($item['thumb_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="video-meta">
                            <span><?= htmlspecialchars($item['category']) ?></span>
                            <?php if(!empty($item['duration'])): ?>
                                <span> • <?= htmlspecialchars($item['duration']) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <button class="remove-btn" data-video-id="<?= $item['id'] ?>" title="إزالة من القائمة">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // تحديد كل أزرار الإزالة
    const removeButtons = document.querySelectorAll('.remove-btn');

    // إضافة مستمع حدث لكل زر
    removeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const videoId = button.dataset.videoId;
            const card = document.getElementById('video-card-' + videoId);

            if (confirm('هل أنت متأكد من رغبتك في إزالة هذا الفيديو من قائمة المشاهدة؟')) {
                // إرسال طلب إلى الخادم لحذف العنصر
                fetch('watchlist_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `video_id=${videoId}&action=remove`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // إزالة البطاقة من الصفحة لإعطاء انطباع فوري بالاستجابة
                        if (card) {
                            card.style.transition = 'opacity 0.5s, transform 0.5s';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.9)';
                            setTimeout(() => {
                                card.remove();
                                // التحقق إذا كانت الشبكة فارغة بعد الحذف
                                const grid = document.querySelector('.watchlist-grid');
                                if (grid && grid.children.length === 0) {
                                    location.reload(); // إعادة تحميل الصفحة لعرض رسالة "القائمة فارغة"
                                }
                            }, 500);
                        }
                    } else {
                        alert(data.message || 'حدث خطأ أثناء الإزالة.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('خطأ في الاتصال بالخادم.');
                });
            }
        });
    });
});
</script>

</body>
</html>