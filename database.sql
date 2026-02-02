CREATE DATABASE charity_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE charity_system;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  category VARCHAR(80) NOT NULL,
  location VARCHAR(140) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  goal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  banner VARCHAR(255) NULL,
  description TEXT NOT NULL,
  status ENUM('ongoing','completed','planned') NOT NULL DEFAULT 'planned',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;



CREATE TABLE donations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  message VARCHAR(255) NULL,
  donated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_don_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_don_event
    FOREIGN KEY (event_id) REFERENCES events(id)
    ON DELETE CASCADE,

  INDEX idx_user (user_id),
  INDEX idx_event (event_id)
) ENGINE=InnoDB;


-- Admin login:
-- Email: admin@charity.com
-- Password: admin123

-- Demo user login:
-- Email: user@charity.com
-- Password: user123

INSERT INTO users (name,email,password_hash,role) VALUES
(
 'Charity Admin',
 'admin@charity.com',
 '$2y$10$enCnQP3zoGtT0hJb0GubleKsDN6awrc0qZFL5rRzs5j6ryhwD5IoO',
 'admin'
),
(
 'Demo User',
 'user@charity.com',
 '$2y$10$7Ex1ZyNtLbG0Yw8zJmqx5.Zisdu4YKdyu4oE3IMxXAIYHc4eozN0u',
 'user'
);

-- Demo Events

INSERT INTO events
(title,category,location,start_date,end_date,goal_amount,banner,description,status)
VALUES

(
 'School Supplies for Children',
 'Education',
 'Kathmandu, Nepal',
 '2026-01-05',
 '2026-03-01',
 5000.00,
 'assests/img/banner_default.svg',
 'Providing notebooks, uniforms, and learning materials for children in public schools.
Funds support supplies, transport, and distribution.',
 'ongoing'
),

(
 'Emergency Food Relief',
 'Food',
 'Lalitpur, Nepal',
 '2026-01-15',
 '2026-02-28',
 3500.00,
 'assests/img/banner_default.svg',
 'Supporting families with essential groceries and community meals.
Budget includes food packages and local distribution.',
 'ongoing'
),

(
 'Community Health Camp',
 'Health',
 'Bhaktapur, Nepal',
 '2026-02-10',
 '2026-04-10',
 8000.00,
 'assests/img/banner_default.svg',
 'Free basic health checks and medicine support for low-income communities.
Budget covers medical supplies and volunteer support.',
 'planned'
);

-- Demo Donations

INSERT INTO donations (user_id,event_id,amount,message)
VALUES
(2,1,25.00,'Hope this helps!');