<?php
// config.php - DB connection for LetsSched (XAMPP defaults)
$DB_HOST = "sql202.infinityfree.com";
$DB_USER = "if0_42969322";
$DB_PASS = "cyee66gV0j8Ufsj";
$DB_NAME = "if0_42969322_letssched";

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // Auto-create DB if not exists (first run convenience)
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        $tmp = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS);
        $tmp->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo = new PDO(
            "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        // create tables
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        // schema.sql contains CREATE DATABASE + USE, strip to just tables for PDO exec
        // Run statements one by one
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if (stripos($stmt, 'CREATE DATABASE') === 0 || stripos($stmt, 'USE ') === 0 || stripos($stmt, 'DROP TABLE') === 0) {
                // handle drops/creates safely - exec table statements only when they are CREATE TABLE
                if (stripos($stmt, 'CREATE TABLE') === 0 || stripos($stmt, 'DROP TABLE') === 0) {
                    $pdo->exec($stmt);
                }
                continue;
            }
            if ($stmt !== '') $pdo->exec($stmt);
        }
    } else {
        http_response_code(500);
        die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
    }
}

function ensureTables(PDO $pdo) {
    // Ensure tables exist (for fresh DB)
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // migration for existing DBs
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM events LIKE 'mode'")->fetch();
        if (!$cols) $pdo->exec("ALTER TABLE events ADD COLUMN mode VARCHAR(10) NOT NULL DEFAULT 'dates'");
        $cols2 = $pdo->query("SHOW COLUMNS FROM events LIKE 'meta'")->fetch();
        if (!$cols2) $pdo->exec("ALTER TABLE events ADD COLUMN meta JSON NULL");
    } catch (Exception $e) {}
    $pdo->exec("CREATE TABLE IF NOT EXISTS participants (
      id INT AUTO_INCREMENT PRIMARY KEY,
      event_id INT NOT NULL,
      name VARCHAR(80) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
      UNIQUE KEY uniq_event_name (event_id, name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS availabilities (
      id INT AUTO_INCREMENT PRIMARY KEY,
      event_id INT NOT NULL,
      participant_id INT NOT NULL,
      date DATE NOT NULL,
      slot_time TIME NOT NULL,
      FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
      FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
      UNIQUE KEY uniq_slot (event_id, participant_id, date, slot_time),
      INDEX idx_lookup (event_id, date, slot_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
ensureTables($pdo);
