<?php
require 'config.php';

/*-- جلب الفيديوهات الأكثر مشاهدة (تقدر تغيّر الشرط براحتك) --*/
$stmt = $pdo->query("
    SELECT id, title, category, views, duration, thumb_url
    FROM videos
    ORDER BY views DESC
    LIMIT 50
");
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*-- حوِّل النتائج لـ JSON حتى ياخذها الـ JS مباشرة --*/
$videosJson = json_encode($videos, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuden - منصة البث المتقدمة</title>
    <link rel="stylesheet" href="/assests/style.css">
</head>
<body>

<header class="header">
    <nav class="nav">
        <div class="logo">FUDEN</div>
        <ul class="nav-links">
            <li><a href="#home">الرئيسية</a></li>
            <li><a href="#trending">الأكثر مشاهدة</a></li>
            <li><a href="#categories">الفئات</a></li>
            <li><a href="#live">البث المباشر</a></li>
            <li><a href="#about">حولنا</a></li>
        </ul>
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="ابحث عن الفيديوهات...">
        </div>
    </nav>
</header>

<!-- Trending Section -->
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

    <div class="video-grid" id="videoGrid"><!-- البطاقات هنا --></div>
</section>

<!-- ====== باقي الأقسام ثابتة (Features + Footer) ====== -->
<!-- … -->
<footer class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="#privacy">سياسة الخصوصية</a>
                <a href="#terms">شروط الاستخدام</a>
                <a href="#support">الدعم التقني</a>
                <a href="#contact">اتصل بنا</a>
                
            </div>
            <p>&copy; 2024 Fuden. جميع الحقوق محفوظة.</p>
        </div>
    </footer>
    <script>
/*--- البيانات التي جاءت من PHP ---*/
const videos = <?= $videosJson ?>;

/*--- عناصر DOM ---*/
const videoGrid   = document.getElementById('videoGrid');
const searchInput = document.getElementById('searchInput');

/*--- وظائف مساعدة ---*/
function createCard(v) {
    return `
        <div class="video-card" onclick="location.href='watch.php?id=${v.id}'">
            <div class="video-thumbnail"
                 style="background:url('/${v.thumb_url}') center/cover"></div>
            <div class="video-info">
                <div class="video-title">${v.title}</div>
                <div class="video-meta">${Intl.NumberFormat().format(v.views)} مشاهدة • ${v.duration}</div>
            </div>
        </div>`;
}

function renderVideos(arr) {
    videoGrid.innerHTML = arr.map(createCard).join('');
}

/*--- فِلترة حسب الفئة ---*/
document.querySelectorAll('.category-button').forEach(btn => {
    btn.addEventListener('click', e => {
        document.querySelectorAll('.category-button').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');

        const cat = btn.dataset.cat;
        const filtered = (cat === 'all') ? videos : videos.filter(v => v.category === cat);
        renderVideos(filtered);
    });
});

/*--- البحث الفوري ---*/
searchInput.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    const shown = videos.filter(v => v.title.toLowerCase().includes(q));
    renderVideos(shown);
});

/*--- تلوين الهيدر عند التمرير ---*/
window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    header.style.background = window.scrollY > 100
        ? 'rgba(0,0,0,0.95)'
        : 'rgba(0,0,0,0.9)';
});

/*--- تشغيل أولي ---*/
renderVideos(videos);
</script>
</body>
</html>
