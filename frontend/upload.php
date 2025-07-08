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
    
    if (!isset($_FILES['video']) || !isset($_FILES['thumbnail'])) {
        $response['error'] = 'Debes subir tanto el video como la miniatura.';
    } else {
        $video = $_FILES['video'];
        $thumbnail = $_FILES['thumbnail'];
        
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
        } elseif ($thumbnail['error'] !== UPLOAD_ERR_OK) {
            switch ($thumbnail['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $response['error'] = "La miniatura es demasiado grande.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $response['error'] = "La miniatura excede el tamaño máximo permitido.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $response['error'] = "La miniatura se subió parcialmente. Intenta de nuevo.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $response['error'] = "No se seleccionó ninguna miniatura.";
                    break;
                default:
                    $response['error'] = "Error al subir la miniatura (código: " . $thumbnail['error'] . ")";
            }
        } elseif ($video['size'] == 0) {
            $response['error'] = 'El archivo de video está vacío o no se pudo leer.';
        } elseif ($thumbnail['size'] == 0) {
            $response['error'] = 'El archivo de miniatura está vacío o no se pudo leer.';
        } elseif (!in_array($thumbnail['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
            $response['error'] = 'El formato de la miniatura debe ser JPG, PNG o GIF.';
        } elseif ($thumbnail['size'] > 5 * 1024 * 1024) { // 5MB max
            $response['error'] = 'La miniatura no debe superar los 5MB.';
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
            
            // Procesar la miniatura
            $thumb_extension = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
            $thumb_name = uniqid('thumb_') . '.' . $thumb_extension;
            $thumbnail_path = $thumbnails_dir . $thumb_name;
            
            // Primero intentamos mover la miniatura
            if (!move_uploaded_file($thumbnail['tmp_name'], $thumbnail_path)) {
                $response['error'] = 'No se pudo guardar la miniatura.';
            } 
            // Si la miniatura se movió correctamente, intentamos mover el video
            elseif (!move_uploaded_file($video['tmp_name'], $video_path)) {
                $response['error'] = 'No se pudo guardar el video.';
                // Si falla el video, eliminamos la miniatura
                if (file_exists($thumbnail_path)) {
                    unlink($thumbnail_path);
                }
            } else {
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
                    if (file_exists($thumbnail_path)) unlink($thumbnail_path);
                }
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

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
            height: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            border: 2px dashed #e5e7eb;
            border-radius: 0.5rem;
            background-color: #f9fafb;
            transition: all 0.3s ease;
            height: 100%;
            min-height: 200px;
        }

        .file-input-button:hover {
            border-color: #dc2626;
            background-color: #fef2f2;
        }

        .progress-bar-animation {
            animation: progress-bar-stripes 1s linear infinite;
            background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent);
            background-size: 1rem 1rem;
        }

        @keyframes progress-bar-stripes {
            from { background-position: 1rem 0; }
            to { background-position: 0 0; }
        }

        .upload-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .upload-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-16">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b">
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-upload mr-3 text-yutube-600"></i>
                    Subir Video
                </h1>
                <a href="index.php" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>

            <!-- Información del servidor en una línea -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <span class="font-semibold text-blue-800">Límites:</span>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center">
                            <i class="fas fa-upload text-blue-500 mr-2"></i>
                            <span class="text-sm text-gray-600">Upload: <strong class="text-blue-700"><?= $max_upload ?></strong></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-file-alt text-blue-500 mr-2"></i>
                            <span class="text-sm text-gray-600">POST: <strong class="text-blue-700"><?= $max_post ?></strong></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-memory text-blue-500 mr-2"></i>
                            <span class="text-sm text-gray-600">RAM: <strong class="text-blue-700"><?= $memory_limit ?></strong></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock text-blue-500 mr-2"></i>
                            <span class="text-sm text-gray-600">Tiempo: <strong class="text-blue-700"><?= $max_execution_time ?>s</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                    <strong>Error:</strong>
                </div>
                <p><?= htmlspecialchars($error) ?></p>
                
                <?php if (strpos($error, 'demasiado grande') !== false || strpos($error, 'Límite del servidor') !== false): ?>
                <div class="mt-4 p-4 bg-yellow-50 border border-yellow-300 rounded-lg">
                    <div class="flex items-center justify-between">
                        <p class="text-yellow-800 text-sm flex items-center">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                            <strong>Solución:</strong> Necesitas aumentar los límites de XAMPP para subir videos grandes.
                        </p>
                        <a href="fix-xampp-limits.php" class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition duration-200">
                            <i class="fas fa-wrench mr-2"></i>
                            Configurar XAMPP
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form id="uploadForm" method="POST" enctype="multipart/form-data">
                <div class="upload-grid">
                    <!-- Columna Izquierda: Información del Video -->
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Información del Video</h2>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                        Título del Video <span class="text-yutube-600">*</span>
                                    </label>
                                    <input type="text" name="title" id="title" required
                                           placeholder="Escribe un título descriptivo"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yutube-500 focus:border-yutube-500 transition duration-200">
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                        Descripción
                                    </label>
                                    <textarea name="description" id="description" rows="3"
                                              placeholder="Describe tu video..."
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yutube-500 focus:border-yutube-500 transition duration-200"></textarea>
                                </div>

                                <div>
                                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                        Categoría <span class="text-yutube-600">*</span>
                                    </label>
                                    <select name="category" id="category" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yutube-500 focus:border-yutube-500 transition duration-200">
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
                            </div>
                        </div>

                        <!-- Barra de Progreso -->
                        <div id="uploadProgress" class="hidden bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    <span id="uploadStatus">Preparando subida...</span>
                                </div>
                                <span id="uploadPercentage" class="font-semibold">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                                <div id="progressBar" class="bg-yutube-600 h-4 progress-bar-animation" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 mt-2">
                                <span id="uploadSpeed">Velocidad: -- MB/s</span>
                                <span id="timeRemaining">Tiempo restante: --:--</span>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Archivos Multimedia -->
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Archivos Multimedia</h2>
                            
                            <div class="space-y-6">
                                <!-- Video Upload -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Archivo de Video <span class="text-yutube-600">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="file-input-wrapper">
                                            <div class="file-input-button" id="videoDropzone">
                                                <div class="text-center">
                                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                                    <p class="text-gray-600">Arrastra y suelta tu video aquí o</p>
                                                    <button type="button" class="mt-2 px-4 py-2 bg-yutube-600 text-white rounded-lg hover:bg-yutube-700 transition duration-200">
                                                        Seleccionar Archivo
                                                    </button>
                                                    <p class="text-sm text-gray-500 mt-2">MP4, AVI, MOV (Máx. 2GB)</p>
                                                </div>
                                            </div>
                                            <input type="file" name="video" id="video" required accept="video/*"
                                                   class="hidden" onchange="showFileInfo(this, 'videoInfo')">
                                        </div>
                                    </div>
                                    <div id="videoInfo" class="hidden mt-3 p-3 bg-white rounded-lg border border-gray-200"></div>
                                </div>

                                <!-- Thumbnail Upload -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Miniatura del Video <span class="text-yutube-600">*</span>
                                    </label>
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div class="relative">
                                            <div class="file-input-wrapper">
                                                <div class="file-input-button" id="thumbnailDropzone">
                                                    <div class="text-center">
                                                        <i class="fas fa-image text-4xl text-gray-400 mb-3"></i>
                                                        <p class="text-gray-600">Arrastra y suelta tu miniatura aquí o</p>
                                                        <button type="button" class="mt-2 px-4 py-2 bg-yutube-600 text-white rounded-lg hover:bg-yutube-700 transition duration-200">
                                                            Seleccionar Imagen
                                                        </button>
                                                        <p class="text-sm text-gray-500 mt-2">JPG, PNG, GIF (1280x720)</p>
                                                    </div>
                                                </div>
                                                <input type="file" name="thumbnail" id="thumbnail" required accept="image/*"
                                                       class="hidden" onchange="previewThumbnail(this)">
                                            </div>
                                        </div>
                                        <div id="thumbnail-preview" class="hidden">
                                            <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                                                <img src="" alt="Vista previa de la miniatura" class="w-full h-full object-contain">
                                            </div>
                                            <button type="button" onclick="clearThumbnail()" 
                                                    class="mt-2 w-full px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 flex items-center justify-center">
                                                <i class="fas fa-trash-alt mr-2"></i>
                                                Eliminar miniatura
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex space-x-4 mt-6">
                    <button type="submit" class="flex-1 bg-yutube-600 text-white py-3 px-6 rounded-xl hover:bg-yutube-700 focus:outline-none focus:ring-2 focus:ring-yutube-500 focus:ring-offset-2 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-upload mr-2"></i>
                        Subir Video
                    </button>
                    <a href="index.php" class="flex-1 bg-gray-100 text-gray-700 py-3 px-6 rounded-xl hover:bg-gray-200 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Función para manejar el drag & drop
        function setupDropzone(dropzoneId, inputId) {
            const dropzone = document.getElementById(dropzoneId);
            const input = document.getElementById(inputId);

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                dropzone.classList.add('border-yutube-600', 'bg-yutube-50');
            }

            function unhighlight(e) {
                dropzone.classList.remove('border-yutube-600', 'bg-yutube-50');
            }

            dropzone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                input.files = files;
                
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            }

            // También manejar clicks en el dropzone
            dropzone.addEventListener('click', () => {
                input.click();
            });
        }

        // Configurar los dropzones
        setupDropzone('videoDropzone', 'video');
        setupDropzone('thumbnailDropzone', 'thumbnail');

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

        function clearThumbnail() {
            const input = document.getElementById('thumbnail');
            const preview = document.getElementById('thumbnail-preview');
            const previewImg = preview.querySelector('img');
            
            input.value = '';
            preview.classList.add('hidden');
            previewImg.src = '';
        }

        function showFileInfo(input, infoId) {
            const infoDiv = document.getElementById(infoId);
            if (input.files && input.files[0]) {
                const file = input.files[0];
                infoDiv.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <i class="fas fa-file-video text-2xl text-yutube-600"></i>
                            <div>
                                <p class="font-medium text-gray-900">${file.name}</p>
                                <p class="text-sm text-gray-500">
                                    <span class="mr-2">${formatSize(file.size)}</span>
                                    <span>${file.type}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="clearFile('${input.id}', '${infoId}')" 
                                class="text-gray-400 hover:text-gray-600 p-1">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                infoDiv.classList.remove('hidden');
            } else {
                infoDiv.classList.add('hidden');
            }
        }

        function clearFile(inputId, infoId) {
            const input = document.getElementById(inputId);
            input.value = '';
            document.getElementById(infoId).classList.add('hidden');
        }

        function previewThumbnail(input) {
            const preview = document.getElementById('thumbnail-preview');
            const previewImg = preview.querySelector('img');
            const file = input.files[0];

            if (file) {
                // Validar el tipo de archivo
                if (!file.type.startsWith('image/')) {
                    alert('Por favor, selecciona una imagen válida (JPG, PNG o GIF)');
                    input.value = '';
                    preview.classList.add('hidden');
                    return;
                }

                // Validar el tamaño del archivo (máximo 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('La imagen es demasiado grande. El tamaño máximo es 5MB');
                    input.value = '';
                    preview.classList.add('hidden');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.classList.remove('hidden');

                    // Validar las dimensiones de la imagen silenciosamente
                    const img = new Image();
                    img.onload = function() {
                        // Solo validamos internamente, sin mostrar mensaje
                        console.log(`Dimensiones de la miniatura: ${this.width}x${this.height}`);
                    }
                    img.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
            }
        }

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