<?php
require 'config.php';                // PDO connection
$id = (int)($_GET['id'] ?? 0);       // SQL-Injection safe

// 1) Fetch current video data
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
$stmt->execute([$id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    // Try to redirect to a generic index or error page if video not found
    header('Location: index.php?error=video_not_found');
    exit;
}

$video_id = $video['id']; // Use the fetched ID to be sure

// 2) If Vimeo video, adjust URL
if ($video['is_vimeo'] && strpos($video['video_url'], 'player.vimeo.com') === false) {
    preg_match('/vimeo\.com\/(\d+)/', $video['video_url'], $match);
    if (isset($match[1])) {
        $video['video_url'] = "https://player.vimeo.com/video/" . $match[1];
    }
}

// 3) Fetch number of likes
$stmt_likes = $pdo->prepare("SELECT COUNT(*) AS total FROM likes WHERE video_id = ?");
$stmt_likes->execute([$video_id]);
$likes = $stmt_likes->fetch(PDO::FETCH_ASSOC)['total'];

// 4) Increment views counter (Consider doing this more robustly, e.g., per session)
$stmt_views = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
$stmt_views->execute([$video_id]);

// 5) Fetch recommended videos (e.g., 4 random videos from the same category, excluding the current one)
$current_category = $video['category'] ?? '';
if ($current_category) {
    $stmt_rec = $pdo->prepare("SELECT id, title, views, duration, thumb_url, category FROM videos WHERE id != :current_id AND category = :category ORDER BY RAND() LIMIT 4");
    $stmt_rec->execute(['current_id' => $video_id, 'category' => $current_category]);
} else { // Fallback if no category or to fetch any video
    $stmt_rec = $pdo->prepare("SELECT id, title, views, duration, thumb_url, category FROM videos WHERE id != :current_id ORDER BY RAND() LIMIT 4");
    $stmt_rec->execute(['current_id' => $video_id]);
}
$recommended_videos_data = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);

