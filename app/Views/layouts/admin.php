<?php
$pageTitle = $viewFile ? 'Admin' : 'Navrang Events';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Navrang Events Admin</title>
  <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/styles.css">
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
  <?php include $viewFile; ?>
</body>
</html>
