<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo incluir database.php si no ha sido incluido antes
if (!defined('DB_INCLUDED')) {
    include_once '../config/database.php';
    define('DB_INCLUDED', true);
}

// Obtener el nombre del archivo actual
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Header -->
<header class="bg-white shadow-sm fixed w-full top-0 z-50">
    <div class="container mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <button id="sidebarToggle" class="p-2 hover:bg-gray-100 rounded-full">
                    <i class="fas fa-bars text-gray-700"></i>
                </button>
                <a href="./index.php" class="flex items-center space-x-2">
                    <img src="./assets/img/Logo_VideosNet.png" alt="VideoNetBandera Logo" class="h-14 w-34 object-contain">
                </a>
            </div>

            <?php if ($current_page === 'index.php'): ?>
            <div class="flex-1 max-w-2xl mx-8">
                <form action="./search.php" method="GET" class="flex">
                    <input type="text" name="q" placeholder="Buscar videos..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-l-full focus:outline-none focus:border-yutube-500 focus:ring-1 focus:ring-yutube-500">
                    <button type="submit" class="px-6 py-2 bg-gray-100 text-gray-600 rounded-r-full hover:bg-gray-200 border border-l-0 border-gray-300">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="flex-1"></div> <!-- Espaciador para mantener el layout -->
            <?php endif; ?>

            <div class="flex items-center space-x-6">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="./upload.php" class="flex items-center space-x-2 bg-yutube-50 text-yutube-600 px-4 py-2 rounded-full hover:bg-yutube-100 transition duration-200">
                        <i class="fas fa-video"></i>
                        <span class="hidden sm:inline">Crear</span>
                    </a>
                    <div class="relative group">
                        <button id="userMenuButton" class="flex items-center space-x-2 hover:bg-gray-100 rounded-full p-2 transition duration-200">
                            <?php
                            $avatarUrl = isset($_SESSION['avatar']) && $_SESSION['avatar'] 
                                ? htmlspecialchars($_SESSION['avatar'])
                                : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['first_name'] ?? 'User');
                            ?>
                            <img src="<?= $avatarUrl ?>" 
                                 alt="Avatar" class="w-8 h-8 rounded-full">
                            <span class="text-sm font-medium hidden sm:inline"><?= htmlspecialchars($_SESSION['first_name'] ?? '') ?></span>
                        </button>
                        <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden">
                            <a href="./my-videos.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-film mr-2"></i>Mis Videos
                            </a>
                            <a href="./logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="./login.php" class="text-yutube-600 hover:text-yutube-700 font-medium">Iniciar Sesión</a>
                    <a href="./register.php" class="bg-yutube-600 text-white px-4 py-2 rounded-full hover:bg-yutube-700 transition duration-200">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Sidebar -->
<aside class="fixed left-0 top-16 h-[calc(100vh-4rem)] w-64 bg-white shadow-md transform -translate-x-full transition-transform duration-200 ease-in-out z-40" id="sidebar">
    <nav class="py-4">
        <a href="./index.php" class="flex items-center space-x-4 px-6 py-3 text-gray-700 hover:bg-gray-100">
            <i class="fas fa-home text-xl"></i>
            <span>Inicio</span>
        </a>
        <a href="./trending.php" class="flex items-center space-x-4 px-6 py-3 text-gray-700 hover:bg-gray-100">
            <i class="fas fa-fire text-xl"></i>
            <span>Tendencias</span>
        </a>
        <a href="./subscriptions.php" class="flex items-center space-x-4 px-6 py-3 text-gray-700 hover:bg-gray-100">
            <i class="fas fa-play-circle text-xl"></i>
            <span>Suscripciones</span>
        </a>

        <?php if (isset($_SESSION['user_id'])): 
            // Obtener información del usuario y sus estadísticas
            $stmt = $pdo->prepare("
                SELECT 
                    u.*,
                    (SELECT COUNT(*) FROM subscriptions WHERE channel_id = u.id) as subscribers_count,
                    (SELECT COUNT(*) FROM videos WHERE user_id = u.id AND status = 'active') as videos_count
                FROM users u 
                WHERE u.id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user_stats = $stmt->fetch();
        ?>
            <hr class="my-2 border-gray-200">
            
            <!-- Mi Canal -->
            <a href="./channel.php?id=<?= $_SESSION['user_id'] ?>" class="flex items-center space-x-4 px-6 py-3 text-gray-700 hover:bg-gray-100">
                <img src="<?= isset($_SESSION['avatar']) && $_SESSION['avatar'] ? htmlspecialchars($_SESSION['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) ?>" 
                     alt="Avatar" 
                     class="w-8 h-8 rounded-full">
                <div class="flex-1">
                    <span class="block font-medium">Mi Canal</span>
                    <span class="text-sm text-gray-500"><?= number_format($user_stats['subscribers_count']) ?> suscriptores</span>
                </div>
            </a>

            <!-- Estadísticas -->
            <div class="px-6 py-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                    Mis Estadísticas
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">
                            <i class="fas fa-users mr-2 text-yutube-600"></i>
                            Suscriptores
                        </span>
                        <span class="font-medium"><?= number_format($user_stats['subscribers_count']) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">
                            <i class="fas fa-video mr-2 text-yutube-600"></i>
                            Videos
                        </span>
                        <span class="font-medium"><?= number_format($user_stats['videos_count']) ?></span>
                    </div>
                </div>
            </div>

            <hr class="my-2 border-gray-200">
            
            <!-- Enlaces de Usuario -->
            <a href="./my-videos.php" class="flex items-center space-x-4 px-6 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fas fa-film text-xl"></i>
                <span>Mis Videos</span>
            </a>
            <a href="./upload.php" class="flex items-center space-x-4 px-6 py-3 text-gray-700 hover:bg-gray-100">
                <i class="fas fa-upload text-xl"></i>
                <span>Subir Video</span>
            </a>
            <a href="./logout.php" class="flex items-center space-x-4 px-6 py-3 text-red-600 hover:bg-gray-100">
                <i class="fas fa-sign-out-alt text-xl"></i>
                <span>Cerrar Sesión</span>
            </a>
        <?php endif; ?>
    </nav>
</aside>

<!-- Overlay para cerrar el sidebar en móviles -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/30 opacity-0 invisible transition-opacity duration-200 z-30"></div>

<script>
    // Toggle sidebar con overlay
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const mainContent = document.getElementById('mainContent');
        
        sidebar.classList.toggle('-translate-x-full');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            // Ocultar overlay
            overlay.classList.add('invisible', 'opacity-0');
            if (mainContent) {
                mainContent.classList.remove('md:ml-64');
            }
        } else {
            // Mostrar overlay
            overlay.classList.remove('invisible', 'opacity-0');
            if (mainContent) {
                mainContent.classList.add('md:ml-64');
            }
        }
    });

    // Cerrar sidebar al hacer click en el overlay
    document.getElementById('sidebarOverlay').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = this;
        const mainContent = document.getElementById('mainContent');
        
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('invisible', 'opacity-0');
        if (mainContent) {
            mainContent.classList.remove('md:ml-64');
        }
    });

    // Toggle user menu
    const userMenuButton = document.getElementById('userMenuButton');
    const userMenu = document.getElementById('userMenu');
    
    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
        });

        // Cerrar el menú cuando se hace click fuera
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target) && !userMenuButton.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    }
</script>