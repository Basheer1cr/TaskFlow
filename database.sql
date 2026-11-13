-- file: database.sql
CREATE DATABASE IF NOT EXISTS taskflow
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE taskflow;

DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;

-- Users table (Level 3 Authentication & Role-Based Authorization)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'readonly') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks table (Level 1, 2 & 3 Core Schema)
CREATE TABLE tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    status ENUM('Pending', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_tasks_user_status (user_id, status),
    INDEX idx_tasks_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-created Sample Users (Password for both accounts: password)
INSERT INTO users (id, name, email, password_hash, role) VALUES
(1, 'Basheer Shaik', 'shaikbasheeruddin12@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(2, 'Bob Reviewer', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Sample Tasks for User 1 and User 2
INSERT INTO tasks (user_id, title, description, status) VALUES
(1, 'Create MySQL database and tasks table', 'Design schema with id, title, description, status, and created_at.', 'Completed'),
(1, 'Implement search and status filter', 'Filter tasks by Pending, In Progress, or Completed and search by title.', 'In Progress'),
(1, 'Verify Level 3 security controls', 'Test PDO prepared statements, CSRF tokens, XSS escaping, and ownership.', 'Pending'),
(2, 'Bob private task', 'User 1 must not be able to view or modify this task.', 'Pending');