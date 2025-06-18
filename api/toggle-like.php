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
$type = $input['type'] ?? '';

if (!$video_id || !in_array($type, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Verificar si ya existe un like/dislike del usuario
    $stmt = $pdo->prepare("SELECT type FROM likes WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$user_id, $video_id]);
    $existing = $stmt->fetchColumn();
    
    if ($existing === $type) {
        // Si es el mismo tipo, eliminar el like/dislike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
        $user_like = null;
    } elseif ($existing) {
        // Si existe pero es diferente tipo, actualizar
        $stmt = $pdo->prepare("UPDATE likes SET type = ? WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$type, $user_id, $video_id]);
        $user_like = $type;
    } else {
        // Si no existe, crear nuevo
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, video_id, type) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $video_id, $type]);
        $user_like = $type;
    }
    
    // Obtener contadores actualizados
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM likes WHERE video_id = ? AND type = 'like') as likes_count,
        (SELECT COUNT(*) FROM likes WHERE video_id = ? AND type = 'dislike') as dislikes_count");
    $stmt->execute([$video_id, $video_id]);
    $counts = $stmt->fetch();
    
    // Actualizar contadores en la tabla videos
    $stmt = $pdo->prepare("UPDATE videos SET likes_count = ?, dislikes_count = ? WHERE id = ?");
    $stmt->execute([$counts['likes_count'], $counts['dislikes_count'], $video_id]);
    
    echo json_encode([
        'success' => true,
        'likes_count' => $counts['likes_count'],
        'dislikes_count' => $counts['dislikes_count'],
        'user_like' => $user_like
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?> 