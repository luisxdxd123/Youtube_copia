<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$channel_id = $data['channel_id'] ?? null;

if (!$channel_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID del canal no proporcionado']);
    exit;
}

if ($channel_id == $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No puedes suscribirte a tu propio canal']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar si ya existe la suscripción
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
    $stmt->execute([$_SESSION['user_id'], $channel_id]);
    $subscription = $stmt->fetch();

    if ($subscription) {
        // Eliminar suscripción
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
        $stmt->execute([$_SESSION['user_id'], $channel_id]);
        
        // Decrementar contador de suscriptores
        $stmt = $pdo->prepare("UPDATE users SET subscribers_count = subscribers_count - 1 WHERE id = ?");
        $stmt->execute([$channel_id]);
        
        $isSubscribed = false;
    } else {
        // Crear suscripción
        $stmt = $pdo->prepare("INSERT INTO subscriptions (subscriber_id, channel_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $channel_id]);
        
        // Incrementar contador de suscriptores
        $stmt = $pdo->prepare("UPDATE users SET subscribers_count = subscribers_count + 1 WHERE id = ?");
        $stmt->execute([$channel_id]);
        
        $isSubscribed = true;
    }

    // Obtener el número actualizado de suscriptores
    $stmt = $pdo->prepare("SELECT subscribers_count FROM users WHERE id = ?");
    $stmt->execute([$channel_id]);
    $subscribers_count = $stmt->fetchColumn();

    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'isSubscribed' => $isSubscribed,
        'subscribers_count' => $subscribers_count
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al procesar la suscripción']);
} 