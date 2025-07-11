<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    // Obtener videos de canales suscritos
    $stmt = $pdo->prepare("
        SELECT 
            v.*,
            u.username,
            u.avatar,
            u.subscribers_count,
            COUNT(DISTINCT l.id) as likes_count,
            COUNT(DISTINCT c.id) as comments_count,
            s.created_at as subscription_date
        FROM subscriptions s
        JOIN users u ON s.channel_id = u.id
        JOIN videos v ON v.user_id = u.id
        LEFT JOIN likes l ON v.id = l.video_id AND l.type = 'like'
        LEFT JOIN comments c ON v.id = c.video_id
        WHERE 
            s.subscriber_id = ? 
            AND v.status = 'active'
        GROUP BY v.id
        ORDER BY v.created_at DESC
        LIMIT 48
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $videos = $stmt->fetchAll();

    // Obtener lista de canales suscritos
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            s.created_at as subscription_date,
            (SELECT COUNT(*) FROM videos WHERE user_id = u.id AND status = 'active') as videos_count
        FROM subscriptions s
        JOIN users u ON s.channel_id = u.id
        WHERE s.subscriber_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $channels = $stmt->fetchAll();

} catch (Exception $e) {
    $videos = [];
    $channels = [];
    $error = "Error al cargar las suscripciones: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Suscripciones - videoNetBandera</title>
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
        .channel-scroll {
            scrollbar-width: thin;
            scrollbar-color: #2563eb #f3f4f6;
        }
        .channel-scroll::-webkit-scrollbar {
            height: 6px;
        }
        .channel-scroll::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 3px;
        }
        .channel-scroll::-webkit-scrollbar-thumb {
            background-color: #2563eb;
            border-radius: 3px;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'header.php'; ?>

    <main class="container mx-auto px-4 py-8 mt-16">
        <!-- Canales Suscritos -->
        <section class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Canales Suscritos (<?= count($channels) ?>)</h2>
            
            <?php if (!empty($channels)): ?>
                <div class="overflow-x-auto channel-scroll">
                    <div class="flex space-x-4 pb-4">
                        <?php foreach ($channels as $channel): ?>
                            <a href="channel.php?id=<?= $channel['id'] ?>" 
                               class="flex flex-col items-center space-y-2 min-w-[120px] p-3 bg-white rounded-lg hover:bg-gray-50 transition duration-200">
                                <img src="<?= $channel['avatar'] ? htmlspecialchars($channel['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($channel['username']) ?>" 
                                     alt="<?= htmlspecialchars($channel['username']) ?>" 
                                     class="w-16 h-16 rounded-full">
                                <span class="font-medium text-sm text-center line-clamp-2">
                                    <?= htmlspecialchars($channel['username']) ?>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <?= number_format($channel['subscribers_count']) ?> subs
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Videos de Suscripciones -->
        <section>
            <h2 class="text-xl font-bold text-gray-900 mb-4">Videos Recientes</h2>

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
                                            <h3 class="font-semibold text-gray-900 line-clamp-2 mb-1 group-hover:text-blue-600">
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
                <?php if (empty($channels)): ?>
                    <div class="text-center py-16 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-users text-7xl text-gray-300 mb-6"></i>
                        <h2 class="text-2xl font-semibold text-gray-800 mb-3">No tienes suscripciones</h2>
                        <p class="text-gray-600 mb-6">Suscríbete a canales para ver sus videos aquí</p>
                        <a href="index.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-full hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-compass mr-2"></i>
                            Explorar canales
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-video text-7xl text-gray-300 mb-6"></i>
                        <h2 class="text-2xl font-semibold text-gray-800 mb-3">No hay videos nuevos</h2>
                        <p class="text-gray-600">Los canales que sigues no han publicado videos recientemente</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
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
                        if (subscribersCount) {
                            subscribersCount.textContent = data.subscribers_count;
                        }
                        
                        if (data.isSubscribed) {
                            button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                            button.classList.add('bg-gray-200', 'text-gray-800', 'hover:bg-gray-300');
                            button.innerHTML = '<i class="fas fa-bell mr-2"></i>Suscrito';
                        } else {
                            button.classList.remove('bg-gray-200', 'text-gray-800', 'hover:bg-gray-300');
                            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                            button.innerHTML = '<i class="fas fa-plus mr-2"></i>Suscribirse';
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
    </script>
</body>
</html> 