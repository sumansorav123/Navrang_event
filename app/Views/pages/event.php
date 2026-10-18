<?php
$event = $featuredEvent ?? EventModel::getFeatured();
if (!$event):
?>
<main class="page-shell py-24">
  <section class="glass-panel mx-auto max-w-2xl rounded-[2rem] p-10 text-center">
    <h1 class="text-3xl font-black text-white">No event is currently published</h1>
    <p class="mt-4 text-slate-300">Please check back soon for event details.</p>
    <a href="<?= e(APP_URL) ?>?page=archive" class="secondary-button mt-6 inline-flex">Browse previous events</a>
  </section>
</main>
<?php
    return;
endif;
$gallery = EventModel::getGallery((int) ($event['id'] ?? 0));
$stats = $event ? ContributionModel::getVerifiedStats((int) $event['id']) : ['total_amount' => '0.00', 'contributors' => 0, 'batches' => 0, 'goal_amount' => '250000.00'];
$totalAmount = (float) ($stats['total_amount'] ?? 0);
$goalAmount = (float) ($stats['goal_amount'] ?? 0);
$progress = $goalAmount > 0 ? min(100, ($totalAmount / $goalAmount) * 100) : 0;
?>
<main class="page-shell py-16">
  <section class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr]">
    <div class="glass-panel rounded-[2rem] p-6 md:p-8">
      <div class="mb-6 inline-flex rounded-full border border-gold/30 bg-gold/10 px-3 py-1 text-xs uppercase tracking-[0.3em] text-gold">Event Spotlight</div>
      <h1 class="text-4xl font-black text-white md:text-5xl"><?= e($event['title'] ?? 'NAVRANG 1.0 – Dandiya Night') ?></h1>
      <p class="mt-4 text-xl text-slate-200"><?= e($event['tagline'] ?? 'Nine Colors. One Campus. One Celebration.') ?></p>
      <img src="<?= e(eventImageUrl($event['poster_url'] ?? null)) ?>" data-fallback="<?= e(eventImageFallbackUrl()) ?>" onerror="this.onerror=null;this.src=this.dataset.fallback" alt="<?= e(($event['title'] ?? 'Event') . ' poster') ?>" class="mt-8 h-[420px] w-full rounded-3xl object-cover" />
      <p class="mt-8 text-slate-300"><?= e($event['description'] ?? 'Join our celebration of dance, music, pride, and fellowship.') ?></p>
    </div>

    <aside class="space-y-6">
      <div class="glass-panel rounded-[2rem] p-6">
        <h2 class="text-2xl font-bold text-white">Event details</h2>
        <ul class="mt-6 space-y-4 text-slate-300">
          <li><span class="font-semibold text-white">Date:</span> <?= e(date('d M Y', strtotime($event['event_date'] ?? '2026-10-26'))) ?></li>
          <li><span class="font-semibold text-white">Time:</span> <?= e(($event['start_time'] ?? '16:00:00') . ' – ' . ($event['end_time'] ?? '20:00:00')) ?></li>
          <li><span class="font-semibold text-white">Venue:</span> <?= e($event['venue'] ?? 'Girls Hostel Lawn, Pondicherry University, Karaikal Campus') ?></li>
          <li><span class="font-semibold text-white">Goal:</span> <?= e(formatCurrency($goalAmount)) ?></li>
        </ul>
      </div>

      <div class="glass-panel rounded-[2rem] p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-white">Fundraising progress</h3>
          <span class="text-sm text-gold"><?= round($progress) ?>%</span>
        </div>
        <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-white/10">
          <div class="h-full rounded-full bg-gradient-to-r from-gold to-magenta" style="width: <?= $progress ?>%"></div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm text-slate-300">
          <span><?= e(formatCurrency($totalAmount)) ?> raised</span>
          <span><?= e(formatCurrency($goalAmount - $totalAmount)) ?> remaining</span>
        </div>
      </div>

      <div class="glass-panel rounded-[2rem] p-6">
        <h3 class="text-lg font-semibold text-white">Paid using UPI</h3>
        <div class="mt-4 rounded-2xl border border-dashed border-white/10 bg-slate-900/60 p-4 text-center">
          <div class="mb-2 text-sm uppercase tracking-[0.3em] text-gold">UPI ID</div>
          <div class="text-2xl font-semibold text-white"><?= e($event['upi_id'] ?? 'navrang@upi') ?></div>
        </div>
      </div>
    </aside>
  </section>

  <section class="mt-16">
    <h2 class="section-title">Gallery highlights</h2>
    <div class="grid gap-5 md:grid-cols-3">
      <?php foreach (array_slice($gallery, 0, 6) as $image): ?>
        <img src="<?= e(eventImageUrl($image['image_url'] ?? null)) ?>" data-fallback="<?= e(eventImageFallbackUrl()) ?>" onerror="this.onerror=null;this.src=this.dataset.fallback" alt="<?= e($image['title'] ?? 'Event gallery image') ?>" class="h-72 w-full rounded-3xl object-cover shadow-xl" />
      <?php endforeach; ?>
    </div>
  </section>
</main>
