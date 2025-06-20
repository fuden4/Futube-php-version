<?php
session_start();
require 'config.php'; // استدعاء ملف الاتصال بقاعدة البيانات

// --- الأمان ---
// هذه الصفحة يجب أن تكون متاحة للمدراء فقط في الموقع الحقيقي.
// سنكتفي حاليًا بالتحقق مما إذا كان المستخدم مسجلاً دخوله.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- معالجة طلب الحذف ---
if (isset($_GET['delete_id'])) {
    $comment_id_to_delete = (int)$_GET['delete_id'];
    
    // يجب إضافة تحقق إضافي هنا للتأكد من أن المستخدم الحالي هو مدير
    // if (is_admin($_SESSION['user_id'])) { ... }

    $stmt_delete = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt_delete->execute([$comment_id_to_delete]);

    // إعادة التوجيه لنفس الصفحة لتحديث القائمة وتجنب الحذف المتكرر عند تحديث الصفحة
    header('Location: comments.php');
    exit;
}

// --- جلب جميع التعليقات مع معلومات المستخدم والفيديو ---
// نستخدم LEFT JOIN لضمان عرض التعليق حتى لو تم حذف المستخدم أو الفيديو المرتبط به
$stmt_comments = $pdo->prepare("
    SELECT 
        c.id, 
        c.comment_text, 
        c.created_at,
        u.username,
        u.profile_image_url,
        v.id AS video_id,
        v.title AS video_title
    FROM 
        comments AS c
    LEFT JOIN 
        users AS u ON c.user_id = u.id
    LEFT JOIN 
        videos AS v ON c.video_id = v.id
    ORDER BY 
        c.created_at DESC
");
$stmt_comments->execute();
$comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التعليقات</title>
    <style>
        :root {
            --primary-color-1: #ff6b6b;
            --primary-color-2: #4ecdc4;
            --primary-color-3: #45b7d1;
            --background-dark-1: #0a0e27;
            --background-dark-2: #1a1a2e;
            --text-light: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.7);
            --border-light: rgba(255, 255, 255, 0.2);
            --card-background: rgba(255, 255, 255, 0.05);
            --error-color: #dc3545;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background-dark-1);
            color: var(--text-light);
            padding: 2rem;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            background: linear-gradient(45deg, var(--primary-color-1), var(--primary-color-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .comment-item {
            background: var(--card-background);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 1.5rem;
            display: flex;
            gap: 1.5rem;
        }
        .comment-avatar img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color-2);
        }
        .comment-content {
            flex-grow: 1;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }
        .comment-user {
            font-weight: bold;
            color: var(--primary-color-2);
        }
        .comment-date {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .comment-body p {
            line-height: 1.6;
            margin-bottom: 1rem;
            word-break: break-word;
        }
        .comment-footer {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .comment-footer a {
            color: var(--primary-color-3);
            text-decoration: none;
            transition: color 0.2s;
        }
        .comment-footer a:hover {
            color: var(--text-light);
        }
        .delete-btn {
            background: transparent;
            border: 1px solid var(--error-color);
            color: var(--error-color);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background-color 0.2s, color 0.2s;
        }
        .delete-btn:hover {
            background-color: var(--error-color);
            color: var(--text-light);
        }
        .no-comments {
            text-align: center;
            font-size: 1.2rem;
            color: var(--text-muted);
            padding: 3rem;
            background: var(--card-background);
            border-radius: 10px;
        }
        .back-link { display: block; text-align: center; margin-top: 2rem; color: var(--primary-color-3); text-decoration: none; }
    </style>
</head>
<body>

    <div class="container">
        <h1 class="page-header">إدارة التعليقات</h1>

        <?php if (empty($comments)): ?>
            <div class="no-comments">
                <p>لا توجد أي تعليقات لعرضها حاليًا.</p>
            </div>
        <?php else: ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-avatar">
                            <img src="<?= htmlspecialchars($comment['profile_image_url'] ?? 'images/default_avatar.png') ?>" alt="الصورة الرمزية">
                        </div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <div>
                                    <span class="comment-user"><?= htmlspecialchars($comment['username'] ?? 'مستخدم محذوف') ?></span>
                                    <span class="comment-date"><?= date('d M Y, H:i', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <form action="comments.php?delete_id=<?= $comment['id'] ?>" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا التعليق نهائيًا؟');">
                                    <button type="submit" class="delete-btn">حذف</button>
                                </form>
                            </div>
                            <div class="comment-body">
                                <p><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                            </div>
                            <div class="comment-footer">
                                <?php if ($comment['video_title']): ?>
                                    <span>على فيديو: <a href="watch.php?id=<?= $comment['video_id'] ?>"><?= htmlspecialchars($comment['video_title']) ?></a></span>
                                <?php else: ?>
                                    <span>على فيديو: (فيديو محذوف)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a href="index.php" class="back-link">العودة إلى الرئيسية</a>
    </div>

</body>
</html>