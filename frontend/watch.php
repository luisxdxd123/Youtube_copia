<?php
session_start();
include '../config/database.php';

$video_id = $_GET['v'] ?? 0;

// Obtener información del video
$stmt = $pdo->prepare("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ? AND v.status = 'active'");
$stmt->execute([$video_id]);
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
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <a href="../index.php" class="flex items-center space-x-2">
                <i class="fab fa-youtube text-red-600 text-2xl"></i>
                <span class="text-xl font-bold">YuTube</span>
            </a>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="upload.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        <i class="fas fa-plus mr-2"></i>Subir
                    </a>
                    <span class="text-gray-700">Hola, <?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="text-red-600 hover:text-red-800">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-6 max-w-4xl">
        <!-- Reproductor de Video -->
        <div class="bg-black rounded-lg overflow-hidden mb-6">
            <video controls class="w-full h-auto" style="max-height: 500px;">
                <source src="<?= htmlspecialchars($video['video_path']) ?>" type="video/mp4">
                Tu navegador no soporta el elemento video.
            </video>
        </div>

        <!-- Información del Video -->
        <div class="bg-white rounded-lg p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($video['title']) ?></h1>
            
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600"><?= number_format($video['views']) ?> visualizaciones</span>
                    <span class="text-gray-600"><?= date('M j, Y', strtotime($video['created_at'])) ?></span>
                </div>
                
                <!-- Botones de Like/Dislike -->
                <div class="flex items-center space-x-2">
                    <button onclick="toggleLike('like')" 
                            class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-gray-100 <?= $user_like === 'like' ? 'bg-blue-100 text-blue-600' : 'text-gray-600' ?>">
                        <i class="fas fa-thumbs-up"></i>
                        <span id="likesCount"><?= $video['likes_count'] ?></span>
                    </button>
                    <button onclick="toggleLike('dislike')" 
                            class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-gray-100 <?= $user_like === 'dislike' ? 'bg-red-100 text-red-600' : 'text-gray-600' ?>">
                        <i class="fas fa-thumbs-down"></i>
                        <span id="dislikesCount"><?= $video['dislikes_count'] ?></span>
                    </button>
                </div>
            </div>

            <!-- Canal -->
            <div class="flex items-center justify-between border-t pt-4">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($video['username']) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Descripción -->
            <?php if ($video['description']): ?>
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-gray-700"><?= nl2br(htmlspecialchars($video['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Comentarios -->
        <div class="bg-white rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                Comentarios (<?= count($comments) ?>)
            </h2>

            <!-- Formulario de comentario -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <form id="commentForm" class="mb-6">
                <div class="flex space-x-4">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-600 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <textarea id="commentText" placeholder="Agrega un comentario..." 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                                  rows="2"></textarea>
                        <div class="flex justify-end mt-2">
                            <button type="submit" 
                                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                Comentar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <?php else: ?>
            <div class="text-center py-4 border-b">
                <p class="text-gray-600">
                    <a href="login.php" class="text-red-600 hover:text-red-700">Inicia sesión</a> 
                    para comentar
                </p>
            </div>
            <?php endif; ?>

            <!-- Lista de comentarios -->
            <div id="commentsList" class="space-y-4">
                <?php foreach ($comments as $comment): ?>
                <div class="flex space-x-4">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-600 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="font-semibold text-sm"><?= htmlspecialchars($comment['username']) ?></span>
                            <span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($comment['created_at'])) ?></span>
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
    </script>
</body>
</html> 