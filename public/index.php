<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once APP_PATH . '/Models/Event.php';
require_once APP_PATH . '/Models/Contribution.php';
require_once APP_PATH . '/Models/Alumni.php';
require_once APP_PATH . '/Models/Settings.php';

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset();
    session_destroy();
    redirect(APP_URL . '?page=admin');
}

$requestedPage = $_GET['page'] ?? null;
if ($requestedPage === null) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $basePath = '/navrang/public';
    if (str_contains($path, $basePath)) {
        $path = substr($path, strlen($basePath));
    }
    $requestedPage = trim($path, '/');
}

$page = strtolower($requestedPage ?: 'home');
$publicPages = ['home', 'event', 'contribute', 'payment', 'archive', 'gallery', 'about', 'contact', 'privacy', 'terms', 'file'];

if ($page === 'admin' || $page === 'admin-login' || $page === 'login') {
    if (isAdminLoggedIn()) {
        redirect(APP_URL . '?page=dashboard');
    }
    $viewFile = APP_PATH . '/Views/admin/login.php';
    include APP_PATH . '/Views/layouts/admin.php';
    exit;
}

if ($page === 'dashboard') {
    if (!isAdminLoggedIn()) {
        redirect(APP_URL . '?page=admin');
    }
    $viewFile = APP_PATH . '/Views/admin/dashboard.php';
    include APP_PATH . '/Views/layouts/admin.php';
    exit;
}

if ($page === 'file') {
    $requestedPath = trim((string) ($_GET['path'] ?? ''));
    $normalizedPath = ltrim($requestedPath, '/');
    if ($normalizedPath === '') {
        http_response_code(404);
        exit;
    }

    $candidate = ROOT_PATH . '/' . str_replace('\\', '/', $normalizedPath);
    $realFile = realpath($candidate);
    $allowedRoots = [
        realpath(ROOT_PATH . '/public'),
        realpath(ROOT_PATH . '/storage'),
    ];

    $allowed = false;
    foreach ($allowedRoots as $root) {
        if ($root !== false && $realFile !== false && str_starts_with($realFile, $root . DIRECTORY_SEPARATOR)) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed || $realFile === false || !is_file($realFile)) {
        http_response_code(404);
        exit;
    }

    $privateUploadRoot = realpath(PRIVATE_UPLOAD_PATH);
    $isPrivateUpload = $privateUploadRoot !== false && str_starts_with($realFile, $privateUploadRoot . DIRECTORY_SEPARATOR);
    if ($isPrivateUpload) {
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($realFile, strlen(ROOT_PATH) + 1));
        $pdo = requireDatabase();
        $isPublicImage = isAdminLoggedIn();
        if (!$isPublicImage && $pdo) {
            $storedPath = '/' . ltrim($relativePath, '/');
            $contribution = $pdo->prepare('SELECT 1 FROM contributions c INNER JOIN events e ON e.id = c.event_id WHERE c.photo_url IN (:path, :path_without_slash) AND c.status = "verified" AND c.is_public = 1 AND c.consent_status = "explicit" AND c.display_photo = 1 AND e.status IN ("published", "archived") LIMIT 1');
            $contribution->execute([
                ':path' => $storedPath,
                ':path_without_slash' => ltrim($storedPath, '/'),
            ]);
            $isPublicImage = (bool) $contribution->fetchColumn();

            if (!$isPublicImage) {
                $alumni = $pdo->prepare('SELECT 1 FROM alumni WHERE photo_url IN (:path, :path_without_slash) AND is_visible = 1 LIMIT 1');
                $alumni->execute([
                    ':path' => $storedPath,
                    ':path_without_slash' => ltrim($storedPath, '/'),
                ]);
                $isPublicImage = (bool) $alumni->fetchColumn();
            }
        }

        if (!$isPublicImage) {
            http_response_code(404);
            exit;
        }
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($realFile);
    if (!in_array($mime, $allowedMime, true)) {
        http_response_code(403);
        exit;
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($realFile));
    header('Cache-Control: ' . ($isPrivateUpload && isAdminLoggedIn() ? 'private, no-store' : 'public, max-age=3600'));
    readfile($realFile);
    exit;
}

if (!in_array($page, $publicPages, true)) {
    $page = 'home';
}

$settings = SettingsModel::getAll();
$featuredEvent = EventModel::getFeatured();
if (!$featuredEvent) {
    $featuredEvent = EventModel::getActive()[0] ?? null;
}

$siteName = $settings['site_name'] ?? 'Navrang Events';
$siteTagline = $settings['tagline'] ?? 'Nine Colors. One Campus. One Celebration.';
$pageFile = APP_PATH . '/Views/pages/' . $page . '.php';
if (!file_exists($pageFile)) {
    $pageFile = APP_PATH . '/Views/pages/404.php';
}

include APP_PATH . '/Views/layouts/public.php';
