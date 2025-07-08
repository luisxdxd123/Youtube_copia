<?php
session_start();
include '../config/database.php';

// Obtener videos recientes
try {
    $stmt = $pdo->query("SELECT v.*, u.username, u.avatar FROM videos v JOIN users u ON v.user_id = u.id WHERE v.status = 'active' ORDER BY v.created_at DESC LIMIT 12");
    $videos = $stmt->fetchAll();
} catch (Exception $e) {
    $videos = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YuTube - Plataforma de Videos</title>
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

    <!-- Main Content -->
    <main id="mainContent" class="container mx-auto px-4 py-8 mt-16 transition-all duration-200">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Videos Recientes</h1>
            <div class="flex space-x-2">
                <button class="px-4 py-2 bg-gray-100 rounded-full text-sm font-medium hover:bg-gray-200">Todos</button>
                <button class="px-4 py-2 bg-gray-100 rounded-full text-sm font-medium hover:bg-gray-200">Música</button>
                <button class="px-4 py-2 bg-gray-100 rounded-full text-sm font-medium hover:bg-gray-200">Juegos</button>
                <button class="px-4 py-2 bg-gray-100 rounded-full text-sm font-medium hover:bg-gray-200">Noticias</button>
            </div>
        </div>
        
        <?php if (empty($videos)): ?>
        <div class="text-center py-16 bg-white rounded-xl shadow-sm">
            <i class="fas fa-video text-7xl text-gray-300 mb-6"></i>
            <h2 class="text-2xl font-semibold text-gray-800 mb-3">No hay videos disponibles</h2>
            <p class="text-gray-600 mb-6">¡Sé el primero en compartir un video con la comunidad!</p>
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="upload.php" class="inline-block bg-yutube-600 text-white px-8 py-3 rounded-full hover:bg-yutube-700 transition duration-200">
                <i class="fas fa-plus mr-2"></i>Subir Video
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($videos as $video): ?>
            <article class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition duration-200 group">
                <a href="watch.php?v=<?= $video['id'] ?>" class="block">
                    <div class="relative aspect-video">
                        <?php if ($video['thumbnail']): ?>
                            <img src="<?= htmlspecialchars($video['thumbnail']) ?>" 
                                 alt="<?= htmlspecialchars($video['title']) ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-200">
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
                        <div class="flex space-x-3">
                            <img src="<?= $video['avatar'] ? htmlspecialchars($video['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($video['username']) ?>" 
                                 alt="<?= htmlspecialchars($video['username']) ?>" 
                                 class="w-10 h-10 rounded-full">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 line-clamp-2 mb-1 group-hover:text-yutube-600">
                                    <?= htmlspecialchars($video['title']) ?>
                                </h3>
                                <p class="text-sm text-gray-600 mb-1">
                                    <?= htmlspecialchars($video['username']) ?>
                                </p>
                                <div class="flex items-center text-xs text-gray-500">
                                    <span><?= number_format($video['views']) ?> visualizaciones</span>
                                    <span class="mx-1">•</span>
                                    <span><?= date('d M Y', strtotime($video['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <script>
        // El código del sidebar ahora está en header.php
    </script>
</body>
</html> 