<?php
define('SITE_BASE_URL', APP_URL);
$navItems = [
    ['label' => 'Home', 'page' => 'home'],
    ['label' => 'Event Details', 'page' => 'event'],
    ['label' => 'Contribute', 'page' => 'contribute'],
    ['label' => 'Archive', 'page' => 'archive'],
    ['label' => 'Gallery', 'page' => 'gallery'],
    ['label' => 'About', 'page' => 'about'],
    ['label' => 'Contact', 'page' => 'contact'],
];
?>
<header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/80 backdrop-blur-xl">
  <nav class="page-shell flex items-center justify-between py-4">
    <a href="<?= e(APP_URL) ?>?page=home" class="flex items-center gap-3">
      <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-gold to-magenta text-lg font-black text-slate-950">N</div>
      <div>
        <div class="text-sm uppercase tracking-[0.3em] text-gold">Navrang</div>
        <div class="text-xs text-slate-300">Events</div>
      </div>
    </a>

    <div class="hidden items-center gap-6 md:flex">
      <?php foreach ($navItems as $item): ?>
        <a href="<?= e(APP_URL) ?>?page=<?= e($item['page']) ?>" class="text-sm text-slate-200 transition hover:text-gold">
          <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= e(APP_URL) ?>?page=contribute" class="primary-button text-sm">Support</a>
      <a href="<?= e(APP_URL) ?>?page=admin" class="secondary-button text-sm">Admin</a>
    </div>
  </nav>
</header>
