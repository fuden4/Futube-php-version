<?php
require '../config.php';

$category = $_GET['category'] ?? 'all';
$search   = $_GET['search']   ?? '';

$sql  = "SELECT * FROM videos WHERE 1";
$args = [];

if ($category !== 'all') {
    $sql .= " AND category = ?";
    $args[] = $category;
}

if ($search !== '') {
    $sql .= " AND title LIKE ?";
    $args[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($args);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