// If no category-specific recommendations found, try fetching any 4 random videos
if (empty($recommended_videos_data)) {
    $stmt_rec_fallback = $pdo->prepare("SELECT id, title, views, duration, thumb_url, category FROM videos WHERE id != :current_id ORDER BY RAND() LIMIT 4");
    $stmt_rec_fallback->execute(['current_id' => $video_id]);
    $recommended_videos_data = $stmt_rec_fallback->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watching - <?= htmlspecialchars($video['title'] ?? 'Fuden') ?></title>
   <style>
        :root {
            --primary-color-1: #ff6b6b;
            --primary-color-2: #4ecdc4;
            --primary-color-3: #45b7d1;
            --background-dark-1: #0a0e27;
            --background-dark-2: #1a1a2e;
            --background-dark-3: #16213e;
            --text-light: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.8);
            --border-light: rgba(255, 255, 255, 0.1);
            --card-background: rgba(255, 255, 255, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background-dark-1) 0%, var(--background-dark-2) 50%, var(--background-dark-3) 100%);
            color: var(--text-light);
            overflow-x: hidden;
        }

        /* Header */
        .header {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 1.5rem;
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
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: bold;
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2), var(--primary-color-3));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            cursor: pointer;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { filter: drop-shadow(0 0 5px rgba(255, 107, 107, 0.5)); }
            to { filter: drop-shadow(0 0 20px rgba(78, 205, 196, 0.8)); }
        }

        .nav-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-light);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: clamp(0.8rem, 2.5vw, 0.9rem);
        }

        .nav-button:hover {
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .back-button span:first-child { font-size: 1.2em; }


        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 1rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Video Player Section */
        .video-section {
            margin-bottom: 2rem;
        }

        .video-player {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-light);
        }
        .video-player.playing .video-play-button-overlay {
            display: none;
        }


        .video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; 
            background: linear-gradient(135deg,rgb(8, 8, 8) 0%,rgb(16, 16, 16) 100%);
        }

        .video-element {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .video-player:has(.custom-controls) .video-element {
             border-radius: 0;
        }

        /* Centered Play Button */
        .video-play-button-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: clamp(60px, 15vw, 100px); 
            height: clamp(60px, 15vw, 100px);
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5; 
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .video-play-button-overlay:hover {
            background: rgba(0, 0, 0, 0.7);
            transform: translate(-50%, -50%) scale(1.1);
        }
        .video-play-button-overlay svg {
            width: 50%;
            height: 50%;
            fill: white;
            margin-left: 5%; 
        }


        /* Custom video controls overlay for local videos */
        .custom-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.85));
            padding: 0.75rem 1rem 0.5rem; 
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 10; 
        }

        .video-player:hover .custom-controls,
        .video-player.controls-visible .custom-controls { /* Use class to control visibility */
            opacity: 1;
            visibility: visible;
        }

        .progress-bar {
            width: 100%;
            height: 5px; 
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            margin-bottom: 0.75rem; 
            cursor: pointer;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            border-radius: 3px;
            width: 0%;
        }

        .progress-handle {
            position: absolute;
            left: 0%; 
            top: -5px; 
            width: 14px; 
            height: 14px; 
            background: var(--primary-color-2);
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            transform: translateX(-50%); 
        }

        .progress-bar:hover .progress-handle,
        .progress-bar.dragging .progress-handle {
            opacity: 1;
        }

        .control-buttons {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap; 
            gap: 0.25rem; 
        }

        .control-left, .control-right {
            display: flex;
            align-items: center;
            gap: 0.25rem; 
        }

        .control-btn {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: clamp(0.9rem, 3vw, 1.1rem); 
            cursor: pointer;
            padding: 0.5rem; 
            border-radius: 50%;
            min-width: 36px; 
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .control-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .time-display {
            color: var(--text-light);
            font-size: clamp(0.75rem, 2.2vw, 0.85rem); 
            min-width: 80px; 
            text-align: center;
        }

        .volume-control {
            display: flex;
            align-items: center;
            gap: 0.3rem; 
        }

        .volume-slider {
            width: 60px; 
            height: 5px; 
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            cursor: pointer;
            position: relative;
        }

        .volume-fill {
            height: 100%;
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            border-radius: 3px;
            width: 70%;
        }
         .volume-handle { 
            position: absolute;
            left: 70%;
            top: -4.5px; 
            width: 14px; 
            height: 14px;
            background: var(--primary-color-2);
            border-radius: 50%;
            transform: translateX(-50%);
            opacity: 0;
        }
        .volume-slider:hover .volume-handle,
        .volume-slider.dragging .volume-handle {
            opacity: 1;
        }


        /* Video Info */
        .video-info {
            max-width: 1200px;
            margin: 2rem auto;
            background: var(--card-background);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid var(--border-light);
        }

        .video-title {
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: bold;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .video-meta {
            display: flex;
            gap: 1rem; 
            margin-bottom: 1.5rem;
            flex-wrap: wrap; 
            color: var(--text-muted);
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
        }
        .meta-item { display: flex; align-items: center; gap: 0.5rem; }

        .video-description {
            line-height: 1.7; 
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            font-size: clamp(0.9rem, 2.8vw, 1rem);
        }
        .video-description p { word-break: break-word; }

        .video-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .action-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-light);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: clamp(0.85rem, 2.5vw, 0.9rem);
        }
        .action-button:hover, .action-button.liked {
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        

        /* Recommended Videos */
        .recommended-section { max-width: 1200px; margin: 2rem auto; }
        .section-title {
            font-size: clamp(1.5rem, 5vw, 1.8rem);
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .recommended-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .recommended-card {
            background: var(--card-background);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
            cursor: pointer;
        }
        .recommended-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            border-color: rgba(78, 205, 196, 0.3);
        }
        .recommended-thumbnail {
            height: 0;
            padding-bottom: 56.25%; 
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #222; 
        }
         .recommended-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .recommended-thumbnail::after {
            content: '▶';
            color: rgba(255, 255, 255, 0.9);
            font-size: 2rem; 
            background: rgba(0,0,0,0.4); 
            width: 45px; 
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
        }
        .recommended-card:hover .recommended-thumbnail::after { transform: scale(1.1); }
        .recommended-info { padding: 1rem; }
        .recommended-title {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .recommended-meta { color: var(--text-muted); font-size: 0.85rem; }

        /* Quality Selector */
        .quality-selector { position: relative; display: inline-block; }
        .quality-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-light);
            padding: 0.4rem 0.8rem; 
            border-radius: 15px;
            cursor: pointer;
            font-size: clamp(0.75rem, 2.2vw, 0.85rem); 
            min-width: 60px; 
            text-align: center;
        }
        .quality-dropdown {
            position: absolute;
            bottom: calc(100% + 5px); 
            right: 0; 
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 10px;
            padding: 0.5rem;
            display: none;
            min-width: 100px; 
            border: 1px solid var(--border-light);
            z-index: 20; 
        }
        .quality-option {
            padding: 0.5rem 0.7rem; 
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.2s;
            text-align: center;
        }
        .quality-option:hover { background: rgba(255, 255, 255, 0.15); }
        .quality-option.active { background: var(--primary-color-2); color: var(--background-dark-1); font-weight: bold; }


        /* Loading Animation */
        .loading {
            display: inline-block; width: 20px; height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%; border-top-color: var(--primary-color-2);
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Notification Styles */
        .fuden-notification {
            position: fixed; top: 90px; left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9); color: white;
            padding: 1rem 2rem; border-radius: 10px;
            z-index: 10000; opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            border: 1px solid var(--primary-color-2);
            font-size: 0.9rem; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .fuden-notification.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .fuden-notification.hide { opacity: 0; transform: translateX(-50%) translateY(-20px); }


        /* Responsive */
        @media (max-width: 768px) {
            .header { padding: 0.8rem 1rem; }
            .nav-controls { gap: 0.5rem; }
            .nav-button { padding: 0.4rem 0.8rem; }
            .main-content { margin-top: 70px; }
            .video-info { padding: 1rem; }
            .video-meta { gap: 0.8rem; }
            .video-meta .meta-item { font-size: 0.8rem; }
            .custom-controls { padding: 0.6rem 0.8rem 0.4rem; }
            .progress-bar { height: 6px; margin-bottom: 0.6rem; }
            .progress-handle { width: 15px; height: 15px; top: -4.5px;}
            .control-btn { padding: 0.6rem; font-size: 1.1rem; min-width: 38px; min-height: 38px;}
            .time-display { font-size: 0.8rem; }
            .volume-slider { width: 70px; height: 6px;}
            .volume-handle { width: 15px; height: 15px; top: -4.5px;}
            .quality-button { padding: 0.5rem 0.9rem; font-size: 0.8rem;}
        }

        @media (max-width: 480px) { 
            .logo { font-size: 1.3rem; }
            .nav-button { padding: 0.3rem 0.6rem; font-size: 0.75rem; }
            
            .custom-controls { padding: 0.5rem 0.5rem 0.25rem; } 
            .progress-bar { height: 4px; margin-bottom: 0.5rem; }
            .progress-handle { width: 12px; height: 12px; top: -4px; } 
            .control-buttons { gap: 0.1rem; } 
            .control-left, .control-right { gap: 0.1rem; }
            .control-btn { padding: 0.35rem; font-size: 0.85rem; min-width: 30px; min-height: 30px;}
            .time-display { font-size: 0.7rem; min-width: 70px;}
            .volume-slider { width: 50px; height: 4px; }
            .volume-handle { width: 12px; height: 12px; top: -4px;}
            .quality-button { padding: 0.3rem 0.6rem; font-size: 0.7rem; min-width: 50px;}
            .quality-dropdown { min-width: 90px; }
            .quality-option { padding: 0.4rem 0.6rem; }

            .video-title { font-size: 1.3rem; }
            .video-description { font-size: 0.85rem; }
            .action-button { padding: 0.6rem 1.2rem; font-size: 0.8rem; }
            .section-title { font-size: 1.3rem; }
            .recommended-grid { grid-template-columns: 1fr; gap: 1rem; }
            .recommended-info { padding: 0.8rem; }
            .recommended-title { font-size: 0.9rem; }
            .fuden-notification { width: 90%; padding: 0.8rem 1rem; font-size: 0.85rem; top: 80px; }
        }
   </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo" onclick="goHome()">FUDEN</div>
            <div class="nav-controls">
                <a href="#" class="nav-button back-button" onclick="event.preventDefault(); goBack();" aria-label="Go Back">
                    <span>&larr;</span>
                    <span>Back</span>
                </a>
                <button class="nav-button" id="navFullscreenBtn" aria-label="Toggle Fullscreen">Fullscreen</button>
                <button class="nav-button" id="navShareBtn" aria-label="Share Video">Share</button>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <section class="video-section">
            <div class="video-player" id="videoPlayer">
                <?php if ($video['is_vimeo']): ?>
                    <div style="padding:56.25% 0 0 0;position:relative;">
                        <iframe src="<?= htmlspecialchars($video['video_url']) ?>?autoplay=1&transparent=0&title=0&byline=0&portrait=0&muted=0"
                                style="position:absolute;top:0;left:0;width:100%;height:100%;"
                                frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <div class="video-container">
                        <video id="localVideo" class="video-element" preload="metadata" poster="<?= htmlspecialchars($video['thumb_url'] ?? '') ?>" style="width:100%;">
                            <source src="<?= htmlspecialchars($video['video_url']) ?>" type="video/mp4">
                            Your browser does not support the video tag. 🙁
                        </video>
                        <button class="video-play-button-overlay" id="videoPlayButtonOverlay" aria-label="Play Video">
                            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                    </div>
                    <div class="custom-controls" id="customControls">
                        <div class="progress-bar" id="progressBar">
                            <div class="progress-fill" id="progressFill"></div>
                            <div class="progress-handle" id="progressHandle"></div>
                        </div>
                        
                        <div class="control-buttons">
                            <div class="control-left">
                                <button class="control-btn" id="playPauseBtn" aria-label="Play/Pause">▶️</button>
                                <button class="control-btn" id="skipBackwardBtn" aria-label="Skip Backward 10 seconds">⏪</button>
                                <button class="control-btn" id="skipForwardBtn" aria-label="Skip Forward 10 seconds">⏩</button>
                                <div class="time-display" id="timeDisplay">00:00 / 00:00</div>
                            </div>
                            
                            <div class="control-right">
                                <div class="volume-control">
                                    <button class="control-btn" id="muteBtn" aria-label="Mute/Unmute">🔊</button>
                                    <div class="volume-slider" id="volumeSlider">
                                        <div class="volume-fill" id="volumeFill"></div>
                                        <div class="volume-handle" id="volumeHandle"></div>
                                    </div>
                                </div>
                                
                                <div class="quality-selector">
                                    <button class="quality-button" id="qualityBtn" aria-label="Select Quality">Auto</button>
                                    <div class="quality-dropdown" id="qualityDropdown">
                                        <div class="quality-option" data-quality="1080p">1080p</div>
                                        <div class="quality-option" data-quality="720p">720p</div>
                                        <div class="quality-option" data-quality="480p">480p</div>
                                        <div class="quality-option active" data-quality="Auto">Auto</div>
                                    </div>
                                </div>
                                
                                <button class="control-btn" id="fullscreenBtn" aria-label="Toggle Fullscreen">⛶</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="video-info">
            <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>

            <div class="video-meta">
                <div class="meta-item">👁️ <?= number_format($video['views']) ?> views</div>
                <div class="meta-item">📅 <?= date('Y-m-d', strtotime($video['created_at'])) ?></div>
                <?php if(!empty($video['duration'])): ?>
                    <div class="meta-item">⏱️ <?= htmlspecialchars($video['duration']) ?></div>
                <?php endif; ?>
                <?php if(!empty($video['category'])): ?>
                    <div class="meta-item">🎬 <?= ucfirst(htmlspecialchars($video['category'])) ?></div>
                <?php endif; ?>
            </div>

            <div class="video-description">
                <p><?= nl2br(htmlspecialchars($video['description'])) ?></p>
            </div>

            <div class="video-actions">
               <button class="action-button" id="like-btn" data-video-id="<?= $video_id ?>">
                   <span id="like-icon">👍</span> <span id="like-text">Like</span> <span id="like-count" style="margin-left: 5px;"><?= $likes ?></span>
               </button>
                <button class="action-button" id="addToWatchlistBtn">➕ Add to Watchlist</button>
                <button class="action-button" id="downloadBtn">⬇️ Download</button>
                <button class="action-button" id="reportBtn">⚠️ Report</button>
            </div>
        </section>

        <section class="recommended-section">
            <h2 class="section-title">Recommended For You</h2>
            <div class="recommended-grid" id="recommendedGrid">
                </div>
        </section>
    </main>
<script>
    // Pass PHP data to JavaScript
    const currentVideoId = <?= json_encode($video_id) ?>;
    const recommendedVideosData = <?= json_encode($recommended_videos_data) ?>;
    const videoTitleForDownload = <?= json_encode($video['title'] ?? 'video') ?>;
    const localVideoSrc = <?= json_encode($video['is_vimeo'] ? '' : $video['video_url']) ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const likeBtn = document.getElementById('like-btn');
  const likeCountSpan = document.getElementById('like-count');
  const likeIconSpan = document.getElementById('like-icon');

  function updateLikeButtonVisuals(liked, count) {
      if (likeCountSpan) likeCountSpan.innerText = count;
      if (likeBtn && likeIconSpan) {
          if (liked) {
              likeBtn.classList.add('liked');
              likeIconSpan.textContent = '❤️';
          } else {
              likeBtn.classList.remove('liked');
              likeIconSpan.textContent = '👍';
          }
      }
  }
  
  const initialLikeStatus = localStorage.getItem('liked_video_' + currentVideoId) === 'true';
  let initialServerLikes = parseInt(likeCountSpan?.innerText || '0');
  updateLikeButtonVisuals(initialLikeStatus, initialServerLikes); 

  if (likeBtn) {
    likeBtn.addEventListener('click', () => {
        const videoId = likeBtn.dataset.videoId;
        const isCurrentlyLikedClient = localStorage.getItem('liked_video_' + videoId) === 'true'; 
        let currentClientLikes = parseInt(likeCountSpan.innerText); 

        const newOptimisticLikedState = !isCurrentlyLikedClient;
        const newOptimisticLikes = newOptimisticLikedState ? currentClientLikes + 1 : (currentClientLikes > 0 ? currentClientLikes - 1 : 0) ;
        
        updateLikeButtonVisuals(newOptimisticLikedState, newOptimisticLikes);

        fetch('like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: 'video_id=' + encodeURIComponent(videoId) + '&action=' + (newOptimisticLikedState ? 'like' : 'unlike')
        })
        .then(res => {
            if (!res.ok) { throw new Error('Network response was not ok: ' + res.statusText + ' status: ' + res.status); }
            return res.json();
        })
        .then(data => {
            if (data && typeof data.likes !== 'undefined') {
                const serverLikes = parseInt(data.likes);
                if (newOptimisticLikedState) { 
                    localStorage.setItem('liked_video_' + videoId, 'true');
                    updateLikeButtonVisuals(true, serverLikes);
                } else { 
                    localStorage.removeItem('liked_video_' + videoId);
                    updateLikeButtonVisuals(false, serverLikes);
                }
            } else {
                console.error("Like/Unlike failed: Response missing 'likes' field or invalid data.", data);
                showNotification('Action failed: Invalid server response.');
                updateLikeButtonVisuals(isCurrentlyLikedClient, currentClientLikes); 
            }
        })
        .catch(err => {
            console.error("Fetch error for like/unlike:", err);
            showNotification('Error connecting to the server for like action.');
            updateLikeButtonVisuals(isCurrentlyLikedClient, currentClientLikes);
        });
    });
  }

  document.getElementById('addToWatchlistBtn')?.addEventListener('click', () => showNotification('Added to Watchlist!'));
  document.getElementById('downloadBtn')?.addEventListener('click', downloadVideo);
  document.getElementById('reportBtn')?.addEventListener('click', () => showNotification('Video reported. Thank you.'));
  
  document.getElementById('navFullscreenBtn')?.addEventListener('click', toggleFullscreen);
  document.getElementById('navShareBtn')?.addEventListener('click', shareVideo);

});


