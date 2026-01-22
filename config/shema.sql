-- Пользователи
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'banned', 'pending') DEFAULT 'pending',
    verification_token VARCHAR(100) NULL,
    reset_token VARCHAR(100) NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Сессии пользователей (для лаунчера)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(100) UNIQUE NOT NULL,
    launcher_token VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_launcher_token (launcher_token),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Донаты
CREATE TABLE IF NOT EXISTS donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    tier VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100) UNIQUE,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_transaction_id (transaction_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Игровые серверы
CREATE TABLE IF NOT EXISTS servers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(100) NOT NULL,
    port INT DEFAULT 25565,
    status ENUM('online', 'offline', 'maintenance') DEFAULT 'offline',
    player_count INT DEFAULT 0,
    max_players INT DEFAULT 100,
    version VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Новости
CREATE TABLE IF NOT EXISTS news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    image_url VARCHAR(500) NULL,
    views INT DEFAULT 0,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_published BOOLEAN DEFAULT FALSE,
    INDEX idx_author_id (author_id),
    INDEX idx_published_at (published_at),
    INDEX idx_is_published (is_published),
    FULLTEXT idx_search (title, content),
    FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Тестовые данные (опционально)
INSERT INTO servers (name, description, ip_address, status, player_count, max_players, version) VALUES
('Основной мир', 'Основной сервер для приключений', 'play.deepworld.site', 'online', 42, 100, '1.20.1'),
('Креативный', 'Сервер для строительства и творчества', 'creative.deepworld.site', 'online', 18, 50, '1.20.1'),
('Мини-игры', 'Различные мини-игры и развлечения', 'games.deepworld.site', 'maintenance', 0, 50, '1.20.1');

-- Тестовый администратор (email: admin@deepworld.site, password: admin123)
INSERT INTO users (email, username, password_hash, status) VALUES
('admin@deepworld.site', 'Admin', '$2y$10$YourHashedPasswordHere', 'active');