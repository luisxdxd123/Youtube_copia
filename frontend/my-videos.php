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
                            50: '#eff6ff',   // azul claro
                            100: '#dbeafe',  // azul muy claro
                            500: '#3b82f6',  // azul medio
                            600: '#2563eb',  // azul principal
                            700: '#1d4ed8',  // azul oscuro
                        },
                    },
                },
            },
        }
    </script>
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-16">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Mis Videos</h1>
            <a href="upload.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-full hover:bg-blue-700 transition duration-200">
                <i class="fas fa-upload mr-2"></i>
                Subir Nuevo Video
            </a>
        </div>

        <?php if (empty($videos)): ?>
        <div class="text-center py-16 bg-white rounded-xl shadow-sm">
            <i class="fas fa-video text-7xl text-gray-300 mb-6"></i>
            <h2 class="text-2xl font-semibold text-gray-800 mb-3">No tienes videos</h2>
            <p class="text-gray-600 mb-6">¡Sube tu primer video y comienza a compartir contenido!</p>
            <a href="upload.php" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-full hover:bg-blue-700 transition duration-200">
                <i class="fas fa-upload mr-2"></i>Subir Video
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($videos as $video): ?>
            <article class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition duration-200">
                <div class="relative aspect-video">
                    <?php if ($video['thumbnail']): ?>
                        <img src="<?= htmlspecialchars($video['thumbnail']) ?>" 
                             alt="<?= htmlspecialchars($video['title']) ?>" 
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-play text-4xl text-gray-400"></i>
                        </div>
                    <?php endif; ?>
                    <span class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                        <?= gmdate("i:s", $video['duration']) ?>
                    </span>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 line-clamp-2 mb-1 group-hover:text-blue-600">
                        <?= htmlspecialchars($video['title']) ?>
                    </h3>
                    <div class="flex items-center text-sm text-gray-500 mb-3">
                        <span><?= number_format($video['views']) ?> visualizaciones</span>
                        <span class="mx-2">•</span>
                        <span><?= date('d M Y', strtotime($video['created_at'])) ?></span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <span><i class="fas fa-thumbs-up mr-1"></i><?= $video['likes_count'] ?></span>
                        <span class="mx-2">•</span>
                        <span><i class="fas fa-comment mr-1"></i><?= $video['comments_count'] ?></span>
                    </div>
                    <div class="flex space-x-2">
                        <a href="watch.php?v=<?= $video['id'] ?>" 
                           class="flex-1 bg-gray-100 text-gray-700 text-center py-2 px-4 rounded-full hover:bg-gray-200 transition duration-200">
                            <i class="fas fa-eye mr-2"></i>Ver
                        </a>
                        <button onclick="deleteVideo(<?= $video['id'] ?>)" 
                                class="flex-1 bg-blue-50 text-blue-600 py-2 px-4 rounded-full hover:bg-blue-100 transition duration-200">
                            <i class="fas fa-trash mr-2"></i>Eliminar
                        </button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de confirmación -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar eliminación</h3>
            <p class="text-gray-600 mb-6">¿Estás seguro de que quieres eliminar este video? Esta acción no se puede deshacer.</p>
            <div class="flex space-x-4">
                <button onclick="closeDeleteModal()" 
                        class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-full hover:bg-gray-200 transition duration-200">
                    Cancelar
                </button>
                <button onclick="confirmDelete()" 
                        class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-full hover:bg-blue-700 transition duration-200">
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