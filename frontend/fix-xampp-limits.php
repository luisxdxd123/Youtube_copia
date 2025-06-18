<?php
/**
 * Script para configurar XAMPP para subir videos grandes
 * Ejecuta: http://localhost/yutu/frontend/fix-xampp-limits.php
 */

// Intentar configurar dinámicamente
ini_set('upload_max_filesize', '2048M');
ini_set('post_max_size', '2048M');
ini_set('max_execution_time', 600);
ini_set('memory_limit', '1024M');

// Detectar rutas posibles de XAMPP
$possible_paths = [
    'C:\xampp\php\php.ini',
    'C:\XAMPP\php\php.ini', 
    'D:\xampp\php\php.ini',
    'E:\xampp\php\php.ini',
    'C:\wamp\bin\php\php.ini',
    'C:\wamp64\bin\php\php.ini'
];

$current_ini = php_ini_loaded_file();
$xampp_ini = null;

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $xampp_ini = $path;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar XAMPP para Videos Grandes - YuTube</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">🔧 Configurar XAMPP para Videos de 2GB</h1>
        
        <!-- Estado actual -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold text-blue-800 mb-3">Estado Actual:</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><strong>Upload Max:</strong> <span class="font-mono"><?= ini_get('upload_max_filesize') ?></span></div>
                <div><strong>Post Max:</strong> <span class="font-mono"><?= ini_get('post_max_size') ?></span></div>
                <div><strong>Memory Limit:</strong> <span class="font-mono"><?= ini_get('memory_limit') ?></span></div>
                <div><strong>Max Execution:</strong> <span class="font-mono"><?= ini_get('max_execution_time') ?>s</span></div>
            </div>
        </div>

        <!-- Información de archivos -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold text-yellow-800 mb-3">Archivos de Configuración:</h2>
            <div class="space-y-2 text-sm">
                <div><strong>php.ini actual:</strong> <span class="font-mono text-xs"><?= $current_ini ?: 'No encontrado' ?></span></div>
                <?php if ($xampp_ini): ?>
                <div><strong>XAMPP php.ini encontrado:</strong> <span class="font-mono text-xs text-green-600"><?= $xampp_ini ?></span></div>
                <?php else: ?>
                <div class="text-red-600"><strong>XAMPP php.ini:</strong> No encontrado en rutas comunes</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Instrucciones paso a paso -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-green-800 mb-4">📋 Instrucciones para Configurar XAMPP:</h2>
            
            <div class="space-y-4">
                <div class="bg-white border border-green-300 rounded p-4">
                    <h3 class="font-semibold text-green-700 mb-2">Paso 1: Encontrar el archivo php.ini</h3>
                    <ol class="list-decimal list-inside space-y-1 text-sm">
                        <li>Abre el <strong>Panel de Control de XAMPP</strong></li>
                        <li>Al lado de "Apache" haz clic en <strong>"Config"</strong></li>
                        <li>Selecciona <strong>"PHP (php.ini)"</strong></li>
                        <?php if ($xampp_ini): ?>
                        <li class="text-green-600">✅ <strong>O edita directamente:</strong> <code><?= $xampp_ini ?></code></li>
                        <?php endif; ?>
                    </ol>
                </div>

                <div class="bg-white border border-green-300 rounded p-4">
                    <h3 class="font-semibold text-green-700 mb-2">Paso 2: Modificar estos valores</h3>
                    <div class="bg-gray-100 p-3 rounded font-mono text-sm space-y-1">
                        <div>upload_max_filesize = 2048M</div>
                        <div>post_max_size = 2048M</div>
                        <div>max_execution_time = 600</div>
                        <div>max_input_time = 600</div>
                        <div>memory_limit = 1024M</div>
                        <div>max_file_uploads = 20</div>
                    </div>
                </div>

                <div class="bg-white border border-green-300 rounded p-4">
                    <h3 class="font-semibold text-green-700 mb-2">Paso 3: Reiniciar Apache</h3>
                    <ol class="list-decimal list-inside space-y-1 text-sm">
                        <li>En el Panel de XAMPP, haz clic en <strong>"Stop"</strong> al lado de Apache</li>
                        <li>Espera unos segundos</li>
                        <li>Haz clic en <strong>"Start"</strong> para reiniciar Apache</li>
                        <li>Recarga esta página para verificar los cambios</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Verificación automática -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold text-purple-800 mb-3">🔍 Verificación Automática:</h2>
            <div class="space-y-2 text-sm">
                <?php
                $upload_ok = (int)ini_get('upload_max_filesize') >= 2048 || strpos(ini_get('upload_max_filesize'), 'G') !== false;
                $post_ok = (int)ini_get('post_max_size') >= 2048 || strpos(ini_get('post_max_size'), 'G') !== false;
                $memory_ok = (int)ini_get('memory_limit') >= 1024 || strpos(ini_get('memory_limit'), 'G') !== false;
                $time_ok = (int)ini_get('max_execution_time') >= 300;
                ?>
                <div class="flex items-center space-x-2">
                    <span class="<?= $upload_ok ? 'text-green-600' : 'text-red-600' ?>"><?= $upload_ok ? '✅' : '❌' ?></span>
                    <span>Upload Max Filesize: <?= ini_get('upload_max_filesize') ?></span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="<?= $post_ok ? 'text-green-600' : 'text-red-600' ?>"><?= $post_ok ? '✅' : '❌' ?></span>
                    <span>Post Max Size: <?= ini_get('post_max_size') ?></span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="<?= $memory_ok ? 'text-green-600' : 'text-red-600' ?>"><?= $memory_ok ? '✅' : '❌' ?></span>
                    <span>Memory Limit: <?= ini_get('memory_limit') ?></span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="<?= $time_ok ? 'text-green-600' : 'text-red-600' ?>"><?= $time_ok ? '✅' : '❌' ?></span>
                    <span>Max Execution Time: <?= ini_get('max_execution_time') ?>s</span>
                </div>
            </div>
            
            <?php if ($upload_ok && $post_ok && $memory_ok && $time_ok): ?>
            <div class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                <strong>🎉 ¡Perfecto! Tu configuración está lista para videos de 2GB</strong>
            </div>
            <?php else: ?>
            <div class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                <strong>⚠️ Configuración insuficiente. Sigue los pasos de arriba para configurar XAMPP.</strong>
            </div>
            <?php endif; ?>
        </div>

        <!-- Botones de acción -->
        <div class="flex space-x-4">
            <a href="upload.php" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700">
                🎬 Probar Subir Video
            </a>
            <a href="test-upload.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                🔧 Test de Upload
            </a>
            <a href="../index.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700">
                🏠 Volver a Inicio
            </a>
            <button onclick="location.reload()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                🔄 Recargar Verificación
            </button>
        </div>
    </div>
</body>
</html> 