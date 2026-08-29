CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admins` (`name`, `username`, `email`, `password`) 
VALUES ('System Administrator', 'admin', 'admin@portal.com', '$2y$10$wT8B1dF6S9z0wX8B1dF6S.y4J3q5V6W7X8Y9Z0A1B2C3D4E5F6G7H');