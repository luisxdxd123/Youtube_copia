<?php
/**
 * Script de instalación automática para YuTube Clone
 * Ejecuta este archivo una sola vez después de configurar la base de datos
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalación - YuTube Clone</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8'>
        <h1 class='text-3xl font-bold text-gray-800 mb-6'>🚀 Instalación YuTube Clone</h1>";

$errors = [];
$success = [];

// Verificar si PHP está funcionando
$success[] = "✅ PHP funcionando correctamente (versión " . PHP_VERSION . ")";

// Crear directorios necesarios
$directories = [
    '../uploads',
    '../uploads/videos',
    '../uploads/thumbnails'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            $success[] = "✅ Directorio creado: $dir";
        } else {
            $errors[] = "❌ Error al crear directorio: $dir";
        }
    } else {
        $success[] = "✅ Directorio ya existe: $dir";
    }
    
    // Verificar permisos de escritura
    if (is_writable($dir)) {
        $success[] = "✅ Permisos de escritura OK en: $dir";
    } else {
        $errors[] = "❌ Sin permisos de escritura en: $dir";
    }
}

// Verificar conexión a la base de datos
try {
    include '../config/database.php';
    $success[] = "✅ Conexión a base de datos exitosa";
    
    // Verificar si las tablas existen
    $tables = ['users', 'videos', 'comments', 'likes'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $success[] = "✅ Tabla '$table' encontrada";
        } else {
            $errors[] = "❌ Tabla '$table' no encontrada - ¿Importaste el archivo SQL?";
        }
    }
} catch (Exception $e) {
    $errors[] = "❌ Error de conexión a BD: " . $e->getMessage();
    $errors[] = "💡 Asegúrate de que MySQL esté ejecutándose y que hayas creado la BD 'youtube_clone'";
}

// Verificar configuración de PHP
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');

$success[] = "✅ Límite de upload: $upload_max";
$success[] = "✅ Límite POST: $post_max";
$success[] = "✅ Memoria límite: $memory_limit";

// Mostrar resultados
echo "<div class='space-y-4'>";

if (!empty($success)) {
    echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4'>";
    echo "<h2 class='text-lg font-semibold text-green-800 mb-2'>Configuración Exitosa:</h2>";
    echo "<ul class='space-y-1'>";
    foreach ($success as $msg) {
        echo "<li class='text-green-700'>$msg</li>";
    }
    echo "</ul></div>";
}

if (!empty($errors)) {
    echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4'>";
    echo "<h2 class='text-lg font-semibold text-red-800 mb-2'>Errores Encontrados:</h2>";
    echo "<ul class='space-y-1'>";
    foreach ($errors as $msg) {
        echo "<li class='text-red-700'>$msg</li>";
    }
    echo "</ul></div>";
}

if (empty($errors)) {
    echo "<div class='bg-blue-50 border border-blue-200 rounded-lg p-4'>";
    echo "<h2 class='text-lg font-semibold text-blue-800 mb-2'>🎉 ¡Instalación Completada!</h2>";
    echo "<p class='text-blue-700 mb-4'>Tu plataforma YuTube Clone está lista para usar.</p>";
    echo "<div class='space-y-2'>";
    echo "<p class='text-sm text-blue-600'>📋 <strong>Próximos pasos:</strong></p>";
    echo "<ol class='list-decimal list-inside text-sm text-blue-700 space-y-1'>";
    echo "<li>Elimina este archivo (frontend/install.php) por seguridad</li>";
    echo "<li>Ve a <a href='../index.php' class='underline font-semibold'>la página principal</a></li>";
    echo "<li>Crea tu cuenta de usuario</li>";
    echo "<li>¡Comienza a subir videos!</li>";
    echo "</ol>";
    echo "</div>";
    echo "<div class='mt-4 pt-4 border-t border-blue-200'>";
    echo "<a href='../index.php' class='inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200'>";
    echo "🏠 Ir a YuTube Clone";
    echo "</a>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div class='bg-yellow-50 border border-yellow-200 rounded-lg p-4'>";
    echo "<h2 class='text-lg font-semibold text-yellow-800 mb-2'>⚠️ Acción Requerida</h2>";
    echo "<p class='text-yellow-700'>Por favor, corrige los errores antes de continuar.</p>";
    echo "</div>";
}

echo "</div>";

// Información adicional
echo "<div class='mt-8 pt-6 border-t border-gray-200'>";
echo "<h3 class='text-lg font-semibold text-gray-800 mb-3'>📖 Información del Sistema</h3>";
echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4 text-sm'>";
echo "<div class='bg-gray-50 p-3 rounded'>";
echo "<strong>Servidor Web:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "<strong>PHP:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Sistema:</strong> " . PHP_OS;
echo "</div>";
echo "<div class='bg-gray-50 p-3 rounded'>";
echo "<strong>Directorio:</strong> " . __DIR__ . "<br>";
echo "<strong>URL:</strong> " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "<br>";
echo "<strong>Zona Horaria:</strong> " . date_default_timezone_get();
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div></body></html>";
?> 