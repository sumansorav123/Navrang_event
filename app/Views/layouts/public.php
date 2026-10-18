<?php
$siteTitle = $siteName ?? 'Navrang Events';
$siteTagline = $siteTagline ?? 'Nine Colors. One Campus. One Celebration.';
$pageTitle = getPageTitle($page ?? 'home');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($siteTitle) ?> | <?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($siteTagline) ?>">
  <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/styles.css">
  <script defer src="<?= e(APP_URL) ?>/assets/js/app.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
  <?php include APP_PATH . '/Views/components/public-header.php'; ?>
  <?php include $pageFile; ?>
  <?php include APP_PATH . '/Views/components/public-footer.php'; ?>
</body>
</html>
