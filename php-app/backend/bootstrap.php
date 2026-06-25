<?php

declare(strict_types=1);

use ImobiHub\Database;
use ImobiHub\PropertyRepository;
use ImobiHub\ApiResponse;
use ImobiHub\AdminRepository;
use ImobiHub\Mailer;

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/PropertyRepository.php';
require_once __DIR__ . '/src/ApiResponse.php';
require_once __DIR__ . '/src/AdminRepository.php';
require_once __DIR__ . '/src/Mailer.php';

$config = require __DIR__ . '/config/config.php';

if (!is_dir($config['upload_dir'])) {
    mkdir($config['upload_dir'], 0777, true);
}

$db = new Database($config['db_path']);
$db->initializeSchema();

$repository = new PropertyRepository($db->pdo());
$repository->seedIfEmpty();

$adminRepository = new AdminRepository($db->pdo());
$mailer = new Mailer($config['mail']);