// Video Player Logic
let video = null;
let videoPlayer = null;
let customControls = null;
let playPauseBtn = null;
let progressBar = null;
let progressFill = null;
let progressHandle = null;
let timeDisplay = null;
let muteBtn = null;
let volumeSlider = null;
let volumeFill = null;
let volumeHandle = null;
let qualityBtn = null;
let qualityDropdown = null;
let bigPlayButton = null;
let fullscreenVideoBtn = null; 

let controlsTimeout;
let lastToggleTime = 0; // For debouncing toggleControlsVisibility
const toggleDebounce = 100; // Milliseconds for debounce

// Variables for tap detection
let touchstartX = 0;
let touchstartY = 0;
let touchstartTime = 0;
const tapThreshold = 10; // Max pixels moved to be considered a tap
const tapTimeThreshold = 300; // Max ms to be considered a tap (increased slightly)


document.addEventListener('DOMContentLoaded', function() {
    video = document.getElementById('localVideo');
    videoPlayer = document.getElementById('videoPlayer');
    
    if (video && videoPlayer) { 
        customControls = document.getElementById('customControls');
        playPauseBtn = document.getElementById('playPauseBtn');
        progressBar = document.getElementById('progressBar');
        progressFill = document.getElementById('progressFill');
        progressHandle = document.getElementById('progressHandle');
        timeDisplay = document.getElementById('timeDisplay');
        muteBtn = document.getElementById('muteBtn');
        volumeSlider = document.getElementById('volumeSlider');
        volumeFill = document.getElementById('volumeFill');
        volumeHandle = document.getElementById('volumeHandle');
        qualityBtn = document.getElementById('qualityBtn');
        qualityDropdown = document.getElementById('qualityDropdown');
        bigPlayButton = document.getElementById('videoPlayButtonOverlay');
        fullscreenVideoBtn = document.getElementById('fullscreenBtn'); 

        document.getElementById('skipBackwardBtn')?.addEventListener('click', skipBackward);
        document.getElementById('skipForwardBtn')?.addEventListener('click', skipForward);
        playPauseBtn?.addEventListener('click', togglePlay);
        muteBtn?.addEventListener('click', toggleMute);
        qualityBtn?.addEventListener('click', toggleQualityDropdown);
        fullscreenVideoBtn?.addEventListener('click', toggleFullscreen); 
        bigPlayButton?.addEventListener('click', togglePlay);

        document.querySelectorAll('.quality-option').forEach(option => {
            option.addEventListener('click', () => changeQuality(option.dataset.quality, option));
        });

        setupVideoEventListeners();
        setupCustomControlsInteraction();
        video.removeAttribute('controls'); 
        updateVolumeDisplay();
        if (customControls) { 
            showControls(); 
            if (!video.paused) { 
                hideControlsWithDelay(5000);
            }
        }
    }
    
    renderRecommendedVideos();
});

