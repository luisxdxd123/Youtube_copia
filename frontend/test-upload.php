<?php
/**
 * Archivo de prueba para verificar configuración de uploads
 * Ejecuta: http://localhost/yutu/frontend/test-upload.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Configuración - YuTube</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">🔧 Test de Configuración PHP</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Información de PHP -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-blue-800 mb-3">Configuración de PHP</h2>
                <div class="space-y-2 text-sm">
                    <div><strong>PHP Version:</strong> <?= PHP_VERSION ?></div>
                    <div><strong>Upload Max Filesize:</strong> <span class="font-mono"><?= ini_get('upload_max_filesize') ?></span></div>
                    <div><strong>Post Max Size:</strong> <span class="font-mono"><?= ini_get('post_max_size') ?></span></div>
                    <div><strong>Max Execution Time:</strong> <span class="font-mono"><?= ini_get('max_execution_time') ?>s</span></div>
                    <div><strong>Max Input Time:</strong> <span class="font-mono"><?= ini_get('max_input_time') ?>s</span></div>
                    <div><strong>Memory Limit:</strong> <span class="font-mono"><?= ini_get('memory_limit') ?></span></div>
                    <div><strong>Max File Uploads:</strong> <span class="font-mono"><?= ini_get('max_file_uploads') ?></span></div>
                </div>
            </div>
            
            <!-- Información de directorios -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-green-800 mb-3">Directorios</h2>
                <div class="space-y-2 text-sm">
                    <?php
                    $dirs = [
                        '../uploads' => '../uploads',
                        '../uploads/videos' => '../uploads/videos', 
                        '../uploads/thumbnails' => '../uploads/thumbnails'
                    ];
                    
                    foreach ($dirs as $label => $path) {
                        $exists = is_dir($path);
                        $writable = $exists && is_writable($path);
                        $status = $exists ? ($writable ? '✅ OK' : '⚠️ Sin permisos') : '❌ No existe';
                        echo "<div><strong>$label:</strong> <span class='font-mono'>$status</span></div>";
                    }
                    ?>
                </div>
            </div>
            
            <!-- Información del servidor -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-yellow-800 mb-3">Servidor Web</h2>
                <div class="space-y-2 text-sm">
                    <div><strong>Server Software:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido' ?></div>
                    <div><strong>Document Root:</strong> <span class="font-mono text-xs"><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Desconocido' ?></span></div>
                    <div><strong>Script Filename:</strong> <span class="font-mono text-xs"><?= __FILE__ ?></span></div>
                    <div><strong>Temp Directory:</strong> <span class="font-mono text-xs"><?= sys_get_temp_dir() ?></span></div>
                </div>
            </div>
            
            <!-- Extensiones de PHP -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-purple-800 mb-3">Extensiones PHP</h2>
                <div class="space-y-2 text-sm">
                    <?php
                    $extensions = ['fileinfo', 'gd', 'pdo', 'pdo_mysql', 'json', 'mbstring'];
                    foreach ($extensions as $ext) {
                        $loaded = extension_loaded($ext);
                        $status = $loaded ? '✅ Cargada' : '❌ No disponible';
                        echo "<div><strong>$ext:</strong> <span class='font-mono'>$status</span></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Test de formulario -->
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Test de Upload de Archivo</h2>
            
            <?php if ($_POST && isset($_FILES['test_file'])): ?>
            <div class="mb-4 p-4 <?= $_FILES['test_file']['error'] === UPLOAD_ERR_OK ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' ?> border rounded">
                <h3 class="font-semibold mb-2">Resultado del test:</h3>
                <div class="text-sm space-y-1">
                    <div><strong>Nombre:</strong> <?= htmlspecialchars($_FILES['test_file']['name']) ?></div>
                    <div><strong>Tamaño:</strong> <?= number_format($_FILES['test_file']['size']) ?> bytes</div>
                    <div><strong>Tipo:</strong> <?= htmlspecialchars($_FILES['test_file']['type']) ?></div>
                    <div><strong>Error:</strong> <?= $_FILES['test_file']['error'] ?> 
                        <?php 
                        switch($_FILES['test_file']['error']) {
                            case UPLOAD_ERR_OK: echo '(OK)'; break;
                            case UPLOAD_ERR_INI_SIZE: echo '(Archivo muy grande - ini)'; break;
                            case UPLOAD_ERR_FORM_SIZE: echo '(Archivo muy grande - form)'; break;
                            case UPLOAD_ERR_PARTIAL: echo '(Subida parcial)'; break;
                            case UPLOAD_ERR_NO_FILE: echo '(Sin archivo)'; break;
                            case UPLOAD_ERR_NO_TMP_DIR: echo '(Sin directorio temporal)'; break;
                            case UPLOAD_ERR_CANT_WRITE: echo '(No se puede escribir)'; break;
                            default: echo '(Error desconocido)';
                        }
                        ?>
                    </div>
                    <?php if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK): ?>
                    <div><strong>Archivo temporal:</strong> <?= $_FILES['test_file']['tmp_name'] ?></div>
                    <div><strong>¿Existe temporal?:</strong> <?= file_exists($_FILES['test_file']['tmp_name']) ? 'Sí' : 'No' ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selecciona un archivo para probar:</label>
                    <input type="file" name="test_file" class="w-full text-sm border border-gray-300 rounded p-2">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Probar Upload
                </button>
            </form>
        </div>
        
        <div class="mt-6 text-center">
            <a href="../index.php" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700">
                🏠 Volver a YuTube
            </a>
        </div>
    </div>
</body>
</html> 