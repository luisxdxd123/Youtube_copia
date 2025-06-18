# 🚀 Instalación Rápida - YuTube Clone

## Pasos Simples para Instalar

### 1. ⬇️ Preparar XAMPP
- Descarga XAMPP desde: https://www.apachefriends.org/
- Instala XAMPP en tu computadora
- Abre el **Panel de Control de XAMPP**
- Inicia **Apache** y **MySQL**

### 2. 📁 Copiar Archivos
- Copia toda la carpeta del proyecto en: `C:\xampp\htdocs\yutu\`
- (O donde tengas instalado XAMPP)

### 3. 🗄️ Configurar Base de Datos
- Abre tu navegador y ve a: `http://localhost/phpmyadmin`
- Haz clic en **"Nueva"** para crear una base de datos
- Nombra la base de datos: **`youtube_clone`**
- Selecciona la base de datos creada
- Haz clic en **"Importar"**
- Selecciona el archivo: `database/youtube_clone.sql`
- Haz clic en **"Continuar"**

### 4. ✅ Ejecutar Instalación
- Ve a: `http://localhost/yutu/frontend/install.php`
- Sigue las instrucciones en pantalla
- Si todo está en verde, ¡listo!

### 5. 🎬 Configurar para Videos Grandes (2GB)
- Ve a: `http://localhost/yutu/frontend/fix-xampp-limits.php`
- Sigue las instrucciones para configurar XAMPP
- Reinicia Apache en el panel de XAMPP
- ¡Ya puedes subir videos de hasta 2GB!

### 6. 🎉 ¡Ya Funciona!
- Ve a: `http://localhost/yutu/`
- Crea tu cuenta de usuario
- ¡Comienza a subir videos!

---

## 🔧 Funcionalidades Incluidas

✅ **Registro y Login de Usuarios**
✅ **Subir Videos** (sin límite de tamaño)
✅ **Ver Videos** con reproductor
✅ **Sistema de Likes** (Me gusta/No me gusta)
✅ **Comentarios** en videos
✅ **Eliminar Videos** propios
✅ **Gestión de Videos** personales
✅ **Diseño Responsive** para móvil y escritorio

## 📱 URLs Importantes

- **Página Principal**: `http://localhost/yutu/`
- **Iniciar Sesión**: `http://localhost/yutu/frontend/login.php`
- **Registrarse**: `http://localhost/yutu/frontend/register.php`
- **Subir Video**: `http://localhost/yutu/frontend/upload.php`
- **Mis Videos**: `http://localhost/yutu/frontend/my-videos.php`
- **Configurar XAMPP**: `http://localhost/yutu/frontend/fix-xampp-limits.php`

## 🆘 ¿Problemas?

### ❌ No carga la página
- Verifica que Apache esté iniciado en XAMPP
- Revisa que la URL sea correcta

### ❌ Error de base de datos
- Verifica que MySQL esté iniciado en XAMPP
- Confirma que importaste el archivo SQL
- Revisa que la base de datos se llame exactamente `youtube_clone`

### ❌ No se suben videos
- Ejecuta `frontend/install.php` para crear las carpetas necesarias
- Verifica que las carpetas `uploads/` tengan permisos

---

**¡Listo! Ya tienes tu propio YouTube funcionando! 🎬** 