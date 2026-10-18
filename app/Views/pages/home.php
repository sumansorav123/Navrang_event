<?php
$featuredEvent = $featuredEvent ?? EventModel::getFeatured();
if (!$featuredEvent) {
    $featuredEvent = EventModel::getActive()[0] ?? null;
}
$events = EventModel::getActive();
$wall = ContributionModel::getPublicWall();
$alumni = AlumniModel::getPublicProfiles();
if (!$featuredEvent):
?>
<main class="page-shell py-24">
  <section class="glass-panel mx-auto max-w-2xl rounded-[2rem] p-10 text-center">
    <h1 class="text-3xl font-black text-white">No events are currently published</h1>
    <p class="mt-4 text-slate-300">Please check back soon for upcoming Navrang events.</p>
    <a href="<?= e(APP_URL) ?>?page=archive" class="secondary-button mt-6 inline-flex">Browse previous events</a>
  </section>
</main>
<?php
    return;
endif;
$stats = ContributionModel::getVerifiedStats((int) $featuredEvent['id']);
$goalAmount = (float) ($stats['goal_amount'] ?? 0);
$totalAmount = (float) ($stats['total_amount'] ?? 0);
$progress = $goalAmount > 0 ? min(100, ($totalAmount / $goalAmount) * 100) : 0;
$eventDate = $featuredEvent['event_date'] ?? '2026-10-26';
$eventTime = ($featuredEvent['start_time'] ?? '16:00:00') . ' - ' . ($featuredEvent['end_time'] ?? '20:00:00');
?>
<main>
  <section class="relative overflow-hidden border-b border-white/10">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(247,201,73,0.25),transparent_35%)]"></div>
    <div class="page-shell relative grid gap-10 py-16 md:grid-cols-2 md:items-center">
      <div>
        <div class="mb-4 inline-flex items-center rounded-full border border-gold/30 bg-gold/10 px-3 py-1 text-xs uppercase tracking-[0.35em] text-gold">Alumni Celebration</div>
        <h1 class="max-w-xl text-5xl font-black tracking-tight text-white md:text-6xl">
          <?= e($featuredEvent['title'] ?? 'NAVRANG 1.0 – Dandiya Night') ?>
        </h1>
        <p class="mt-5 max-w-lg text-xl text-slate-200">
          <?= e($featuredEvent['tagline'] ?? 'Nine Colors. One Campus. One Celebration.') ?>
        </p>
        <p class="mt-5 max-w-xl text-slate-300">
          <?= e($featuredEvent['description'] ?? 'Celebrate campus spirit, culture, alumni pride, and the joy of giving back to the university community.') ?>
        </p>
        <div class="mt-8 flex flex-wrap gap-4">
          <a href="<?= e(APP_URL) ?>?page=contribute" class="primary-button">Contribute Now</a>
          <a href="<?= e(APP_URL) ?>?page=event" class="secondary-button">Event Details</a>
        </div>

        <div class="mt-8 flex flex-wrap gap-6 text-sm text-slate-300">
          <div><span class="font-semibold text-white">Date:</span> <?= e(date('d M Y', strtotime($eventDate))) ?></div>
          <div><span class="font-semibold text-white">Time:</span> <?= e($eventTime) ?></div>
          <div><span class="font-semibold text-white">Venue:</span> <?= e($featuredEvent['venue'] ?? 'Girls Hostel Lawn, Pondicherry University, Karaikal Campus') ?></div>
        </div>
      </div>

      <div class="relative mx-auto w-full max-w-xl">
        <div class="absolute -inset-3 rounded-[2rem] bg-gradient-to-r from-gold/30 to-magenta/30 blur-3xl"></div>
        <div class="hero-ring relative overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900/80 p-4 shadow-2xl">
          <img src="<?= e(eventImageUrl($featuredEvent['poster_url'] ?? null)) ?>" data-fallback="<?= e(eventImageFallbackUrl()) ?>" onerror="this.onerror=null;this.src=this.dataset.fallback" alt="<?= e(($featuredEvent['title'] ?? 'Event') . ' poster') ?>" class="h-[480px] w-full rounded-[1.4rem] object-cover" />
          <div class="mt-4 rounded-2xl border border-white/10 bg-slate-950/70 p-4">
            <div class="flex items-center justify-between text-sm text-slate-300">
              <span>Countdown</span>
              <span class="text-gold">Live</span>
            </div>
            <div id="countdown" class="mt-3" data-target-date="<?= e((new DateTime($eventDate . ' ' . ($featuredEvent['start_time'] ?? '16:00:00')))->format('c')) ?>"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="page-shell py-16">
    <div class="grid gap-6 md:grid-cols-4">
      <div class="glass-panel card-hover rounded-2xl p-6">
        <div class="text-sm text-slate-300">Verified contributions</div>
        <div class="mt-3 text-3xl font-bold text-gold" data-number="<?= (int) round((float) $stats['total_amount']) ?>" data-prefix="₹">₹0</div>
      </div>
      <div class="glass-panel card-hover rounded-2xl p-6">
        <div class="text-sm text-slate-300">Verified contributors</div>
        <div class="mt-3 text-3xl font-bold text-white" data-number="<?= (int) ($stats['contributors'] ?? 0) ?>">0</div>
      </div>
      <div class="glass-panel card-hover rounded-2xl p-6">
        <div class="text-sm text-slate-300">Participating batches</div>
        <div class="mt-3 text-3xl font-bold text-white" data-number="<?= (int) ($stats['batches'] ?? 0) ?>">0</div>
      </div>
      <div class="glass-panel card-hover rounded-2xl p-6">
        <div class="text-sm text-slate-300">Goal achieved</div>
        <div class="mt-3 text-3xl font-bold text-white"><?= round($progress) ?>%</div>
      </div>
    </div>
  </section>

  <section class="page-shell py-16">
    <div class="mb-10 flex items-center justify-between gap-3">
      <h2 class="section-title">Featured event</h2>
      <a href="<?= e(APP_URL) ?>?page=event" class="text-sm text-gold">View details →</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <?php foreach (array_slice($events, 0, 3) as $event): ?>
        <article class="glass-panel card-hover overflow-hidden rounded-3xl">
          <img src="<?= e(eventImageUrl($event['poster_url'] ?? null)) ?>" data-fallback="<?= e(eventImageFallbackUrl()) ?>" onerror="this.onerror=null;this.src=this.dataset.fallback" alt="<?= e($event['title'] . ' poster') ?>" class="h-56 w-full object-cover" />
          <div class="p-6">
            <div class="mb-2 inline-flex rounded-full bg-white/5 px-2.5 py-1 text-[10px] uppercase tracking-[0.25em] text-gold"><?= e($event['status']) ?></div>
            <h3 class="text-2xl font-bold text-white"><?= e($event['title']) ?></h3>
            <p class="mt-3 text-sm text-slate-300"><?= e($event['tagline']) ?></p>
            <p class="mt-3 text-sm text-slate-400"><?= e(date('d M Y', strtotime($event['event_date']))) ?> • <?= e($event['venue']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

 

  <section class="page-shell py-16">
    <div class="mb-10 flex items-center justify-between">
      <h2 class="section-title">Supporters wall</h2>
      <a href="<?= e(APP_URL) ?>?page=contribute" class="text-sm text-gold">Become a supporter →</a>
    </div>

    <div class="grid auto-rows-fr gap-5 sm:grid-cols-2 xl:grid-cols-3">
      <?php foreach (array_slice($wall, 0, 6) as $contribution): ?>
        <?php $supporterName = trim((string) ($contribution['full_name'] ?? '')) ?: 'Name Not Available'; ?>
        <article class="glass-panel card-hover flex h-full min-h-64 flex-col rounded-3xl border border-white/10 p-6 shadow-xl shadow-black/10">
          <div class="flex items-center gap-4">
            <?php if (!empty($contribution['photo_url'])): ?>
              <img src="<?= e(publicFileUrl($contribution['photo_url'])) ?>" alt="<?= e('Photo shared by ' . $supporterName) ?>" class="h-20 w-20 shrink-0 rounded-full border-2 border-gold/50 object-cover shadow-lg shadow-gold/10" loading="lazy" />
            <?php else: ?>
              <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full border border-gold/30 bg-gradient-to-br from-gold/20 to-magenta/20 text-2xl font-bold text-gold" aria-label="No photo shared">
                <?= e(strtoupper(substr($supporterName, 0, 1))) ?>
              </div>
            <?php endif; ?>
            <div class="min-w-0">
              <h3 class="break-words text-lg font-semibold text-white"><?= e($supporterName) ?></h3>
              <?php if (!empty($contribution['department'])): ?>
                <p class="mt-1 text-sm text-slate-300"><?= e($contribution['department']) ?></p>
              <?php endif; ?>
              <?php if (!empty($contribution['batch'])): ?>
                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400"><?= e($contribution['batch']) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/10 pt-4">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-200">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-300" aria-hidden="true"></span>
              Verified Contribution
            </span>
            <?php if ($contribution['amount'] !== null): ?>
              <span class="font-semibold text-gold" aria-label="Publicly shared contribution amount"><?= e(formatCurrency((float) $contribution['amount'])) ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($contribution['message'])): ?>
            <p class="mt-4 flex-1 text-sm leading-relaxed text-slate-300">“<?= e($contribution['message']) ?>”</p>
          <?php endif; ?>
          <div class="mt-4 border-t border-white/5 pt-3 text-xs text-slate-400">
            Event: <span class="font-medium text-slate-300"><?= e($contribution['event_title']) ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if ($wall === []): ?>
      <div class="glass-panel rounded-2xl border border-white/10 p-8 text-center text-slate-300">No verified contributions have been shared publicly yet.</div>
    <?php endif; ?>
  </section>

</main>
