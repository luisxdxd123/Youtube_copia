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

if (!$video_id) {
    echo json_encode(['success' => false, 'error' => 'Video ID requerido']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Verificar que el video pertenece al usuario
    $stmt = $pdo->prepare("SELECT user_id, video_path FROM videos WHERE id = ?");
    $stmt->execute([$video_id]);
    $video = $stmt->fetch();
    
    if (!$video) {
        echo json_encode(['success' => false, 'error' => 'Video no encontrado']);
        exit;
    }
    
    if ($video['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para eliminar este video']);
        exit;
    }
    
    // Eliminar archivo físico
    if (file_exists($video['video_path'])) {
        unlink($video['video_path']);
    }
    
    // Marcar como eliminado en la base de datos
    $stmt = $pdo->prepare("UPDATE videos SET status = 'deleted' WHERE id = ?");
    $stmt->execute([$video_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?> 