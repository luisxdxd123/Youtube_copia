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
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <a href="index.php" class="flex items-center space-x-2">
                <i class="fab fa-youtube text-red-600 text-3xl"></i>
                <span class="text-2xl font-bold">YuTube</span>
            </a>

            <div class="flex-1 max-w-md mx-4">
                <form action="search.php" method="GET" class="flex">
                    <input type="text" name="q" placeholder="Buscar videos..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:border-red-500">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-r-lg hover:bg-red-700">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

                            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="upload.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        <i class="fas fa-plus mr-2"></i>Subir
                    </a>
                    <a href="my-videos.php" class="text-gray-700 hover:text-gray-900">
                        <i class="fas fa-video mr-1"></i>Mis Videos
                    </a>
                    <span class="text-gray-700">Hola, <?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="text-red-600 hover:text-red-800">Iniciar Sesión</a>
                    <a href="register.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Videos Recientes</h1>
        
        <?php if (empty($videos)): ?>
        <div class="text-center py-12">
            <i class="fas fa-video text-6xl text-gray-300 mb-4"></i>
            <h2 class="text-2xl font-semibold text-gray-600 mb-2">No hay videos disponibles</h2>
            <p class="text-gray-500">¡Sé el primero en subir un video!</p>
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="upload.php" class="inline-block mt-4 bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700">
                Subir Video
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($videos as $video): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <a href="watch.php?v=<?= $video['id'] ?>">
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
                        <p class="text-sm text-gray-600 mb-2">
                            <?= htmlspecialchars($video['username']) ?>
                        </p>
                        <div class="flex items-center text-sm text-gray-500">
                            <span><?= number_format($video['views']) ?> visualizaciones</span>
                            <span class="mx-2">•</span>
                            <span><?= date('M j, Y', strtotime($video['created_at'])) ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 