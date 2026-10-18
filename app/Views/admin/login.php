<?php
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $pdo = requireDatabase();

    if ($pdo && $email !== '' && $password !== '') {
        $statement = $pdo->prepare('SELECT * FROM admins WHERE email = :email AND is_active = 1 LIMIT 1');
        $statement->execute([':email' => $email]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_logged_in'] = true;
            redirect(APP_URL . '?page=dashboard');
        }
    }

    $message = 'Invalid email or password.';
}
?>
<main class="flex min-h-screen items-center justify-center bg-slate-950 px-4">
  <div class="w-full max-w-md rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-glow backdrop-blur-md">
    <div class="mb-8 text-center">
      <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-gold to-magenta text-2xl font-black text-slate-950">N</div>
      <h1 class="text-3xl font-black text-white">Admin Login</h1>
      <p class="mt-2 text-sm text-slate-300">Access the Navrang dashboard securely.</p>
    </div>

    <?php if ($message !== ''): ?>
      <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <input type="hidden" name="login_submit" value="1">
      <label class="block text-sm text-slate-300">
        Email
        <input type="email" name="email" required class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
      </label>
      <label class="block text-sm text-slate-300">
        Password
        <input type="password" name="password" required class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
      </label>
      <button type="submit" class="primary-button w-full">Sign in</button>
    </form>
  </div>
</main>