function setupVideoEventListeners() {
    if (!video || !videoPlayer) return; 
    video.addEventListener('loadedmetadata', () => {
        updateTimeDisplay();
        updateVolumeDisplay();
        if (bigPlayButton) bigPlayButton.style.display = 'flex'; 
    });
    video.addEventListener('timeupdate', () => {
        updateProgress();
        updateTimeDisplay();
    });
    video.addEventListener('play', () => {
        if(playPauseBtn) playPauseBtn.textContent = '⏸️';
        if(playPauseBtn) playPauseBtn.setAttribute('aria-label', 'Pause');
        if (bigPlayButton) bigPlayButton.style.display = 'none';
        if(videoPlayer) videoPlayer.classList.add('playing');
        hideControlsWithDelay(); 
    });
    video.addEventListener('pause', () => {
        if(playPauseBtn) playPauseBtn.textContent = '▶️';
        if(playPauseBtn) playPauseBtn.setAttribute('aria-label', 'Play');
        if (bigPlayButton) bigPlayButton.style.display = 'flex';
        if(videoPlayer) videoPlayer.classList.remove('playing');
        showControls(); 
    });
    video.addEventListener('volumechange', updateVolumeDisplay);
    video.addEventListener('ended', () => {
        if(playPauseBtn) playPauseBtn.textContent = '▶️';
        if(playPauseBtn) playPauseBtn.setAttribute('aria-label', 'Play');
        if (bigPlayButton) bigPlayButton.style.display = 'flex';
        if(videoPlayer) videoPlayer.classList.remove('playing');
        showNotification('Video Ended');
        showControls();
    });
    video.addEventListener('error', (e) => {
        console.error('Video error:', e);
        showNotification('Error loading video');
        showControls();
    });

    videoPlayer.addEventListener('mouseenter', showControlsAndResetTimeout);
    videoPlayer.addEventListener('mousemove', showControlsAndResetTimeout);
    videoPlayer.addEventListener('mouseleave', () => { if(video && !video.paused) hideControlsWithDelay(); });
    
    // Click on video itself to toggle controls (for mouse)
    video.addEventListener('click', (e) => {
        if (e.target === video) { 
             toggleControlsVisibility();
        }
    });

    // Touch events for tap detection on video element
    video.addEventListener('touchstart', function(e) {
        if (e.target === video) {
            touchstartX = e.changedTouches[0].screenX;
            touchstartY = e.changedTouches[0].screenY;
            touchstartTime = new Date().getTime();
        }
    }, { passive: true });

    video.addEventListener('touchend', function(e) {
        if (e.target === video) {
            const touchendX = e.changedTouches[0].screenX;
            const touchendY = e.changedTouches[0].screenY;
            const touchendTime = new Date().getTime();

            const deltaX = Math.abs(touchstartX - touchendX);
            const deltaY = Math.abs(touchstartY - touchendY);
            const deltaTime = touchendTime - touchstartTime;

            if (deltaX < tapThreshold && deltaY < tapThreshold && deltaTime < tapTimeThreshold) {
                // It's a tap!
                e.preventDefault(); // Prevent click if it's a tap, to avoid double toggle
                toggleControlsVisibility();
            }
        }
    }, { passive: false });


    customControls?.addEventListener('click', (e) => {
        e.stopPropagation(); 
        showControlsAndResetTimeout(); 
    });
}

