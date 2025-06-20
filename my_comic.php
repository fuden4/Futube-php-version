<?php
session_start();
require 'config.php'; // استدعاء ملف الاتصال بقاعدة البيانات

// التحقق من أن المستخدم مسجل دخوله، فهذه صفحته الشخصية
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // هذا هو معرّف المستخدم الحالي

// --- معالجة طلب الحذف (يمكن للمستخدم حذف تعليقاته) ---
if (isset($_GET['delete_id'])) {
    $comment_id_to_delete = (int)$_GET['delete_id'];

    // نتأكد أن التعليق الذي يحاول حذفه يخصه هو فقط
    $stmt_delete = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt_delete->execute([$comment_id_to_delete, $user_id]);

    header('Location: my-comments.php'); // إعادة التوجيه لنفس الصفحة
    exit;
}

// --- جلب تعليقات المستخدم الحالي فقط ---
// **هذا هو التعديل الجوهري** -> أضفنا جملة WHERE c.user_id = ?
$stmt_comments = $pdo->prepare("
    SELECT 
        c.id, 
        c.comment_text, 
        c.created_at,
        v.id AS video_id,
        v.title AS video_title
    FROM 
        comments AS c
    LEFT JOIN 
        videos AS v ON c.video_id = v.id
    WHERE 
        c.user_id = ? -- << فلترة النتائج لعرض تعليقات هذا المستخدم فقط
    ORDER BY 
        c.created_at DESC
");
$stmt_comments->execute([$user_id]); // نمرر معرّف المستخدم هنا
$comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعليقاتي</title>
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
        .container { max-width: 900px; margin: 0 auto; }
        .page-header { text-align: center; margin-bottom: 2rem; font-size: 2.5rem; color: var(--primary-color-2); }
        .comments-list { display: flex; flex-direction: column; gap: 1.5rem; }
        .comment-item {
            background: var(--card-background);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .comment-main-content { flex-grow: 1; }
        .comment-body p { line-height: 1.6; margin-bottom: 1rem; word-break: break-word; }
        .comment-footer { font-size: 0.85rem; color: var(--text-muted); }
        .comment-footer a { color: var(--primary-color-3); text-decoration: none; }
        .delete-btn { background: transparent; border: 1px solid var(--error-color); color: var(--error-color); padding: 0.3rem 0.8rem; border-radius: 20px; cursor: pointer; }
        .no-comments { text-align: center; font-size: 1.2rem; color: var(--text-muted); padding: 3rem; background: var(--card-background); border-radius: 10px; }
        .back-link { display: block; text-align: center; margin-top: 2rem; color: var(--primary-color-3); text-decoration: none; }
    </style>
</head>
<body>

    <div class="container">
        <h1 class="page-header">قائمة تعليقاتي</h1>

        <?php if (empty($comments)): ?>
            <div class="no-comments">
                <p>لم تقم بكتابة أي تعليقات بعد.</p>
            </div>
        <?php else: ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-main-content">
                            <div class="comment-body">
                                <p><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                            </div>
                            <div class="comment-footer">
                                <?php if ($comment['video_title']): ?>
                                    <span>علّقت على فيديو: <a href="watch.php?id=<?= $comment['video_id'] ?>"><?= htmlspecialchars($comment['video_title']) ?></a></span>
                                <?php else: ?>
                                    <span>على فيديو: (فيديو محذوف)</span>
                                <?php endif; ?>
                                <span style="margin: 0 10px;">•</span>
                                <span>بتاريخ: <?= date('d M Y', strtotime($comment['created_at'])) ?></span>
                            </div>
                        </div>
                        <div>
                            <form action="my-comments.php?delete_id=<?= $comment['id'] ?>" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا التعليق؟');">
                                <button type="submit" class="delete-btn">حذف</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a href="index.php" class="back-link">العودة إلى الرئيسية</a>
    </div>

</body>
</html>