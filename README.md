# YuTube Clone - Plataforma de Videos

Un clon completo de YouTube desarrollado en PHP con todas las funcionalidades principales: subir videos, comentarios, likes, sistema de usuarios y más.

## 🚀 Características

- ✅ **Sistema de usuarios**: Registro, login, autenticación
- ✅ **Subida de videos**: Upload con validación y almacenamiento
- ✅ **Reproductor de videos**: Visualización con controles nativos
- ✅ **Sistema de likes**: Like/Dislike en tiempo real
- ✅ **Comentarios**: Sistema completo de comentarios
- ✅ **Gestión de videos**: Eliminar videos propios
- ✅ **Diseño responsive**: Interfaz moderna con Tailwind CSS
- ✅ **Base de datos MySQL**: Estructura completa con relaciones

## 🛠️ Requisitos

- **XAMPP** (Apache + MySQL + PHP)
- **PHP 7.4+**
- **MySQL 5.7+**
- Navegador web moderno

## 📦 Instalación

### 1. Configurar XAMPP

1. Descarga e instala [XAMPP](https://www.apachefriends.org/)
2. Inicia Apache y MySQL desde el panel de control de XAMPP
3. Copia este proyecto en la carpeta `htdocs` de XAMPP

### 2. Configurar Base de Datos

1. Abre **phpMyAdmin** en tu navegador: `http://localhost/phpmyadmin`
2. Crea una nueva base de datos llamada `youtube_clone`
3. Selecciona la base de datos e importa el archivo: `database/youtube_clone.sql`

### 3. Configurar Conexión

El archivo `config/database.php` está configurado por defecto para XAMPP:
```php
$host = 'localhost';
$dbname = 'youtube_clone';
$username = 'root';
$password = '';
```

### 4. Permisos de Carpetas

Asegúrate de que las siguientes carpetas tengan permisos de escritura:
```
uploads/
uploads/videos/
uploads/thumbnails/
```

## 🎯 Uso

### Acceder a la Aplicación

1. Abre tu navegador y ve a: `http://localhost/yutu/`
2. Verás la página principal con el listado de videos

### Crear Cuenta

1. Haz clic en **"Registrarse"**
2. Completa el formulario con tus datos
3. Automáticamente serás logueado

### Subir Video

1. Inicia sesión en tu cuenta
2. Haz clic en **"Subir"** en el header
3. Selecciona tu archivo de video (sin límite de tamaño)
4. Añade título, descripción y categoría
5. Haz clic en **"Subir Video"**

### Ver Videos

1. Haz clic en cualquier video de la página principal
2. El video se reproducirá automáticamente
3. Puedes dar like/dislike y comentar (si estás logueado)

### Gestionar tus Videos

1. Ve a "Mis Videos" desde el menú de usuario
2. Verás todos tus videos subidos
3. Puedes ver estadísticas y eliminar videos

## 🏗️ Estructura del Proyecto

```
yutu/
├── config/
│   └── database.php          # Configuración de BD
├── api/
│   ├── toggle-like.php       # API para likes
│   ├── add-comment.php       # API para comentarios
│   └── delete-video.php      # API para eliminar videos
├── database/
│   └── youtube_clone.sql     # Estructura de BD
├── uploads/
│   ├── videos/               # Videos subidos
│   └── thumbnails/           # Miniaturas
├── frontend/
│   ├── login.php             # Iniciar sesión
│   ├── register.php          # Registro de usuarios
│   ├── upload.php            # Subir videos
│   ├── watch.php             # Ver videos
│   ├── my-videos.php         # Gestionar videos
│   ├── logout.php            # Cerrar sesión
│   └── install.php           # Script de instalación
├── index.php                 # Página principal (raíz)
└── README.md                 # Este archivo
```

## 🎨 Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Tailwind CSS
- **Iconos**: Font Awesome
- **Servidor**: Apache (XAMPP)

## ⚙️ Funcionalidades Técnicas

### Base de Datos
- **usuarios**: Gestión completa de usuarios
- **videos**: Almacenamiento de metadata de videos
- **comentarios**: Sistema de comentarios anidados
- **likes**: Sistema de valoraciones
- **suscripciones**: Sistema de seguimiento (preparado)

### APIs REST
- `POST /api/toggle-like.php` - Gestionar likes/dislikes
- `POST /api/add-comment.php` - Añadir comentarios
- `POST /api/delete-video.php` - Eliminar videos

### Seguridad
- Validación de datos de entrada
- Protección contra SQL injection (PDO)
- Verificación de permisos para acciones críticas
- Sanitización de salida HTML

## 🐛 Solución de Problemas

### Error de conexión a BD
- Verifica que MySQL esté ejecutándose en XAMPP
- Confirma que la base de datos `youtube_clone` existe
- Revisa las credenciales en `config/database.php`

### Videos no se suben
- Verifica permisos de la carpeta `uploads/`
- Revisa el tamaño máximo de upload en PHP (`upload_max_filesize`)
- Confirma que el directorio existe

### Estilos no cargan
- Verifica conexión a internet (Tailwind CSS se carga desde CDN)
- Revisa la consola del navegador para errores

## 🔄 Próximas Mejoras

- [ ] Sistema de suscripciones completo
- [ ] Búsqueda avanzada de videos
- [ ] Categorías y tags mejorados
- [ ] Sistema de notificaciones
- [ ] Panel de administración
- [ ] Optimización de videos (FFmpeg)
- [ ] Sistema de playlists

## 📝 Notas Importantes

- **Tamaño de videos**: Sin límite de tamaño
- **Formatos soportados**: MP4, AVI, MOV, WMV
- **Navegadores**: Chrome, Firefox, Safari, Edge
- **Resolución**: Responsive design para móviles y desktop

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto es de código abierto y está disponible bajo la [MIT License](LICENSE).

---

**¡Disfruta creando tu propia plataforma de videos! 🎬** 