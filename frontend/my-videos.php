<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener videos del usuario
$stmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? AND status != 'deleted' ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$videos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Videos - videoNetBandera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <a href="../index.php" class="flex items-center space-x-2">
                <i class="fab fa-youtube text-red-600 text-2xl"></i>
                <span class="text-xl font-bold">videoNetBandera</span>
            </a>
            <div class="flex items-center space-x-4">
                <a href="upload.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus mr-2"></i>Subir
                </a>
                <span class="text-gray-700">Hola, <?= htmlspecialchars($_SESSION['first_name']) ?></span>
                <a href="logout.php" class="text-red-600 hover:text-red-800">Salir</a>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Mis Videos</h1>
            <a href="upload.php" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700">
                <i class="fas fa-plus mr-2"></i>Subir Nuevo Video
            </a>
        </div>

        <?php if (empty($videos)): ?>
        <div class="text-center py-12">
            <i class="fas fa-video text-6xl text-gray-300 mb-4"></i>
            <h2 class="text-2xl font-semibold text-gray-600 mb-2">No tienes videos</h2>
            <p class="text-gray-500 mb-4">¡Sube tu primer video y comienza a compartir contenido!</p>
            <a href="upload.php" class="inline-block bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700">
                Subir Video
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($videos as $video): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="relative">
                    <?php if ($video['thumbnail']): ?>
                        <img src="<?= htmlspecialchars($video['thumbnail']) ?>" alt="Thumbnail" class="w-full h-48 object-cover">
                    <?php else: ?>
                        <div class="w-full h-48 bg-gray-300 flex items-center justify-center">
                            <i class="fas fa-play text-4xl text-gray-500"></i>
                        </div>
                    <?php endif; ?>
                    <span class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                        <?= gmdate("i:s", $video['duration']) ?>
                    </span>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                        <?= htmlspecialchars($video['title']) ?>
                    </h3>
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <span><?= number_format($video['views']) ?> visualizaciones</span>
                        <span class="mx-2">•</span>
                        <span><?= date('M j, Y', strtotime($video['created_at'])) ?></span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <span><i class="fas fa-thumbs-up mr-1"></i><?= $video['likes_count'] ?></span>
                        <span class="mx-2">•</span>
                        <span><i class="fas fa-comment mr-1"></i><?= $video['comments_count'] ?></span>
                    </div>
                    <div class="flex space-x-2">
                        <a href="watch.php?v=<?= $video['id'] ?>" 
                           class="flex-1 bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700">
                            <i class="fas fa-eye mr-2"></i>Ver
                        </a>
                        <button onclick="deleteVideo(<?= $video['id'] ?>)" 
                                class="flex-1 bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Eliminar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de confirmación -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar eliminación</h3>
            <p class="text-gray-600 mb-6">¿Estás seguro de que quieres eliminar este video? Esta acción no se puede deshacer.</p>
            <div class="flex space-x-4">
                <button onclick="closeDeleteModal()" 
                        class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400">
                    Cancelar
                </button>
                <button onclick="confirmDelete()" 
                        class="flex-1 bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700">
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
        let videoToDelete = null;

        function deleteVideo(videoId) {
            videoToDelete = videoId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            videoToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function confirmDelete() {
            if (!videoToDelete) return;

            fetch('../api/delete-video.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({video_id: videoToDelete})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al eliminar el video: ' + data.error);
                }
                closeDeleteModal();
            })
            .catch(error => {
                alert('Error al eliminar el video');
                closeDeleteModal();
            });
        }
    </script>
</body>
</html> 