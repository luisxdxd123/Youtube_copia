<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$video_id = $input['video_id'] ?? 0;
$content = trim($input['content'] ?? '');

if (!$video_id || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Insertar comentario
    $stmt = $pdo->prepare("INSERT INTO comments (video_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$video_id, $user_id, $content]);
    
    // Actualizar contador de comentarios en la tabla videos
    $stmt = $pdo->prepare("UPDATE videos SET comments_count = (SELECT COUNT(*) FROM comments WHERE video_id = ? AND status = 'active') WHERE id = ?");
    $stmt->execute([$video_id, $video_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?> 