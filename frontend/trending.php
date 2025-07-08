<?php
session_start();
include '../config/database.php';

// Obtener videos en tendencia (últimos 7 días, ordenados por vistas y likes)
try {
    $stmt = $pdo->prepare("
        SELECT 
            v.*,
            u.username,
            u.avatar,
            COUNT(DISTINCT l.id) as likes_count,
            COUNT(DISTINCT c.id) as comments_count
        FROM videos v 
        JOIN users u ON v.user_id = u.id 
        LEFT JOIN likes l ON v.id = l.video_id AND l.type = 'like'
        LEFT JOIN comments c ON v.id = c.video_id
        WHERE 
            v.status = 'active' 
            AND v.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY v.id
        ORDER BY 
            (v.views * 0.6) + (COUNT(DISTINCT l.id) * 0.3) + (COUNT(DISTINCT c.id) * 0.1) DESC,
            v.created_at DESC
        LIMIT 24
    ");
    $stmt->execute();
    $videos = $stmt->fetchAll();

    // Calcular el "score" de tendencia para cada video
    foreach ($videos as &$video) {
        $video['trend_score'] = ($video['views'] * 0.6) + ($video['likes_count'] * 0.3) + ($video['comments_count'] * 0.1);
    }
} catch (Exception $e) {
    $videos = [];
    $error = "Error al cargar los videos en tendencia: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tendencias - videoNetBandera</title>
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
        .trend-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #dc2626;
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'header.php'; ?>

    <main class="container mx-auto px-4 py-8 mt-16">
        <div class="flex items-center space-x-4 mb-8">
            <i class="fas fa-fire text-3xl text-yutube-600"></i>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tendencias</h1>
                <p class="text-gray-600">Los videos más populares de la última semana</p>
            </div>
        </div>

        <?php if (!empty($videos)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($videos as $index => $video): ?>
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
                                <span class="trend-number"><?= $index + 1 ?></span>
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
                                        <div class="flex items-center space-x-3 mt-2 text-xs text-gray-500">
                                            <span class="flex items-center">
                                                <i class="fas fa-thumbs-up text-yutube-600 mr-1"></i>
                                                <?= number_format($video['likes_count']) ?>
                                            </span>
                                            <span class="flex items-center">
                                                <i class="fas fa-comment text-gray-400 mr-1"></i>
                                                <?= number_format($video['comments_count']) ?>
                                            </span>
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
                <i class="fas fa-fire text-7xl text-gray-300 mb-6"></i>
                <h2 class="text-2xl font-semibold text-gray-800 mb-3">No hay videos en tendencia</h2>
                <p class="text-gray-600 mb-6">Vuelve más tarde para ver los videos más populares</p>
                <a href="index.php" class="inline-block bg-yutube-600 text-white px-6 py-3 rounded-full hover:bg-yutube-700 transition duration-200">
                    <i class="fas fa-home mr-2"></i>
                    Ir al inicio
                </a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html> 