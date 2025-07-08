<?php
session_start();
include '../config/database.php';

// Obtener el término de búsqueda y limpiarlo
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$search = htmlspecialchars($search);

// Preparar la consulta de búsqueda
try {
    if (!empty($search)) {
        // Buscar en título, descripción y nombre de usuario
        $stmt = $pdo->prepare("
            SELECT v.*, u.username, u.avatar 
            FROM videos v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.status = 'active' 
            AND (
                v.title LIKE :search 
                OR v.description LIKE :search 
                OR u.username LIKE :search
            )
            ORDER BY 
                CASE 
                    WHEN v.title LIKE :exact_match THEN 1
                    WHEN v.title LIKE :start_match THEN 2
                    ELSE 3
                END,
                v.views DESC,
                v.created_at DESC
            LIMIT 24
        ");
        
        $searchParam = "%{$search}%";
        $stmt->execute([
            'search' => $searchParam,
            'exact_match' => $search,
            'start_match' => $search . '%'
        ]);
        $videos = $stmt->fetchAll();
    } else {
        $videos = [];
    }
} catch (Exception $e) {
    $videos = [];
    $error = "Error al realizar la búsqueda: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($search) ? "Búsqueda: {$search} - " : "" ?>videoNetBandera</title>
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

    <main class="container mx-auto px-4 py-8 mt-16">
        <?php if (!empty($search)): ?>
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">
                    Resultados para "<?= $search ?>"
                </h1>
                <p class="text-gray-600 mt-1">
                    <?= count($videos) ?> videos encontrados
                </p>
            </div>

            <?php if (!empty($videos)): ?>
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
            <?php else: ?>
                <div class="text-center py-16 bg-white rounded-xl shadow-sm">
                    <i class="fas fa-search text-7xl text-gray-300 mb-6"></i>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-3">No se encontraron resultados</h2>
                    <p class="text-gray-600 mb-6">Intenta con otros términos de búsqueda</p>
                    <div class="max-w-md mx-auto">
                        <form action="search.php" method="GET" class="flex">
                            <input type="text" name="q" value="<?= $search ?>"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:border-yutube-500 focus:ring-1 focus:ring-yutube-500"
                                   placeholder="Buscar videos...">
                            <button type="submit" class="px-6 py-2 bg-yutube-600 text-white rounded-r-lg hover:bg-yutube-700">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-xl shadow-sm">
                <i class="fas fa-search text-7xl text-gray-300 mb-6"></i>
                <h2 class="text-2xl font-semibold text-gray-800 mb-3">Realiza una búsqueda</h2>
                <p class="text-gray-600 mb-6">Escribe lo que quieres encontrar</p>
                <div class="max-w-md mx-auto">
                    <form action="search.php" method="GET" class="flex">
                        <input type="text" name="q" 
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:border-yutube-500 focus:ring-1 focus:ring-yutube-500"
                               placeholder="Buscar videos...">
                        <button type="submit" class="px-6 py-2 bg-yutube-600 text-white rounded-r-lg hover:bg-yutube-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html> 