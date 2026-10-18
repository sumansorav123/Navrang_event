<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../app/Models/Event.php';
require_once __DIR__ . '/../app/Models/Contribution.php';
require_once __DIR__ . '/../app/Models/Alumni.php';
require_once __DIR__ . '/../app/Models/Settings.php';

$_SESSION = [];
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'super_admin';
$_SESSION['admin_name'] = 'Admin User';

if (!isAdminLoggedIn()) {
    fwrite(STDERR, "Admin session validation failed\n");
    exit(1);
}

$site = SettingsModel::getAll();
if (!isset($site['site_name'])) {
    fwrite(STDERR, "Site settings unavailable\n");
    exit(1);
}

$event = EventModel::getFeatured();
if (!$event) {
    fwrite(STDERR, "Featured event unavailable\n");
    exit(1);
}

echo "Basic validation passed\n";