function toggleControlsVisibility() {
    const now = new Date().getTime();
    if (now - lastToggleTime < toggleDebounce) {
        return; // Debounce rapid calls
    }
    lastToggleTime = now;

    if (!customControls || !video || !videoPlayer) return;
    const isVisible = videoPlayer.classList.contains('controls-visible');

    if (isVisible) {
        if (qualityDropdown && qualityDropdown.style.display === 'block') {
            return; 
        }
        customControls.style.opacity = '0';
        customControls.style.visibility = 'hidden';
        videoPlayer.classList.remove('controls-visible');
        videoPlayer.style.cursor = 'none';
    } else {
        customControls.style.opacity = '1';
        customControls.style.visibility = 'visible';
        videoPlayer.classList.add('controls-visible');
        videoPlayer.style.cursor = 'default';
        if (!video.paused) { 
            hideControlsWithDelay();
        }
    }
}

function showControls() {
    if (!customControls || !videoPlayer) return;
    customControls.style.opacity = '1';
    customControls.style.visibility = 'visible';
    videoPlayer.classList.add('controls-visible');
    videoPlayer.style.cursor = 'default';
}

function hideControls() { 
    if (!customControls || !video || !videoPlayer) return;
    if (video.paused || (qualityDropdown && qualityDropdown.style.display === 'block')) {
        return;
    }
    customControls.style.opacity = '0';
    customControls.style.visibility = 'hidden';
    videoPlayer.classList.remove('controls-visible');
    videoPlayer.style.cursor = 'none';
}

