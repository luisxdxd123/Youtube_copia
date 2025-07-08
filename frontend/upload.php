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

// Verificar si es una petición AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Función para enviar respuesta JSON
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'error' => null];
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    
    if (!isset($_FILES['video'])) {
        $response['error'] = 'No se recibió ningún archivo de video.';
    } else {
        $video = $_FILES['video'];
        
        if (empty($title)) {
            $response['error'] = 'El título es obligatorio.';
        } elseif ($video['error'] !== UPLOAD_ERR_OK) {
            switch ($video['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $response['error'] = "El archivo es demasiado grande. Límite del servidor: $max_upload";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $response['error'] = "El archivo excede el tamaño máximo permitido por el formulario.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $response['error'] = "El archivo se subió parcialmente. Intenta de nuevo.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $response['error'] = "No se seleccionó ningún archivo.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $response['error'] = "Error del servidor: falta directorio temporal.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $response['error'] = "Error del servidor: no se puede escribir el archivo.";
                    break;
                default:
                    $response['error'] = "Error desconocido al subir el archivo (código: " . $video['error'] . ")";
            }
        } elseif ($video['size'] == 0) {
            $response['error'] = 'El archivo está vacío o no se pudo leer.';
        } else {
            $upload_dir = '../uploads/videos/';
            $thumbnails_dir = '../uploads/thumbnails/';
            
            // Crear directorios si no existen
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            if (!is_dir($thumbnails_dir)) {
                mkdir($thumbnails_dir, 0777, true);
            }
            
            // Procesar el video
            $file_extension = pathinfo($video['name'], PATHINFO_EXTENSION);
            $video_name = uniqid('video_') . '.' . $file_extension;
            $video_path = $upload_dir . $video_name;
            
            // Variable para almacenar la ruta de la miniatura
            $thumbnail_path = null;
            
            // Procesar la miniatura si se proporcionó una
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $thumbnail = $_FILES['thumbnail'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!in_array($thumbnail['type'], $allowed_types)) {
                    $response['error'] = 'El formato de la miniatura no es válido. Use JPG, PNG o GIF.';
                } else {
                    $thumb_extension = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
                    $thumb_name = uniqid('thumb_') . '.' . $thumb_extension;
                    $thumbnail_path = $thumbnails_dir . $thumb_name;
                    
                    if (!move_uploaded_file($thumbnail['tmp_name'], $thumbnail_path)) {
                        $response['error'] = 'No se pudo guardar la miniatura.';
                    }
                }
            }
            
            if (!$response['error'] && move_uploaded_file($video['tmp_name'], $video_path)) {
                $duration = 120; // Valor por defecto
                
                // Guardar en base de datos
                $stmt = $pdo->prepare("INSERT INTO videos (user_id, title, description, video_path, thumbnail, duration, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$_SESSION['user_id'], $title, $description, $video_path, $thumbnail_path, $duration, $category])) {
                    $video_id = $pdo->lastInsertId();
                    $response['success'] = true;
                    $response['video_id'] = $video_id;
                } else {
                    $response['error'] = 'Error al guardar en la base de datos.';
                    if (file_exists($video_path)) unlink($video_path);
                    if ($thumbnail_path && file_exists($thumbnail_path)) unlink($thumbnail_path);
                }
            } else {
                if (!$response['error']) {
                    $response['error'] = 'No se pudo mover el archivo de video al directorio de destino.';
                }
                if ($thumbnail_path && file_exists($thumbnail_path)) unlink($thumbnail_path);
            }
        }
    }
    
    if ($isAjax) {
        sendJsonResponse($response);
    } else if ($response['success']) {
        header("Location: watch.php?v=" . $response['video_id']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Video - YuTube</title>
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

    <div class="container mx-auto px-4 py-8 mt-16 max-w-2xl">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-8 flex items-center">
                <i class="fas fa-upload mr-3 text-yutube-600"></i>
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

            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        Título del Video
                    </label>
                    <input type="text" name="title" id="title" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-yutube-500 focus:border-yutube-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Descripción
                    </label>
                    <textarea name="description" id="description" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-yutube-500 focus:border-yutube-500"></textarea>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                        Categoría
                    </label>
                    <select name="category" id="category" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-yutube-500 focus:border-yutube-500">
                        <option value="">Selecciona una categoría</option>
                        <option value="gaming">Gaming</option>
                        <option value="music">Música</option>
                        <option value="education">Educación</option>
                        <option value="entertainment">Entretenimiento</option>
                        <option value="sports">Deportes</option>
                        <option value="technology">Tecnología</option>
                        <option value="other">Otros</option>
                    </select>
                </div>

                <div>
                    <label for="video" class="block text-sm font-medium text-gray-700 mb-1">
                        Archivo de Video
                    </label>
                    <input type="file" name="video" id="video" required accept="video/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-yutube-500 focus:border-yutube-500"
                           onchange="showFileInfo(this, 'videoInfo')">
                    <p class="mt-1 text-sm text-gray-500">
                        Formatos aceptados: MP4, AVI, MOV, etc. Máximo 2GB
                    </p>
                    <div id="videoInfo" class="mt-2 hidden p-3 bg-gray-50 rounded-lg"></div>
                </div>

                <div>
                    <label for="thumbnail" class="block text-sm font-medium text-gray-700 mb-1">
                        Miniatura del Video (Opcional)
                    </label>
                    <input type="file" name="thumbnail" id="thumbnail" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-yutube-500 focus:border-yutube-500">
                    <p class="mt-1 text-sm text-gray-500">
                        Formatos aceptados: JPG, PNG, GIF. Tamaño recomendado: 1280x720 píxeles
                    </p>
                    <div id="thumbnail-preview" class="mt-2 hidden">
                        <img src="" alt="Vista previa de la miniatura" class="max-w-xs rounded-lg shadow-md">
                    </div>
                </div>

                <!-- Barra de Progreso -->
                <div id="uploadProgress" class="hidden">
                    <div class="mb-2 flex items-center justify-between text-sm text-gray-600">
                        <span id="uploadStatus">Preparando subida...</span>
                        <span id="uploadPercentage">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div id="progressBar" class="bg-yutube-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <div class="mt-2 text-sm text-gray-500">
                        <span id="uploadSpeed">Velocidad: -- MB/s</span>
                        <span class="mx-2">|</span>
                        <span id="timeRemaining">Tiempo restante: --:--</span>
                    </div>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-yutube-600 text-white py-3 px-6 rounded-lg hover:bg-yutube-700 focus:outline-none focus:ring-2 focus:ring-yutube-500 focus:ring-offset-2">
                        <i class="fas fa-upload mr-2"></i>
                        Subir Video
                    </button>
                    <a href="index.php" class="flex-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 text-center">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function formatSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function formatTime(seconds) {
            return new Date(seconds * 1000).toISOString().substr(14, 5);
        }

        function showFileInfo(input, infoId) {
            const infoDiv = document.getElementById(infoId);
            if (input.files && input.files[0]) {
                const file = input.files[0];
                infoDiv.innerHTML = `
                    <div class="text-sm">
                        <p class="font-semibold text-gray-700">📁 Archivo seleccionado:</p>
                        <p class="text-gray-600">Nombre: ${file.name}</p>
                        <p class="text-gray-600">Tamaño: ${formatSize(file.size)}</p>
                        <p class="text-gray-600">Tipo: ${file.type}</p>
                    </div>
                `;
                infoDiv.classList.remove('hidden');
            } else {
                infoDiv.classList.add('hidden');
            }
        }

        // Vista previa de la miniatura
        document.getElementById('thumbnail').addEventListener('change', function(e) {
            const preview = document.getElementById('thumbnail-preview');
            const previewImg = preview.querySelector('img');
            const file = e.target.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
            }
        });

        // Manejo de la subida con barra de progreso
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            const progressBar = document.getElementById('progressBar');
            const uploadStatus = document.getElementById('uploadStatus');
            const uploadProgress = document.getElementById('uploadProgress');
            const uploadPercentage = document.getElementById('uploadPercentage');
            const uploadSpeed = document.getElementById('uploadSpeed');
            const timeRemaining = document.getElementById('timeRemaining');
            
            uploadProgress.classList.remove('hidden');
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    const percentFormatted = percent.toFixed(2);
                    
                    progressBar.style.width = percent + '%';
                    uploadPercentage.textContent = percentFormatted + '%';
                    
                    // Calcular velocidad y tiempo restante
                    const elapsedTime = (Date.now() - startTime) / 1000;
                    const speed = e.loaded / elapsedTime; // bytes por segundo
                    const remainingBytes = e.total - e.loaded;
                    const remainingTime = remainingBytes / speed;
                    
                    uploadSpeed.textContent = `Velocidad: ${formatSize(speed)}/s`;
                    timeRemaining.textContent = `Tiempo restante: ${formatTime(remainingTime)}`;
                    
                    if (percent < 100) {
                        uploadStatus.textContent = 'Subiendo video...';
                    } else {
                        uploadStatus.textContent = 'Procesando...';
                    }
                }
            });

            const startTime = Date.now();
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                window.location.href = `watch.php?v=${response.video_id}`;
                            } else {
                                alert(response.error || 'Error al subir el video');
                                uploadProgress.classList.add('hidden');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('Error al procesar la respuesta del servidor');
                            uploadProgress.classList.add('hidden');
                        }
                    } else {
                        alert('Error en la conexión con el servidor');
                        uploadProgress.classList.add('hidden');
                    }
                }
            };
            
            xhr.open('POST', '', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });
    </script>
</body>
</html> 