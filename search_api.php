<?php
// search_api.php
header('Content-Type: application/json');
require 'config.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // نستخدم LIKE للبحث عن جزء من العنوان
    // نستخدم LIMIT لتحديد عدد النتائج (مثلاً 7 نتائج كحد أقصى)
    $stmt = $pdo->prepare("
        SELECT id, title, thumb_url 
        FROM videos 
        WHERE title LIKE ? 
        LIMIT 7
    ");
    
    // نضيف علامات % للبحث عن الكلمة في أي مكان في العنوان
    $stmt->execute(['%' . $query . '%']);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);

} catch (PDOException $e) {
    // في حالة الخطأ، أرسل مصفوفة فارغة
    error_log("Search API error: " . $e->getMessage());
    echo json_encode([]);
}