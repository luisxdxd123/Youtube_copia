<?php
session_start();
include '../config/database.php';

$channel_id = $_GET['id'] ?? 0;

try {
    // Obtener información del canal
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM videos WHERE user_id = u.id AND status = 'active') as videos_count,
            CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as is_subscribed
        FROM users u
        LEFT JOIN subscriptions s ON u.id = s.channel_id AND s.subscriber_id = ?
        WHERE u.id = ?
    ");
    $stmt->execute([isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null, $channel_id]);
    $channel = $stmt->fetch();

    if (!$channel) {
        header('Location: index.php');
        exit;
    }

    // Obtener estadísticas del canal
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT v.id) as total_videos,
            SUM(v.views) as total_views,
            SUM(v.likes_count) as total_likes,
            COUNT(DISTINCT s.id) as total_subscribers
        FROM users u
        LEFT JOIN videos v ON u.id = v.user_id AND v.status = 'active'
        LEFT JOIN subscriptions s ON u.id = s.channel_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$channel_id]);
    $stats = $stmt->fetch();

    // Obtener videos del canal
    $stmt = $pdo->prepare("
        SELECT v.*, 
               COUNT(DISTINCT l.id) as likes_count,
               COUNT(DISTINCT c.id) as comments_count
        FROM videos v
        LEFT JOIN likes l ON v.id = l.video_id AND l.type = 'like'
        LEFT JOIN comments c ON v.id = c.video_id
        WHERE v.user_id = ? AND v.status = 'active'
        GROUP BY v.id
        ORDER BY v.created_at DESC
    ");
    $stmt->execute([$channel_id]);
    $videos = $stmt->fetchAll();

} catch (Exception $e) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($channel['username']) ?> - videoNetBandera</title>
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
        .channel-banner {
            height: 300px;
            overflow: hidden;
            background: linear-gradient(45deg, #dc2626, #ef4444);
            position: relative;
        }
        .channel-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .channel-info-wrapper {
            position: relative;
            margin-top: -80px;
        }
        .channel-avatar {
            border: 4px solid white;
            margin-left: 2rem;
            margin-bottom: -3rem;
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'header.php'; ?>

    <main class="mt-16">
        <!-- Banner del Canal -->
        <div class="relative">
            <?php if ($channel['banner_image']): ?>
                <div class="channel-banner">
                    <img src="<?= htmlspecialchars($channel['banner_image']) ?>" 
                         alt="Banner de <?= htmlspecialchars($channel['username']) ?>" 
                         class="w-full h-full object-cover">
                </div>
            <?php else: ?>
                <div class="channel-banner" style="background-color: <?= htmlspecialchars($channel['banner_color']) ?>"></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $channel_id): ?>
                <a href="profile-settings.php" 
                   class="absolute top-4 right-4 bg-black/50 text-white px-4 py-2 rounded-lg hover:bg-black/70 transition duration-200">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Canal
                </a>
            <?php endif; ?>
        </div>

        <!-- Avatar y Nombre del Canal -->
        <div class="channel-info-wrapper">
            <img src="<?= $channel['avatar'] ? htmlspecialchars($channel['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($channel['username']) ?>" 
                 alt="<?= htmlspecialchars($channel['username']) ?>" 
                 class="channel-avatar w-32 h-32 rounded-full shadow-lg">
        </div>

        <!-- Información del Canal -->
        <div class="container mx-auto px-4 py-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            <?= htmlspecialchars($channel['username']) ?>
                        </h1>
                        <div class="flex items-center space-x-4 mt-2 text-gray-600">
                            <span><?= number_format($stats['total_subscribers']) ?> suscriptores</span>
                            <span>•</span>
                            <span><?= number_format($stats['total_videos']) ?> videos</span>
                        </div>
                        <?php if ($channel['bio']): ?>
                            <p class="mt-4 text-gray-600 max-w-2xl"><?= htmlspecialchars($channel['bio']) ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $channel_id): ?>
                        <button onclick="toggleSubscription(<?= $channel_id ?>, this)" 
                                class="mt-4 md:mt-0 px-8 py-3 rounded-full transition duration-200 <?= $channel['is_subscribed'] ? 'bg-gray-200 text-gray-800 hover:bg-gray-300' : 'bg-yutube-600 text-white hover:bg-yutube-700' ?>">
                            <i class="fas <?= $channel['is_subscribed'] ? 'fa-bell' : 'fa-plus' ?> mr-2"></i>
                            <span class="subscription-text"><?= $channel['is_subscribed'] ? 'Suscrito' : 'Suscribirse' ?></span>
                            <span class="subscribers-count hidden"><?= $stats['total_subscribers'] ?></span>
                        </button>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="mt-4 md:mt-0 px-8 py-3 rounded-full bg-yutube-600 text-white hover:bg-yutube-700 transition duration-200">
                            <i class="fas fa-plus mr-2"></i>
                            Suscribirse
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Estadísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 pt-6 border-t">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_views']) ?></div>
                        <div class="text-sm text-gray-600">Visualizaciones</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_subscribers']) ?></div>
                        <div class="text-sm text-gray-600">Suscriptores</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_videos']) ?></div>
                        <div class="text-sm text-gray-600">Videos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_likes']) ?></div>
                        <div class="text-sm text-gray-600">Me gusta</div>
                    </div>
                </div>
            </div>

            <!-- Videos del Canal -->
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Videos</h2>

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
                                        <h3 class="font-semibold text-gray-900 line-clamp-2 mb-2 group-hover:text-yutube-600">
                                            <?= htmlspecialchars($video['title']) ?>
                                        </h3>
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
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-video text-7xl text-gray-300 mb-6"></i>
                        <h3 class="text-2xl font-semibold text-gray-800 mb-3">No hay videos</h3>
                        <p class="text-gray-600">Este canal aún no ha subido ningún video</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
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
                        
                        // Actualizar todos los contadores de suscriptores en la página
                        document.querySelectorAll('.subscribers-count').forEach(counter => {
                            counter.textContent = data.subscribers_count;
                        });
                        
                        // Actualizar el texto de suscriptores en el encabezado
                        const statsText = document.querySelector('.text-gray-600');
                        if (statsText) {
                            const parts = statsText.textContent.split('•');
                            statsText.textContent = `${number_format(data.subscribers_count)} suscriptores • ${parts[1]}`;
                        }
                        
                        // Actualizar la estadística de suscriptores
                        const statsSubscribers = document.querySelector('.grid.grid-cols-2 .text-2xl.font-bold');
                        if (statsSubscribers) {
                            statsSubscribers.textContent = number_format(data.subscribers_count);
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