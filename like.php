<?php
require 'config.php';

$video_id = $_POST['video_id'] ?? 0;
$user_ip  = $_SERVER['REMOTE_ADDR'];

// نحاول ندخل اللايك (INSERT IGNORE بيسمح بتجاهل التكرار)
$stmt = $pdo->prepare("
  INSERT IGNORE INTO likes (video_id, user_ip)
  VALUES (?, ?)
");
$stmt->execute([$video_id, $user_ip]);

// نرجّع عدد اللايكات الجديد
$stmt = $pdo->prepare("
  SELECT COUNT(*) AS total 
  FROM likes 
  WHERE video_id = ?
");
$stmt->execute([$video_id]);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo json_encode(['likes' => $total]);
