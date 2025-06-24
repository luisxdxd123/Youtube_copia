<?php
session_start();

// CONFIGURAR LÍMITES DE PHP DINÁMICAMENTE PARA VIDEOS GRANDES
ini_set('upload_max_filesize', '2048M');
ini_set('post_max_size', '2048M');
ini_set('max_execution_time', 600);
ini_set('max_input_time', 600);
ini_set('memory_limit', '1024M');
ini_set('max_file_uploads', 20);

include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$debug_info = [];

// Información de debug para mostrar al usuario
$max_upload = ini_get('upload_max_filesize');
$max_post = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');

if ($_POST) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    
    // Debug: verificar si se recibió el archivo
    $debug_info[] = "POST recibido";
    $debug_info[] = "Título: " . ($title ?: "vacío");
    
    if (!isset($_FILES['video'])) {
        $error = 'No se recibió ningún archivo. Verifica que el formulario permita archivos.';
        $debug_info[] = "ERROR: \$_FILES['video'] no existe";
    } else {
        $video = $_FILES['video'];
        $debug_info[] = "Archivo recibido: " . $video['name'];
        $debug_info[] = "Tamaño: " . ($video['size'] ?? 0) . " bytes";
        $debug_info[] = "Tipo: " . ($video['type'] ?? 'desconocido');
        $debug_info[] = "Error code: " . ($video['error'] ?? 'desconocido');
        
        if (empty($title)) {
            $error = 'El título es obligatorio.';
        } elseif ($video['error'] !== UPLOAD_ERR_OK) {
            // Detalles específicos del error
            switch ($video['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error = "El archivo es demasiado grande. Límite del servidor: $max_upload";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "El archivo excede el tamaño máximo permitido por el formulario.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "El archivo se subió parcialmente. Intenta de nuevo.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "No se seleccionó ningún archivo.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = "Error del servidor: falta directorio temporal.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error = "Error del servidor: no se puede escribir el archivo.";
                    break;
                default:
                    $error = "Error desconocido al subir el archivo (código: " . $video['error'] . ")";
            }
        } elseif ($video['size'] == 0) {
            $error = 'El archivo está vacío o no se pudo leer.';
        } else {
            // Crear directorios si no existen
            $upload_dir = '../uploads/videos/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $error = 'No se pudo crear el directorio de videos.';
                } else {
                    $debug_info[] = "Directorio creado: $upload_dir";
                }
            } else {
                $debug_info[] = "Directorio existe: $upload_dir";
            }
            
            if (!$error) {
                // Verificar permisos de escritura
                if (!is_writable($upload_dir)) {
                    $error = 'No hay permisos de escritura en el directorio de videos.';
                } else {
                    $debug_info[] = "Permisos de escritura OK";
                    
                    // Generar nombre único para el archivo
                    $file_extension = pathinfo($video['name'], PATHINFO_EXTENSION);
                    $video_name = uniqid('video_') . '.' . $file_extension;
                    $video_path = $upload_dir . $video_name;
                    
                    $debug_info[] = "Intentando mover archivo a: $video_path";
                    
                    if (move_uploaded_file($video['tmp_name'], $video_path)) {
                        $debug_info[] = "Archivo movido exitosamente";
                        
                        // Verificar que el archivo se creó correctamente
                        if (file_exists($video_path)) {
                            $file_size = filesize($video_path);
                            $debug_info[] = "Archivo guardado, tamaño: $file_size bytes";
                            
                            $duration = 120; // Valor por defecto
                            
                            // Guardar en base de datos
                            $stmt = $pdo->prepare("INSERT INTO videos (user_id, title, description, video_path, duration, category) VALUES (?, ?, ?, ?, ?, ?)");
                            
                            if ($stmt->execute([$_SESSION['user_id'], $title, $description, $video_path, $duration, $category])) {
                                $video_id = $pdo->lastInsertId();
                                $debug_info[] = "Video guardado en BD con ID: $video_id";
                                header("Location: watch.php?v=$video_id");
                                exit;
                            } else {
                                $error = 'Error al guardar en la base de datos: ' . implode(', ', $stmt->errorInfo());
                                if (file_exists($video_path)) {
                                    unlink($video_path);
                                }
                            }
                        } else {
                            $error = 'El archivo no se guardó correctamente.';
                        }
                    } else {
                        $error = 'No se pudo mover el archivo al directorio de destino.';
                        $debug_info[] = "ERROR: move_uploaded_file falló";
                        $debug_info[] = "Archivo temporal: " . $video['tmp_name'];
                        $debug_info[] = "¿Existe archivo temporal? " . (file_exists($video['tmp_name']) ? 'SÍ' : 'NO');
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Video - videoNetBandera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <a href="../index.php" class="flex items-center space-x-2">
                <i class="fab fa-youtube text-red-600 text-2xl"></i>
                <span class="text-xl font-bold">videoNetBandera</span>
            </a>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Hola, <?= htmlspecialchars($_SESSION['first_name']) ?></span>
                <a href="logout.php" class="text-red-600 hover:text-red-800">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-8 flex items-center">
                <i class="fas fa-upload mr-3 text-red-600"></i>
                Subir Video
            </h1>

            <!-- Información del servidor -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-blue-800 mb-2">Límites del Servidor:</h3>
                <div class="text-sm text-blue-700 grid grid-cols-2 gap-2">
                    <div>📁 Upload máximo: <strong><?= $max_upload ?></strong></div>
                    <div>📝 POST máximo: <strong><?= $max_post ?></strong></div>
                    <div>🧠 Memoria: <strong><?= $memory_limit ?></strong></div>
                    <div>⏱️ Tiempo límite: <strong><?= $max_execution_time ?>s</strong></div>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                
                <?php if (strpos($error, 'demasiado grande') !== false || strpos($error, 'Límite del servidor') !== false): ?>
                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-300 rounded">
                    <p class="text-yellow-800 text-sm mb-2">
                        <strong>💡 Solución:</strong> Necesitas aumentar los límites de XAMPP para subir videos grandes.
                    </p>
                    <a href="fix-xampp-limits.php" class="inline-block bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 text-sm">
                        🔧 Configurar XAMPP para Videos de 2GB
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($debug_info)): ?>
            <div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded mb-6">
                <strong>Información de debug:</strong>
                <ul class="list-disc list-inside mt-2 text-sm">
                    <?php foreach ($debug_info as $info): ?>
                    <li><?= htmlspecialchars($info) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-video mr-2"></i>Archivo de Video *
                    </label>
                    <input type="file" name="video" accept="video/*" required
                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100"
                           onchange="showFileInfo(this)">
                    <p class="text-xs text-gray-500 mt-2">
                        Configurado para videos hasta 2GB. Todos los formatos soportados.
                        <?php if ((int)ini_get('upload_max_filesize') < 1000): ?>
                        <a href="fix-xampp-limits.php" class="text-yellow-600 underline">⚠️ Configurar límites</a>
                        <?php endif; ?>
                    </p>
                    <div id="fileInfo" class="text-sm text-gray-600 mt-2"></div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-heading mr-2"></i>Título *
                    </label>
                    <input type="text" id="title" name="title" required maxlength="255"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                           placeholder="Escribe un título atractivo para tu video">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-2"></i>Descripción
                    </label>
                    <textarea id="description" name="description" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              placeholder="Describe tu video..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2"></i>Categoría
                    </label>
                    <select id="category" name="category"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <option value="">Seleccionar categoría</option>
                        <option value="music">Música</option>
                        <option value="gaming">Gaming</option>
                        <option value="education">Educación</option>
                        <option value="entertainment">Entretenimiento</option>
                        <option value="sports">Deportes</option>
                        <option value="technology">Tecnología</option>
                    </select>
                </div>

                <div class="flex space-x-4 pt-6">
                    <button type="submit" 
                            class="flex-1 bg-red-600 text-white py-3 px-6 rounded-lg hover:bg-red-700 font-medium">
                        <i class="fas fa-upload mr-2"></i>
                        Subir Video
                    </button>
                    <a href="../index.php" 
                       class="flex-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 text-center">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showFileInfo(input) {
            const fileInfo = document.getElementById('fileInfo');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                fileInfo.innerHTML = `
                    <strong>Archivo seleccionado:</strong><br>
                    📁 Nombre: ${file.name}<br>
                    📏 Tamaño: ${sizeInMB} MB<br>
                    🎬 Tipo: ${file.type}
                `;
                fileInfo.className = 'text-sm text-green-600 mt-2 p-2 bg-green-50 rounded';
            } else {
                fileInfo.innerHTML = '';
            }
        }
    </script>
</body>
</html> 