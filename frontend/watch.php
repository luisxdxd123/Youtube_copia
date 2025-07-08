<?php
session_start();
include '../config/database.php';

$video_id = $_GET['v'] ?? 0;

// Obtener información del video y del canal
$stmt = $pdo->prepare("
    SELECT 
        v.*, 
        u.username, 
        u.avatar,
        u.subscribers_count,
        (SELECT COUNT(*) FROM videos WHERE user_id = u.id AND status = 'active') as videos_count,
        CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as is_subscribed
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    LEFT JOIN subscriptions s ON u.id = s.channel_id AND s.subscriber_id = ?
    WHERE v.id = ? AND v.status = 'active'
");
$stmt->execute([isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null, $video_id]);
$video = $stmt->fetch();

if (!$video) {
    header('Location: ../index.php');
    exit;
}

// Incrementar vistas
$stmt = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
$stmt->execute([$video_id]);

// Obtener comentarios
$stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.video_id = ? AND c.status = 'active' ORDER BY c.created_at DESC");
$stmt->execute([$video_id]);
$comments = $stmt->fetchAll();

// Obtener like del usuario actual
$user_like = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT type FROM likes WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$_SESSION['user_id'], $video_id]);
    $user_like = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?> - YuTube</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'yutube': {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                        },
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'header.php'; ?>

    <div id="mainContent" class="container mx-auto px-4 py-8 mt-16 transition-all duration-200">
        <!-- Reproductor de Video -->
        <div class="bg-black rounded-lg overflow-hidden mb-6">
            <video controls class="w-full h-auto" style="max-height: 70vh;">
                <source src="<?= htmlspecialchars($video['video_path']) ?>" type="video/mp4">
                Tu navegador no soporta el elemento video.
            </video>
        </div>

        <!-- Información del Video -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($video['title']) ?></h1>
            
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600"><?= number_format($video['views']) ?> visualizaciones</span>
                    <span class="text-gray-600"><?= date('d M Y', strtotime($video['created_at'])) ?></span>
                </div>
                
                <!-- Botones de Like/Dislike -->
                <div class="flex items-center space-x-2">
                    <button onclick="toggleLike('like')" 
                            class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-gray-100 <?= $user_like === 'like' ? 'bg-yutube-50 text-yutube-600' : 'text-gray-600' ?>">
                        <i class="fas fa-thumbs-up"></i>
                        <span id="likesCount"><?= $video['likes_count'] ?></span>
                    </button>
                    <button onclick="toggleLike('dislike')" 
                            class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-gray-100 <?= $user_like === 'dislike' ? 'bg-yutube-50 text-yutube-600' : 'text-gray-600' ?>">
                        <i class="fas fa-thumbs-down"></i>
                        <span id="dislikesCount"><?= $video['dislikes_count'] ?></span>
                    </button>
                </div>
            </div>

            <!-- Canal -->
            <div class="flex items-center justify-between border-t pt-4">
                <div class="flex items-center space-x-4">
                    <a href="channel.php?id=<?= $video['user_id'] ?>" class="flex items-center space-x-4 group">
                        <img src="<?= $video['avatar'] ? htmlspecialchars($video['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($video['username']) ?>" 
                             alt="<?= htmlspecialchars($video['username']) ?>" 
                             class="w-12 h-12 rounded-full">
                        <div>
                            <h3 class="font-semibold text-gray-900 group-hover:text-yutube-600">
                                <?= htmlspecialchars($video['username']) ?>
                            </h3>
                            <p class="text-sm text-gray-500">
                                <?= number_format($video['subscribers_count']) ?> suscriptores • 
                                <?= number_format($video['videos_count']) ?> videos
                            </p>
                        </div>
                    </a>
                </div>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $video['user_id']): ?>
                    <button onclick="toggleSubscription(<?= $video['user_id'] ?>, this)" 
                            class="px-6 py-2.5 rounded-full transition duration-200 <?= $video['is_subscribed'] ? 'bg-gray-200 text-gray-800 hover:bg-gray-300' : 'bg-yutube-600 text-white hover:bg-yutube-700' ?>">
                        <i class="fas <?= $video['is_subscribed'] ? 'fa-bell' : 'fa-plus' ?> mr-2"></i>
                        <span class="subscription-text"><?= $video['is_subscribed'] ? 'Suscrito' : 'Suscribirse' ?></span>
                        <span class="subscribers-count hidden"><?= $video['subscribers_count'] ?></span>
                    </button>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="px-6 py-2.5 rounded-full bg-yutube-600 text-white hover:bg-yutube-700 transition duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Suscribirse
                    </a>
                <?php endif; ?>
            </div>

            <!-- Descripción -->
            <?php if ($video['description']): ?>
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($video['description']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Comentarios -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                Comentarios (<?= count($comments) ?>)
            </h2>

            <!-- Formulario de comentario -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <form id="commentForm" class="mb-6">
                <div class="flex space-x-4">
                    <img src="<?= isset($_SESSION['avatar']) ? htmlspecialchars($_SESSION['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['first_name']) ?>" 
                         alt="Avatar" class="w-10 h-10 rounded-full">
                    <div class="flex-1">
                        <textarea id="commentText" placeholder="Agrega un comentario..." 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yutube-500 focus:border-transparent resize-none"
                                  rows="2"></textarea>
                        <div class="flex justify-end mt-2">
                            <button type="submit" 
                                    class="px-6 py-2 bg-yutube-600 text-white rounded-full hover:bg-yutube-700 transition duration-200">
                                Comentar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <?php else: ?>
            <div class="text-center py-4 border-b">
                <p class="text-gray-600">
                    <a href="login.php" class="text-yutube-600 hover:text-yutube-700">Inicia sesión</a> 
                    para comentar
                </p>
            </div>
            <?php endif; ?>

            <!-- Lista de comentarios -->
            <div id="commentsList" class="space-y-6">
                <?php foreach ($comments as $comment): ?>
                <div class="flex space-x-4">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($comment['username']) ?>" 
                         alt="<?= htmlspecialchars($comment['username']) ?>" 
                         class="w-10 h-10 rounded-full">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="font-semibold text-sm"><?= htmlspecialchars($comment['username']) ?></span>
                            <span class="text-xs text-gray-500"><?= date('d M Y', strtotime($comment['created_at'])) ?></span>
                        </div>
                        <p class="text-gray-800"><?= htmlspecialchars($comment['content']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleLike(type) {
            <?php if (isset($_SESSION['user_id'])): ?>
            fetch('../api/toggle-like.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({video_id: <?= $video_id ?>, type: type})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('likesCount').textContent = data.likes_count;
                    document.getElementById('dislikesCount').textContent = data.dislikes_count;
                    location.reload();
                }
            });
            <?php else: ?>
            window.location.href = 'login.php';
            <?php endif; ?>
        }

        document.getElementById('commentForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const commentText = document.getElementById('commentText').value.trim();
            
            if (!commentText) return;
            
            fetch('../api/add-comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({video_id: <?= $video_id ?>, content: commentText})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });

        function toggleSubscription(channelId, button) {
            if (!button.classList.contains('processing')) {
                button.classList.add('processing');
                
                fetch('../api/toggle-subscription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        channel_id: channelId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const subscribersCount = button.querySelector('.subscribers-count');
                        const subscriptionText = button.querySelector('.subscription-text');
                        
                        if (data.isSubscribed) {
                            button.classList.remove('bg-yutube-600', 'hover:bg-yutube-700', 'text-white');
                            button.classList.add('bg-gray-200', 'text-gray-800', 'hover:bg-gray-300');
                            button.querySelector('i').classList.remove('fa-plus');
                            button.querySelector('i').classList.add('fa-bell');
                            subscriptionText.textContent = 'Suscrito';
                        } else {
                            button.classList.remove('bg-gray-200', 'text-gray-800', 'hover:bg-gray-300');
                            button.classList.add('bg-yutube-600', 'hover:bg-yutube-700', 'text-white');
                            button.querySelector('i').classList.remove('fa-bell');
                            button.querySelector('i').classList.add('fa-plus');
                            subscriptionText.textContent = 'Suscribirse';
                        }
                        
                        // Actualizar el contador en la página
                        const channelSubscribersCount = document.querySelector('.text-sm.text-gray-500');
                        if (channelSubscribersCount) {
                            const parts = channelSubscribersCount.textContent.split('•');
                            channelSubscribersCount.textContent = `${number_format(data.subscribers_count)} suscriptores • ${parts[1]}`;
                        }
                    } else {
                        alert(data.error || 'Error al procesar la suscripción');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la suscripción');
                })
                .finally(() => {
                    button.classList.remove('processing');
                });
            }
        }

        function number_format(number) {
            return new Intl.NumberFormat().format(number);
        }
    </script>
</body>
</html> 