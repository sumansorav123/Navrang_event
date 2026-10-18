<?php
$archive = EventModel::getArchive();
?>
<main class="page-shell py-16">
  <section class="mb-10 text-center">
    <div class="mb-4 inline-flex rounded-full border border-gold/30 bg-gold/10 px-3 py-1 text-xs uppercase tracking-[0.3em] text-gold">Previous Events</div>
    <h1 class="text-4xl font-black text-white md:text-5xl">Memories from the campus journey</h1>
  </section>

  <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($archive as $event): ?>
      <article class="glass-panel card-hover overflow-hidden rounded-3xl">
        <img src="<?= e(eventImageUrl($event['poster_url'] ?? null)) ?>" data-fallback="<?= e(eventImageFallbackUrl()) ?>" onerror="this.onerror=null;this.src=this.dataset.fallback" alt="<?= e($event['title'] . ' poster') ?>" class="h-64 w-full object-cover" />
        <div class="p-6">
          <div class="mb-2 text-xs uppercase tracking-[0.25em] text-gold"><?= e(date('Y', strtotime($event['event_date'] ?? '2026-10-26'))) ?></div>
          <h2 class="text-2xl font-bold text-white"><?= e($event['title']) ?></h2>
          <p class="mt-3 text-slate-300"><?= e($event['tagline']) ?></p>
          <a href="<?= e(APP_URL) ?>?page=event" class="mt-5 inline-block text-sm font-semibold text-gold">View event →</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</main>
