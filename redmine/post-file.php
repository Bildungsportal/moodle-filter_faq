<?php

$config = [
    'db' => [
        'host' => 'localhost',
        'dbname' => '',
        'user' => '',
        'pass' => '',
    ],
    'secret' => '',
    'attachments_table' => 'attachments',
    'verzeichnis_upload' => '',
];

if (file_exists(__DIR__ . '/config.php')) {
    $config = array_merge($config, require(__DIR__ . '/config.php'));
} else {
    $config = array_merge($config, require(__DIR__ . '/config.default.php'));
}

/*
CREATE TABLE redmine_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    container_id INT NOT NULL,
    container_type VARCHAR(50) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    disk_filename VARCHAR(255) NOT NULL,
    filesize INT,
    content_type VARCHAR(255),
    digest VARCHAR(40),
    downloads INT DEFAULT 0,
    author_id INT,
    created_on DATETIME,
    description TEXT,
    disk_directory VARCHAR(255),
    INDEX idx_container_id (container_id),
    INDEX idx_container_type (container_type)
);
*/

function handle_upload($config) {

    $attachments_table = $config['attachments_table'];

    $time_modifier = date("ymdHis");
    $time_insert = date("Y-m-d H:i:s");

    $seed = $_REQUEST['seed'] ?? null;
    if (!$seed) {
        throw new \Exception('seed ist leer');
    }

    $dir_info_year = date("Y");
    $dir_info_month = date("m");
    $dir_info = $dir_info_year . "/" . $dir_info_month;

    $verzeichnis_upload = rtrim($config['verzeichnis_upload'], '/') . '/' . $dir_info;

    if (!is_dir($verzeichnis_upload)) {
        mkdir($verzeichnis_upload, 0777, true);
    }

    if (!is_dir($verzeichnis_upload)) {
        throw new \Exception('Verzeichnis konnte nicht erstellt werden');
    }

    try {
        // Create PDO instance
        $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new \Exception("Database connection failed: " . $e->getMessage());
    }

    $result = (object)[
        'files_info' => [],
    ];

    for ($i = 0; $i < count($_FILES['files']["name"] ?? []); $i++) {

        $datei_error = $_FILES['files']["error"][$i];
        if ($datei_error == UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $datei_name = $_FILES['files']["name"][$i];
        $datei_name_target = $time_modifier . "_" . $datei_name;
        $datei_type = $_FILES['files']["type"][$i];
        $datei_tmp = $_FILES['files']["tmp_name"][$i];
        // $md5file = md5_file($datei_tmp);
        $datei_size = $_FILES['files']["size"][$i];
        // $datei_endung = strtolower(pathinfo($_FILES['files']["name"][$i], PATHINFO_EXTENSION));
        $datei_digest = md5_file($datei_tmp);

        // Prepare the insert statement
        $insert_query = "INSERT INTO {$attachments_table}
        (container_id, container_type, filename, disk_filename, filesize, content_type, digest, downloads, author_id, created_on, description, disk_directory)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

        try {
            // Prepare and execute the statement with parameterized values
            $stmt = $pdo->prepare($insert_query);
            $stmt->execute([
                9999,
                'Issue',
                $datei_name,
                $datei_name_target,
                $datei_size,
                $datei_type,
                $datei_digest,
                0,
                1,
                $time_insert,
                $seed,
                $dir_info,
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Database insert failed: " . $e->getMessage());
        }

        move_uploaded_file($datei_tmp, $verzeichnis_upload . "/" . $datei_name_target);
        if (!file_exists($verzeichnis_upload . "/" . $datei_name_target)) {
            throw new \Exception('Datei konnte nicht verschoben werden');
        }

        $result->files_info[] = "{$datei_name} ({$datei_type}) {$datei_size} bytes uploaded";
    }

    return $result;
}

try {
    $secret = $_REQUEST['secret'] ?? null;
    if ($secret != $config['secret']) {
        throw new \Exception('wrong secret');
    }

    $result = handle_upload($config);
    $result = [
        'type' => 'success',
        'result' => $result,
    ];
} catch (\Exception $e) {
    $result = [
        'type' => 'error',
        'error' => $e->getMessage(),
    ];
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