function showControlsAndResetTimeout() {
    if (!video || !videoPlayer || !customControls) return;
    showControls(); 
    clearTimeout(controlsTimeout);
    if(!video.paused) { 
         hideControlsWithDelay();
    }
}

function hideControlsWithDelay(delay = 3000) {
   if (!video || !videoPlayer || !customControls ) return; 
   if (video.paused || (qualityDropdown && qualityDropdown.style.display === 'block')) {
       clearTimeout(controlsTimeout); 
       return;
   }
   clearTimeout(controlsTimeout);
   controlsTimeout = setTimeout(hideControls, delay);
}

function setupCustomControlsInteraction() {
    if(progressBar && progressHandle && video) {
        makeSliderDraggable(progressBar, progressHandle, (percentage) => {
            if (!isNaN(video.duration)) {
                video.currentTime = percentage * video.duration;
                updateProgress(); 
            }
        });
    }
    if(volumeSlider && volumeHandle && video) {
        makeSliderDraggable(volumeSlider, volumeHandle, (percentage) => {
            video.volume = percentage;
            video.muted = percentage === 0; 
        });
    }
}

function makeSliderDraggable(sliderElement, handleElement, callback) {
    let isDragging = false;
    function getPercentage(event) {
        const rect = sliderElement.getBoundingClientRect();
        let clientX = event.touches ? event.touches[0].clientX : event.clientX;
        let value = (clientX - rect.left) / rect.width;
        return Math.max(0, Math.min(1, value));
    }
    function onStart(event) {
        isDragging = true;
        sliderElement.classList.add('dragging');
        if (handleElement) handleElement.style.opacity = '1';
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onEnd);
        document.addEventListener('touchmove', onMove, { passive: false });
        document.addEventListener('touchend', onEnd);
        
        const percentage = getPercentage(event);
        callback(percentage);
        if (event.cancelable && event.type !== 'touchstart') event.preventDefault();
    }
    function onMove(event) {
        if (!isDragging) return;
        if (event.cancelable) event.preventDefault(); 
        const percentage = getPercentage(event);
        callback(percentage);
    }
    function onEnd() {
        if (!isDragging) return;
        isDragging = false;
        sliderElement.classList.remove('dragging');
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onEnd);
        document.removeEventListener('touchmove', onMove);
        document.removeEventListener('touchend', onEnd);
        showControlsAndResetTimeout(); 
    }
    sliderElement.addEventListener('mousedown', onStart);
    sliderElement.addEventListener('touchstart', onStart, { passive: true }); 
}

