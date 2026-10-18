<?php

declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    if ($value === null) {
        $envFile = ROOT_PATH . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }
                [$envKey, $envValue] = array_pad(explode('=', $line, 2), 2, '');
                if (trim($envKey) === $key) {
                    $value = trim($envValue, " \n\r\t\v\0\"");
                    break;
                }
            }
        }
    }

    if ($value === null || $value === '') {
        return $default;
    }

    return $value;
}

function formatCurrency(int|float|string $amount): string
{
    $value = (float) $amount;
    return '₹' . number_format($value, 2, '.', ',');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function generateContributionReference(): string
{
    $prefix = 'NAVRANG';
    $suffix = strtoupper(bin2hex(random_bytes(5)));
    return $prefix . '-' . date('Ymd') . '-' . $suffix;
}

function verifyCsrfToken(mixed $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function redirect(string $path): void
{
    header('Location: ' . $path, true, 302);
    exit;
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_role']);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-');
}

function ensureDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Unable to create directory: ' . $path);
    }
}

function resolveUploadPath(string $filename, bool $private = false): string
{
    $base = $private ? PRIVATE_UPLOAD_PATH : UPLOAD_PATH;
    ensureDirectory($base);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $base . '/' . uniqid('file_', true) . ($ext !== '' ? '.' . $ext : '');
}

function saveUploadedImage(array $file, bool $private = true): ?string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowedMime[$mime])) {
        return null;
    }

    $sizeLimit = 2 * 1024 * 1024;
    $actualSize = filesize($file['tmp_name']);
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($actualSize === false || $actualSize <= 0 || $actualSize > $sizeLimit || $imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
        return null;
    }

    $baseDirectory = $private ? PRIVATE_UPLOAD_PATH : PUBLIC_PATH . '/uploads';
    ensureDirectory($baseDirectory);
    $safePath = $baseDirectory . '/' . bin2hex(random_bytes(16)) . '.' . $allowedMime[$mime];
    if (!move_uploaded_file($file['tmp_name'], $safePath)) {
        return null;
    }

    return str_replace(DIRECTORY_SEPARATOR, '/', substr($safePath, strlen(ROOT_PATH)));
}

function publicFileUrl(?string $storedPath, string $fallback = ''): string
{
    if ($storedPath === null || trim((string) $storedPath) === '') {
        return $fallback;
    }

    $path = str_replace('\\', '/', trim((string) $storedPath));
    if (preg_match('/^https?:\/\//i', $path) === 1) {
        return $path;
    }

    $clean = ltrim($path, '/');
    if ($clean === '') {
        return $fallback;
    }

    if (str_starts_with($clean, 'public/') || str_starts_with($clean, 'storage/')) {
        return APP_URL . '?page=file&path=' . urlencode('/' . $clean);
    }

    return $path;
}

function availableEventImageAssets(): array
{
    $imageDirectory = realpath(PUBLIC_PATH . '/assets/images');
    if ($imageDirectory === false || !is_dir($imageDirectory)) {
        return [];
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $assets = [];
    foreach (glob($imageDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        $realFile = realpath($file);
        if ($realFile === false || !is_file($realFile) || dirname($realFile) !== $imageDirectory) {
            continue;
        }

        $mime = $finfo->file($realFile);
        $imageInfo = @getimagesize($realFile);
        if (in_array($mime, $allowedMime, true) && $imageInfo !== false && ($imageInfo['mime'] ?? '') === $mime) {
            $assets[] = '/assets/images/' . basename($realFile);
        }
    }

    sort($assets, SORT_NATURAL | SORT_FLAG_CASE);
    return $assets;
}

function resolvePublicEventImageUrl(?string $storedPath): ?string
{
    $path = trim((string) $storedPath);
    if ($path === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $path) === 1) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);
    $relativePath = ltrim($path, '/');
    if (str_starts_with($relativePath, 'public/')) {
        $relativePath = substr($relativePath, strlen('public/'));
    }

    if ($relativePath === '' || str_contains($relativePath, "\0") || in_array('..', explode('/', $relativePath), true)) {
        return null;
    }

    $publicRoot = realpath(PUBLIC_PATH);
    $realFile = realpath(PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if ($publicRoot === false || $realFile === false || !is_file($realFile)) {
        return null;
    }

    $rootPrefix = rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with(strtolower($realFile), strtolower($rootPrefix))) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($realFile);
    $imageInfo = @getimagesize($realFile);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
        return null;
    }

    $urlPath = implode('/', array_map('rawurlencode', explode('/', $relativePath)));
    return rtrim(APP_URL, '/') . '/' . $urlPath;
}

