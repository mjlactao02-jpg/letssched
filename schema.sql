-- LetsSched Schema - run via phpMyAdmin or mysql
CREATE DATABASE IF NOT EXISTS letssched CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE letssched;

DROP TABLE IF EXISTS availabilities;
DROP TABLE IF EXISTS participants;
DROP TABLE IF EXISTS events;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(8) NOT NULL UNIQUE,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  dates JSON NOT NULL,
  time_start TIME NOT NULL DEFAULT '09:00:00',
  time_end TIME NOT NULL DEFAULT '18:00:00',
  slot_minutes INT NOT NULL DEFAULT 30,
  mode VARCHAR(10) NOT NULL DEFAULT 'dates',
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_event_name (event_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE availabilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  participant_id INT NOT NULL,
  date DATE NOT NULL,
  slot_time TIME NOT NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_slot (event_id, participant_id, date, slot_time),
  INDEX idx_lookup (event_id, date, slot_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