function togglePlay() {
    if (!video) return;
    if (video.paused || video.ended) {
        video.play().catch(e => {
            console.error('Play error:', e);
            showNotification('Error playing video.');
        });
    } else {
        video.pause();
    }
}

function skipBackward() {
    if (!video || isNaN(video.duration)) return;
    video.currentTime = Math.max(0, video.currentTime - 10);
    updateProgress();
}

function skipForward() {
    if (!video || isNaN(video.duration)) return;
    video.currentTime = Math.min(video.duration, video.currentTime + 10);
    updateProgress();
}

function toggleMute() {
    if (!video) return;
    video.muted = !video.muted;
}

function updateProgress() {
    if (!video || isNaN(video.duration) || !progressFill || !progressHandle) return;
    const pct = video.duration > 0 ? (video.currentTime / video.duration) * 100 : 0;
    progressFill.style.width = pct + '%';
    progressHandle.style.left = pct + '%';
}

function updateTimeDisplay() {
    if (!video || !timeDisplay || isNaN(video.duration)) {
         if(timeDisplay) timeDisplay.textContent = '00:00 / 00:00';
         return;
    };
    timeDisplay.textContent = `${formatTime(video.currentTime)} / ${formatTime(video.duration)}`;
}

function updateVolumeDisplay() {
    if (!video || !volumeFill || !volumeHandle || !muteBtn) return;
    const volumePercentage = video.muted ? 0 : video.volume * 100;
    volumeFill.style.width = volumePercentage + '%';
    volumeHandle.style.left = volumePercentage + '%';
    muteBtn.textContent = video.muted || video.volume === 0 ? '🔇' : '🔊';
    muteBtn.setAttribute('aria-label', video.muted || video.volume === 0 ? 'Unmute' : 'Mute');
}

function formatTime(seconds) {
    if (isNaN(seconds) || seconds < 0) return '00:00';
    const date = new Date(seconds * 1000);
    const hh = date.getUTCHours();
    const mm = date.getUTCMinutes().toString().padStart(2, '0');
    const ss = date.getUTCSeconds().toString().padStart(2, '0');
    if (hh > 0) { return `${hh.toString().padStart(2, '0')}:${mm}:${ss}`; }
    return `${mm}:${ss}`;
}

function toggleQualityDropdown() {
    if (!qualityDropdown || !qualityBtn) return; 
    const isOpen = qualityDropdown.style.display === 'block';
    qualityDropdown.style.display = isOpen ? 'none' : 'block';
    if (isOpen) { 
        showControlsAndResetTimeout(); 
    } else { 
        clearTimeout(controlsTimeout); 
        showControls(); 
    }
}

function changeQuality(quality, element) {
    if(!qualityBtn || !qualityDropdown) return;
    qualityBtn.textContent = quality;
    document.querySelectorAll('.quality-option.active').forEach(el => el.classList.remove('active'));
    if(element) element.classList.add('active');
    qualityDropdown.style.display = 'none'; 
    showNotification(`Quality set to: ${quality}`);
    showControlsAndResetTimeout(); 
}

