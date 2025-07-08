<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Obtener información actual del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Actualizar información básica
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $banner_color = trim($_POST['banner_color'] ?? '#dc2626');

        // Validaciones
        if (empty($username) || empty($email)) {
            throw new Exception('El nombre de usuario y email son obligatorios');
        }

        if ($username !== $user['username']) {
            // Verificar si el nombre de usuario ya existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception('Este nombre de usuario ya está en uso');
            }
        }

        if ($email !== $user['email']) {
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception('Este email ya está registrado');
            }
        }

        // Procesar avatar si se subió uno nuevo
        $avatar_path = $user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
                throw new Exception('El formato del avatar debe ser JPG, PNG o GIF');
            }

            if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) { // 5MB
                throw new Exception('El avatar no debe superar los 5MB');
            }

            $avatar_dir = '../uploads/avatars/';
            if (!is_dir($avatar_dir)) {
                mkdir($avatar_dir, 0777, true);
            }

            $avatar_name = uniqid('avatar_') . '.' . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar_path = $avatar_dir . $avatar_name;

            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                throw new Exception('Error al subir el avatar');
            }

            // Eliminar avatar anterior si existe
            if ($user['avatar'] && file_exists($user['avatar'])) {
                unlink($user['avatar']);
            }
        }

        // Procesar banner si se subió uno nuevo
        $banner_path = $user['banner_image'];
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['banner']['type'], $allowed_types)) {
                throw new Exception('El formato del banner debe ser JPG, PNG o GIF');
            }

            if ($_FILES['banner']['size'] > 10 * 1024 * 1024) { // 10MB
                throw new Exception('El banner no debe superar los 10MB');
            }

            $banner_dir = '../uploads/banners/';
            if (!is_dir($banner_dir)) {
                mkdir($banner_dir, 0777, true);
            }

            $banner_name = uniqid('banner_') . '.' . pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
            $banner_path = $banner_dir . $banner_name;

            if (!move_uploaded_file($_FILES['banner']['tmp_name'], $banner_path)) {
                throw new Exception('Error al subir el banner');
            }

            // Eliminar banner anterior si existe
            if ($user['banner_image'] && file_exists($user['banner_image'])) {
                unlink($user['banner_image']);
            }
        }

        // Actualizar la información del usuario
        $sql = "UPDATE users SET username = ?, email = ?, bio = ?, avatar = ?, banner_color = ?, banner_image = ? WHERE id = ?";
        $params = [$username, $email, $bio, $avatar_path, $banner_color, $banner_path, $_SESSION['user_id']];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Actualizar la sesión
        $_SESSION['username'] = $username;
        $_SESSION['avatar'] = $avatar_path;

        $pdo->commit();
        $success_message = 'Perfil actualizado correctamente';
        
        // Recargar información del usuario
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Perfil - videoNetBandera</title>
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
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'header.php'; ?>

    <main class="container mx-auto px-4 py-8 mt-16">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">Configuración del Perfil</h1>
                        <a href="channel.php?id=<?= $_SESSION['user_id'] ?>" class="text-yutube-600 hover:text-yutube-700">
                            <i class="fas fa-external-link-alt mr-2"></i>Ver mi canal
                        </a>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                <?= htmlspecialchars($success_message) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <?= htmlspecialchars($error_message) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <!-- Avatar -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Avatar
                            </label>
                            <div class="flex items-center space-x-6">
                                <div class="relative">
                                    <img src="<?= $user['avatar'] ? htmlspecialchars($user['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) ?>" 
                                         alt="Avatar" 
                                         class="w-24 h-24 rounded-full object-cover border-2 border-gray-200"
                                         id="avatarPreview">
                                    <button type="button" 
                                            onclick="document.getElementById('avatar').click()" 
                                            class="absolute bottom-0 right-0 bg-white rounded-full p-2 shadow-md hover:bg-gray-100">
                                        <i class="fas fa-camera text-gray-600"></i>
                                    </button>
                                </div>
                                <input type="file" 
                                       id="avatar" 
                                       name="avatar" 
                                       accept="image/*" 
                                       class="hidden"
                                       onchange="previewAvatar(this)">
                                <div class="text-sm text-gray-600">
                                    <p>Sube una foto de perfil</p>
                                    <p class="mt-1">JPG, PNG o GIF (máx. 5MB)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Banner -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Banner del Canal
                            </label>
                            <div class="space-y-4">
                                <div class="relative">
                                    <div class="aspect-[3/1] rounded-lg overflow-hidden bg-gray-100">
                                        <?php if ($user['banner_image']): ?>
                                            <img src="<?= htmlspecialchars($user['banner_image']) ?>" 
                                                 alt="Banner" 
                                                 class="w-full h-full object-cover"
                                                 id="bannerPreview">
                                        <?php else: ?>
                                            <div class="w-full h-full" 
                                                 style="background-color: <?= htmlspecialchars($user['banner_color'] ?? '#dc2626') ?>"
                                                 id="bannerPreview">
                                            </div>
                                        <?php endif; ?>
                                        <button type="button" 
                                                onclick="document.getElementById('banner').click()" 
                                                class="absolute bottom-4 right-4 bg-black/50 text-white px-4 py-2 rounded-lg hover:bg-black/70 transition duration-200">
                                            <i class="fas fa-image mr-2"></i>
                                            Cambiar Banner
                                        </button>
                                    </div>
                                    <input type="file" 
                                           id="banner" 
                                           name="banner" 
                                           accept="image/*" 
                                           class="hidden"
                                           onchange="previewBanner(this)">
                                </div>
                                <div class="flex items-center space-x-4">
                                    <input type="color" 
                                           name="banner_color" 
                                           value="<?= htmlspecialchars($user['banner_color'] ?? '#dc2626') ?>"
                                           class="w-12 h-12 rounded border-0 cursor-pointer"
                                           onchange="updateBannerColor(this)">
                                    <span class="text-sm text-gray-600">
                                        Color de fondo (se usa cuando no hay imagen)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Información Básica -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="username" class="block text-sm font-medium text-gray-700">
                                    Nombre de Usuario <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       value="<?= htmlspecialchars($user['username']) ?>" 
                                       required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yutube-500 focus:border-yutube-500">
                            </div>

                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" 
                                       required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yutube-500 focus:border-yutube-500">
                            </div>
                        </div>

                        <!-- Biografía -->
                        <div class="space-y-2">
                            <label for="bio" class="block text-sm font-medium text-gray-700">
                                Biografía
                            </label>
                            <textarea id="bio" 
                                      name="bio" 
                                      rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yutube-500 focus:border-yutube-500"
                                      placeholder="Cuéntanos sobre ti..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <button type="button" 
                                    onclick="window.location.href='channel.php?id=<?= $_SESSION['user_id'] ?>'"
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-yutube-600 text-white rounded-lg hover:bg-yutube-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yutube-500">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Por favor, selecciona una imagen en formato JPG, PNG o GIF');
                    input.value = '';
                    return;
                }

                // Validar tamaño
                if (file.size > 5 * 1024 * 1024) {
                    alert('La imagen no debe superar los 5MB');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }

        function previewBanner(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Por favor, selecciona una imagen en formato JPG, PNG o GIF');
                    input.value = '';
                    return;
                }

                // Validar tamaño
                if (file.size > 10 * 1024 * 1024) {
                    alert('La imagen no debe superar los 10MB');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('bannerPreview');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover';
                        img.id = 'bannerPreview';
                        preview.parentNode.replaceChild(img, preview);
                    }
                }
                reader.readAsDataURL(file);
            }
        }

        function updateBannerColor(input) {
            const preview = document.getElementById('bannerPreview');
            if (!preview.src) { // Si no hay imagen
                preview.style.backgroundColor = input.value;
            }
        }
    </script>
</body>
</html> 