function eventImageFallbackUrl(): string
{
    return resolvePublicEventImageUrl('/assets/images/Navrang 1.0 Dandiya Night Poster.png') ?? '';
}

function eventImageUrl(?string $storedPath): string
{
    return resolvePublicEventImageUrl($storedPath) ?? eventImageFallbackUrl();
}

function startAudit(string $action, string $note = ''): void
{
    if (!isAdminLoggedIn()) {
        return;
    }

    $pdo = requireDatabase();
    if (!$pdo) {
        return;
    }

    $statement = $pdo->prepare('INSERT INTO admin_audit_logs (admin_id, action, note, created_at) VALUES (:admin_id, :action, :note, NOW())');
    $statement->execute([
        ':admin_id' => $_SESSION['admin_id'],
        ':action' => $action,
        ':note' => $note,
    ]);
}

function ensureInitialAdmin(): void
{
    $pdo = requireDatabase();
    if (!$pdo) {
        return;
    }

    try {
        $count = $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
        if ((int) $count > 0) {
            return;
        }
    } catch (Throwable) {
        return;
    }

    $email = trim((string) env('ADMIN_EMAIL', 'admin@navrang.local'));
    $password = (string) env('ADMIN_PASSWORD', '');
    if ($email === '' || $password === '') {
        return;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        return;
    }

    $statement = $pdo->prepare('INSERT INTO admins (email, username, full_name, password_hash, role, is_active) VALUES (:email, :username, :full_name, :password_hash, :role, 1)');
    $statement->execute([
        ':email' => $email,
        ':username' => 'admin',
        ':full_name' => 'Administrator',
        ':password_hash' => $passwordHash,
        ':role' => 'super_admin',
    ]);
}

function ensureDatabaseSchema(): void
{
    $pdo = requireDatabase();
    if (!$pdo) {
        return;
    }

    try {
        $paymentReferenceColumn = $pdo->query("SHOW COLUMNS FROM contributions LIKE 'payment_reference'")->fetch();
        if (!$paymentReferenceColumn) {
            $pdo->exec('ALTER TABLE contributions ADD COLUMN payment_reference VARCHAR(191) DEFAULT NULL AFTER transaction_ref');
        }
    } catch (Throwable) {
        // Ignore schema migration failures for older DB installs.
    }

    try {
        $columns = $pdo->query("SHOW COLUMNS FROM contributions LIKE 'status'")->fetch();
    } catch (Throwable) {
        return;
    }

    if (!$columns) {
        return;
    }

    $type = strtoupper((string) $columns['Type']);
    $wanted = 'ENUM(\'PENDING_PAYMENT\',\'PENDING_VERIFICATION\',\'PENDING\',\'APPROVED\',\'VERIFIED\',\'REJECTED\',\'REFUNDED\',\'DUPLICATE\')';
    if (str_contains($type, 'PENDING_PAYMENT') || str_contains($type, 'PENDING_VERIFICATION')) {
        return;
    }

    try {
        $pdo->exec("ALTER TABLE contributions MODIFY status ENUM('pending_payment','pending_verification','pending','approved','verified','rejected','refunded','duplicate') NOT NULL DEFAULT 'pending_payment'");
    } catch (Throwable) {
        // Ignore schema migration failures for older DB installs.
    }
}

function requireDatabase(): ?PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $config = require CONFIG_PATH . '/database.php';
    $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'] . ';charset=' . $config['charset'];
    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (Throwable $e) {
        return null;
    }
}

function getPageTitle(string $page): string
{
    $titles = [
        'home' => 'Home',
        'event' => 'Event Details',
        'contribute' => 'Contribute',
        'archive' => 'Previous Events',
        'gallery' => 'Event Gallery',
        'about' => 'About Us',
        'contact' => 'Contact',
        'privacy' => 'Privacy Policy',
        'terms' => 'Contribution Terms',
        'login' => 'Admin Login',
        'dashboard' => 'Admin Dashboard',
    ];

    return $titles[$page] ?? 'Navrang Events';
}
