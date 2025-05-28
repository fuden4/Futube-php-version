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

/*-- حوِّل النتائج لـ JSON حتى ياخذها الـ JS مباشرة --*/
$videosJson = json_encode($videos, JSON_UNESCAPED_UNICODE);
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

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
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
            margin: 0 auto;
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
            .nav-links {
                display: none;
            }

            .search-input {
                width: 200px;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
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
    
    </style>
</head>
<body>

<header class="header">
    <nav class="nav">
        <div class="logo">FUDEN</div>
        <ul class="nav-links">
            <li><a href="#home">الرئيسية</a></li>
            <li><a href="#trending">الأكثر مشاهدة</a></li>
            <li><a href="#categories">الفئات</a></li>
            <li><a href="about.html">حولنا</a></li>
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
                <a href="privacy.html">سياسة الخصوصية</a>
                <a href="terms.html">شروط الاستخدام</a>
                <a href="connect.html">اتصل بنا</a>
                
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
    // Check if thumb_url exists and is not empty
    const thumbUrl = v.thumb_url && v.thumb_url.trim() !== '' ? v.thumb_url : '';
    
    return `
      <div class="video-card" onclick="location.href='watch.php?id=${v.id}'">
        <div class="video-thumbnail">
            ${thumbUrl ? `<img src="${thumbUrl}" alt="${v.title}" onerror="this.style.display='none'">` : ''}
            <div class="play-button"></div>
        </div>
        <div class="video-info">
            <div class="video-title">${v.title}</div>
            <div class="video-meta">${Intl.NumberFormat().format(v.views)} Views • ${v.duration}</div>
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