-- Crear base de datos
CREATE DATABASE IF NOT EXISTS youtube_clone;
USE youtube_clone;

-- Tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT,
    website VARCHAR(255),
    location VARCHAR(100),
    subscribers_count INT DEFAULT 0,
    total_views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active'
);

-- Tabla de videos
CREATE TABLE videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_path VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    duration INT NOT NULL COMMENT 'Duración en segundos',
    views INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    dislikes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    status ENUM('active', 'private', 'unlisted', 'deleted') DEFAULT 'active',
    category VARCHAR(50),
    tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Tabla de comentarios
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL COMMENT 'Para respuestas a comentarios',
    content TEXT NOT NULL,
    likes_count INT DEFAULT 0,
    dislikes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'deleted', 'hidden') DEFAULT 'active',
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_video_id (video_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_id (parent_id)
);

-- Tabla de likes (videos y comentarios)
CREATE TABLE likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    video_id INT DEFAULT NULL,
    comment_id INT DEFAULT NULL,
    type ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_video_like (user_id, video_id),
    UNIQUE KEY unique_comment_like (user_id, comment_id),
    INDEX idx_video_id (video_id),
    INDEX idx_comment_id (comment_id)
);

-- Tabla de suscripciones
CREATE TABLE subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subscriber_id INT NOT NULL,
    channel_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscriber_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subscription (subscriber_id, channel_id),
    INDEX idx_subscriber_id (subscriber_id),
    INDEX idx_channel_id (channel_id)
);

-- Tabla de visualizaciones de videos
CREATE TABLE video_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    watch_time INT DEFAULT 0 COMMENT 'Tiempo visto en segundos',
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_video_id (video_id),
    INDEX idx_user_id (user_id),
    INDEX idx_viewed_at (viewed_at)
);

-- Tabla de listas de reproducción
CREATE TABLE playlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    privacy ENUM('public', 'private', 'unlisted') DEFAULT 'public',
    videos_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Tabla de videos en listas de reproducción
CREATE TABLE playlist_videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    playlist_id INT NOT NULL,
    video_id INT NOT NULL,
    position INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_playlist_video (playlist_id, video_id),
    INDEX idx_playlist_id (playlist_id),
    INDEX idx_video_id (video_id)
);

-- Tabla de historial de búsquedas
CREATE TABLE search_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    search_term VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    ip_address VARCHAR(45),
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_search_term (search_term)
);

-- Tabla de notificaciones
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('like', 'comment', 'subscription', 'video_upload') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    related_id INT DEFAULT NULL COMMENT 'ID relacionado (video_id, comment_id, etc.)',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
);

-- Insertar usuario administrador por defecto
INSERT INTO users (username, email, password, first_name, last_name, bio) VALUES 
('admin', 'admin@youtube.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'Administrador del sistema');

-- Triggers para actualizar contadores

-- Trigger para actualizar contador de comentarios en videos
DELIMITER //
CREATE TRIGGER update_video_comments_count AFTER INSERT ON comments
FOR EACH ROW
BEGIN
    UPDATE videos SET comments_count = (
        SELECT COUNT(*) FROM comments WHERE video_id = NEW.video_id AND status = 'active'
    ) WHERE id = NEW.video_id;
END//

CREATE TRIGGER update_video_comments_count_delete AFTER DELETE ON comments
FOR EACH ROW
BEGIN
    UPDATE videos SET comments_count = (
        SELECT COUNT(*) FROM comments WHERE video_id = OLD.video_id AND status = 'active'
    ) WHERE id = OLD.video_id;
END//

-- Trigger para actualizar contador de suscriptores
CREATE TRIGGER update_subscribers_count AFTER INSERT ON subscriptions
FOR EACH ROW
BEGIN
    UPDATE users SET subscribers_count = (
        SELECT COUNT(*) FROM subscriptions WHERE channel_id = NEW.channel_id
    ) WHERE id = NEW.channel_id;
END//

CREATE TRIGGER update_subscribers_count_delete AFTER DELETE ON subscriptions
FOR EACH ROW
BEGIN
    UPDATE users SET subscribers_count = (
        SELECT COUNT(*) FROM subscriptions WHERE channel_id = OLD.channel_id
    ) WHERE id = OLD.channel_id;
END//

-- Trigger para actualizar contadores de likes en videos
CREATE TRIGGER update_video_likes_count AFTER INSERT ON likes
FOR EACH ROW
BEGIN
    IF NEW.video_id IS NOT NULL THEN
        UPDATE videos SET 
            likes_count = (SELECT COUNT(*) FROM likes WHERE video_id = NEW.video_id AND type = 'like'),
            dislikes_count = (SELECT COUNT(*) FROM likes WHERE video_id = NEW.video_id AND type = 'dislike')
        WHERE id = NEW.video_id;
    END IF;
END//

CREATE TRIGGER update_video_likes_count_delete AFTER DELETE ON likes
FOR EACH ROW
BEGIN
    IF OLD.video_id IS NOT NULL THEN
        UPDATE videos SET 
            likes_count = (SELECT COUNT(*) FROM likes WHERE video_id = OLD.video_id AND type = 'like'),
            dislikes_count = (SELECT COUNT(*) FROM likes WHERE video_id = OLD.video_id AND type = 'dislike')
        WHERE id = OLD.video_id;
    END IF;
END//

DELIMITER ; 