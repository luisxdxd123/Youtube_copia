<?php
function checkFFmpeg() {
    $ffmpeg = trim(shell_exec('where ffmpeg 2>&1'));
    $ffprobe = trim(shell_exec('where ffprobe 2>&1'));
    
    return [
        'ffmpeg_installed' => !empty($ffmpeg) && !str_contains($ffmpeg, 'not found'),
        'ffprobe_installed' => !empty($ffprobe) && !str_contains($ffprobe, 'not found'),
        'ffmpeg_path' => $ffmpeg,
        'ffprobe_path' => $ffprobe
    ];
}

$check_result = checkFFmpeg();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar FFmpeg - videoNetBandera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Verificación de FFmpeg</h1>

            <div class="space-y-4">
                <!-- FFmpeg Status -->
                <div class="p-4 rounded-lg <?= $check_result['ffmpeg_installed'] ? 'bg-green-100' : 'bg-red-100' ?>">
                    <h2 class="font-semibold flex items-center">
                        <i class="fas <?= $check_result['ffmpeg_installed'] ? 'fa-check text-green-500' : 'fa-times text-red-500' ?> mr-2"></i>
                        FFmpeg
                    </h2>
                    <p class="mt-2 text-sm">
                        Estado: <span class="font-semibold"><?= $check_result['ffmpeg_installed'] ? 'Instalado' : 'No instalado' ?></span>
                    </p>
                    <?php if ($check_result['ffmpeg_installed']): ?>
                        <p class="text-sm text-gray-600 mt-1">Ruta: <?= htmlspecialchars($check_result['ffmpeg_path']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- FFprobe Status -->
                <div class="p-4 rounded-lg <?= $check_result['ffprobe_installed'] ? 'bg-green-100' : 'bg-red-100' ?>">
                    <h2 class="font-semibold flex items-center">
                        <i class="fas <?= $check_result['ffprobe_installed'] ? 'fa-check text-green-500' : 'fa-times text-red-500' ?> mr-2"></i>
                        FFprobe
                    </h2>
                    <p class="mt-2 text-sm">
                        Estado: <span class="font-semibold"><?= $check_result['ffprobe_installed'] ? 'Instalado' : 'No instalado' ?></span>
                    </p>
                    <?php if ($check_result['ffprobe_installed']): ?>
                        <p class="text-sm text-gray-600 mt-1">Ruta: <?= htmlspecialchars($check_result['ffprobe_path']) ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!$check_result['ffmpeg_installed'] || !$check_result['ffprobe_installed']): ?>
                <div class="mt-6 p-4 bg-yellow-100 rounded-lg">
                    <h3 class="font-semibold text-yellow-800">Instrucciones de Instalación:</h3>
                    <ol class="mt-2 space-y-2 text-sm text-yellow-800">
                        <li>1. Descarga FFmpeg desde <a href="https://ffmpeg.org/download.html" class="text-blue-600 hover:underline" target="_blank">ffmpeg.org</a></li>
                        <li>2. Extrae el archivo descargado</li>
                        <li>3. Copia los archivos ffmpeg.exe y ffprobe.exe a una carpeta en tu sistema</li>
                        <li>4. Agrega la ruta de la carpeta a las variables de entorno del sistema</li>
                        <li>5. Reinicia el servidor web</li>
                    </ol>
                </div>
                <?php else: ?>
                <div class="mt-6 p-4 bg-green-100 rounded-lg">
                    <p class="text-green-800">
                        <i class="fas fa-check-circle mr-2"></i>
                        FFmpeg está correctamente instalado y configurado. La subida de videos con validación de duración debería funcionar correctamente.
                    </p>
                </div>
                <?php endif; ?>

                <div class="mt-6 flex space-x-4">
                    <a href="upload.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-upload mr-2"></i>
                        Ir a Subir Video
                    </a>
                    <a href="../index.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        <i class="fas fa-home mr-2"></i>
                        Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 