async function toggleFullscreen() {
    const targetElement = videoPlayer; 
    if (!targetElement) return;

    if (!document.fullscreenElement) {
        try {
            await targetElement.requestFullscreen();
            if (screen.orientation && typeof screen.orientation.lock === 'function') {
                try {
                    await screen.orientation.lock('landscape-primary');
                } catch (err) {
                    console.warn('Could not lock screen orientation to landscape:', err);
                }
            }
        } catch (err) {
            console.error(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
            showNotification('Fullscreen not supported or permission denied.');
        }
    } else {
        try {
            await document.exitFullscreen();
        } catch (err) {
            console.error(`Error exiting fullscreen: ${err.message}`);
        }
    }
}

document.addEventListener('fullscreenchange', () => {
    const targetElement = videoPlayer; 
    if (!targetElement) return;
    const isFullscreen = !!document.fullscreenElement;
    targetElement.classList.toggle('fullscreen-active', isFullscreen); 
    if (!isFullscreen) {
        if (screen.orientation && typeof screen.orientation.unlock === 'function') {
            try {
                screen.orientation.unlock();
            } catch(err) {
                 console.warn('Could not unlock screen orientation:', err);
            }
        }
    }
});

function renderRecommendedVideos() {
    const grid = document.getElementById('recommendedGrid');
    if (!grid || !recommendedVideosData) return;
    grid.innerHTML = ''; 

    if (recommendedVideosData.length === 0) {
        grid.innerHTML = '<p style="color: var(--text-muted);">No recommendations available right now.</p>';
        return;
    }

    recommendedVideosData.forEach(recVideo => {
        const videoCard = document.createElement('div');
        videoCard.className = 'recommended-card';
        videoCard.onclick = () => loadVideo(recVideo.id);
        
        const thumbnailUrl = recVideo.thumb_url || `https://via.placeholder.com/300x169/2a2a3e/ffffff?text=${encodeURIComponent(recVideo.title)}`;
        const viewsText = recVideo.views ? `${Number(recVideo.views).toLocaleString()} views` : 'N/A views';
        const durationText = recVideo.duration || '';

        videoCard.innerHTML = `
            <div class="recommended-thumbnail" style="background-image: url('${htmlspecialchars(thumbnailUrl)}');">
                ${!recVideo.thumb_url ? '' : `<img src="${htmlspecialchars(thumbnailUrl)}" alt="${htmlspecialchars(recVideo.title)}" style="display:none;">`}
            </div>
            <div class="recommended-info">
                <div class="recommended-title" title="${htmlspecialchars(recVideo.title)}">${htmlspecialchars(recVideo.title)}</div>
                <div class="recommended-meta">${viewsText}${durationText ? ' &bull; ' + htmlspecialchars(durationText) : ''}</div>
            </div>
        `;
        grid.appendChild(videoCard);
    });
}

function htmlspecialchars(str) { 
    if (typeof str !== 'string') return String(str); 
    return str.replace(/[&<>"']/g, function (match) {
        const S = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return S[match];
    });
}

function downloadVideo() {
    if (!localVideoSrc) {
        showNotification('No downloadable source for this video.');
        return;
    }
    const filename = videoTitleForDownload.replace(/[^a-z0-9_.-]/gi, '_') + '.mp4';
    const a = document.createElement('a');
    a.href = localVideoSrc;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    showNotification('Download started...');
}

function shareVideo() {
    const shareData = {
        title: videoTitleForDownload,
        text: `Check out this video: ${videoTitleForDownload}`,
        url: window.location.href
    };
    if (navigator.share && typeof navigator.canShare === 'function' && navigator.canShare(shareData)) {
        navigator.share(shareData).then(() => showNotification('Video shared successfully!'))
        .catch(err => { if (err.name !== 'AbortError') { console.error('Share failed:', err); copyLinkToClipboard(); }});
    } else { copyLinkToClipboard(); }
}
function copyLinkToClipboard() {
     navigator.clipboard.writeText(window.location.href)
     .then(() => showNotification('Link copied to clipboard!'))
     .catch(err => { showNotification('Failed to copy link.'); console.error('Clipboard copy failed:', err); });
}

function goHome() { window.location.href = 'index.php'; }
function goBack() { window.history.length > 1 ? window.history.back() : goHome(); }
function loadVideo(videoId) {
    showNotification('Loading video...');
    window.location.href = `watch.php?id=${videoId}`;
}

let notificationTimeoutRef;
function showNotification(message) {
    const existingNotification = document.querySelector('.fuden-notification');
    if (existingNotification) { existingNotification.remove(); clearTimeout(notificationTimeoutRef); }

    const notification = document.createElement('div');
    notification.className = 'fuden-notification';
    notification.textContent = message;
    document.body.appendChild(notification);

    requestAnimationFrame(() => { notification.classList.add('show'); });
    notificationTimeoutRef = setTimeout(() => {
        notification.classList.remove('show');
        notification.classList.add('hide');
        setTimeout(() => { if (document.body.contains(notification)) document.body.removeChild(notification); }, 300);
    }, 3000);
}

document.addEventListener('keydown', (e) => {
    if (document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA' || document.activeElement.isContentEditable)) return;
    
    const isVideoRelatedFocus = videoPlayer?.contains(document.activeElement) || document.activeElement === video || document.activeElement === document.body;

    if (!video && !isVideoRelatedFocus && e.code !== 'Escape') return;


    const keyActionMap = {
        'Space': togglePlay, 'KeyK': togglePlay,
        'ArrowLeft': skipBackward, 'KeyJ': skipBackward,
        'ArrowRight': skipForward, 'KeyL': skipForward,
        'KeyM': toggleMute,
        'KeyF': toggleFullscreen, 
        'ArrowUp': () => { if (video) { video.volume = Math.min(1, video.volume + 0.1); updateVolumeDisplay(); }}, 
        'ArrowDown': () => { if (video) { video.volume = Math.max(0, video.volume - 0.1); updateVolumeDisplay(); }}, 
        'Escape': () => {
            if (document.fullscreenElement) toggleFullscreen(); 
            if (qualityDropdown && qualityDropdown.style.display === 'block') toggleQualityDropdown();
        }
    };
    
    if (keyActionMap[e.code]) {
        if (video && (isVideoRelatedFocus || e.code === 'Escape')) { // Allow escape even if video not primary focus
             e.preventDefault(); 
             keyActionMap[e.code](); 
             if (e.code !== 'Escape') { // Don't always show controls on escape if it was for quality dropdown
                showControlsAndResetTimeout();
             }
        } else if (e.code === 'Escape') { // Special handling for escape if video element doesn't exist but dropdown might
            keyActionMap[e.code]();
        }
    }
});

document.addEventListener('click', (e) => { 
    if (qualityDropdown && qualityBtn && !qualityBtn.contains(e.target) && !qualityDropdown.contains(e.target)) {
        if (qualityDropdown.style.display === 'block') {
            toggleQualityDropdown(); 
        }
    }
});

document.addEventListener('visibilitychange', () => { 
    if (video && !video.paused && document.hidden) video.pause();
});

</script>
</body>
</html>