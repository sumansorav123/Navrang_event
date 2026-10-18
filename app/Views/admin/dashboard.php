<?php
if (!isAdminLoggedIn()) {
    redirect(APP_URL . '?page=admin');
}

$canManageDashboard = in_array($_SESSION['admin_role'] ?? '', ['super_admin', 'editor'], true);
$pdo = requireDatabase();
$eventCount = 0;
$featuredEvent = null;
$verifiedContributions = 0;
$verifiedContributors = 0;
$pending = 0;
$batches = 0;
$events = [];
$contributions = [];
$eventImageAssets = availableEventImageAssets();
if ($pdo) {
    $eventCount = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
    $featuredEvent = $pdo->query('SELECT title FROM events WHERE is_featured = 1 LIMIT 1')->fetch();
    $verifiedContributions = (float) ($pdo->query('SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE status = "verified"')->fetchColumn() ?: 0);
    $verifiedContributors = (int) ($pdo->query('SELECT COUNT(DISTINCT contributor_name) FROM contributions WHERE status = "verified"')->fetchColumn() ?: 0);
    $pending = (int) ($pdo->query('SELECT COUNT(*) FROM contributions WHERE status IN ("pending", "pending_payment", "pending_verification")')->fetchColumn() ?: 0);
    $batches = (int) ($pdo->query('SELECT COUNT(DISTINCT batch) FROM contributions WHERE status = "verified"')->fetchColumn() ?: 0);
    $events = EventModel::getAll();
    $contributions = ContributionModel::getAdminList();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$canManageDashboard) {
    $message = 'Your account has view-only access and cannot make changes.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $message = 'Your form session expired. Please refresh the page and try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_event'])) {
        $title = trim((string) ($_POST['title'] ?? ''));
        $tagline = trim((string) ($_POST['tagline'] ?? ''));
        $venue = trim((string) ($_POST['venue'] ?? ''));
        $goal = (float) ($_POST['goal_amount'] ?? 0);
        if ($pdo && $title !== '' && $venue !== '') {
            $baseSlug = slugify($title);
            $slug = $baseSlug !== '' ? $baseSlug : 'event';
            $duplicateCheck = $pdo->prepare('SELECT id FROM events WHERE slug = :slug LIMIT 1');
            $suffix = 1;
            while (true) {
                $duplicateCheck->execute([':slug' => $slug]);
                if (!$duplicateCheck->fetch()) {
                    break;
                }
                $slug = $baseSlug . '-' . ++$suffix;
            }

            $posterUploadProvided = (int) ($_FILES['event_poster']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $posterUrl = null;
            if ($posterUploadProvided) {
                $posterUrl = saveUploadedImage($_FILES['event_poster'], false);
            }

            $qrUploadProvided = (int) ($_FILES['event_qr']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $qrPath = null;
            if ($qrUploadProvided) {
                $qrPath = saveUploadedImage($_FILES['event_qr'], false);
            }

            $selectedPosterAsset = trim((string) ($_POST['event_poster_asset'] ?? ''));
            $invalidPosterAsset = !$posterUploadProvided && $selectedPosterAsset !== '' && !in_array($selectedPosterAsset, $eventImageAssets, true);
            if (!$posterUploadProvided && !$invalidPosterAsset && $selectedPosterAsset !== '') {
                $posterUrl = $selectedPosterAsset;
            }
            $uploadFailed = ($posterUploadProvided && $posterUrl === null)
                || ($qrUploadProvided && $qrPath === null)
                || $invalidPosterAsset;

            if ($uploadFailed) {
                $message = 'Event images must be valid JPG, PNG, or WebP files under 2MB. Choose an existing image from the list or select a valid upload. The event was not created.';
            } else {
                $insert = $pdo->prepare('INSERT INTO events (slug, title, tagline, description, event_date, start_time, end_time, venue, status, is_featured, poster_url, goal_amount, upi_id, upi_qr_path, is_contribution_open, created_at) VALUES (:slug, :title, :tagline, :description, :event_date, :start_time, :end_time, :venue, "published", 0, :poster_url, :goal_amount, :upi_id, :upi_qr_path, 1, NOW())');
                $insert->execute([
                    ':slug' => $slug,
                    ':title' => $title,
                    ':tagline' => $tagline,
                    ':description' => trim((string) ($_POST['description'] ?? 'New campus event created through the admin dashboard.')),
                    ':event_date' => $_POST['event_date'] ?? date('Y-m-d'),
                    ':start_time' => $_POST['start_time'] ?? '16:00:00',
                    ':end_time' => $_POST['end_time'] ?? '20:00:00',
                    ':venue' => $venue,
                    ':poster_url' => $posterUrl,
                    ':goal_amount' => number_format($goal, 2, '.', ''),
                    ':upi_id' => $_POST['upi_id'] ?? 'navrang@upi',
                    ':upi_qr_path' => $qrPath,
                ]);
                startAudit('event_created', 'Created event: ' . $title);
                $message = 'Event created successfully.';
                $events = EventModel::getAll();
            }
        }
    }

    if (isset($_POST['event_action']) && isset($_POST['event_id'])) {
        $eventId = (int) $_POST['event_id'];
        $status = trim((string) ($_POST['status'] ?? ''));
        if ($eventId > 0 && EventModel::updateStatus($eventId, $status)) {
            startAudit('event_status_updated', 'Updated event status for ID ' . $eventId . ' to ' . $status);
            $message = 'Event status updated successfully.';
            $events = EventModel::getAll();
        }
    }

    if (isset($_POST['delete_event']) && isset($_POST['event_id'])) {
        $eventId = (int) $_POST['event_id'];
        if ($eventId > 0 && EventModel::delete($eventId)) {
            startAudit('event_deleted', 'Deleted event ID ' . $eventId);
            $message = 'Event deleted successfully.';
            $events = EventModel::getAll();
        }
    }

    if (isset($_POST['verify_contribution']) && isset($_POST['contribution_id'])) {
        $contributionId = (int) $_POST['contribution_id'];
        $decision = trim((string) ($_POST['decision'] ?? ''));
        $note = trim((string) ($_POST['verification_note'] ?? ''));
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);

        if ($contributionId > 0 && in_array($decision, ['verified', 'rejected', 'refunded', 'duplicate'], true)) {
            if (ContributionModel::updateStatus($contributionId, $decision, $adminId, $note)) {
                startAudit('contribution_reviewed', 'Updated contribution ID ' . $contributionId . ' to ' . $decision);
                $message = 'Contribution status updated successfully.';
                $contributions = ContributionModel::getAdminList();
            } else {
                $message = 'The contribution could not be updated. A payment reference is required before verification.';
            }
        }
    }
}
?>
<main class="page-shell py-10">
  <div class="mb-8 flex items-center justify-between gap-4">
    <div>
      <div class="text-xs uppercase tracking-[0.3em] text-gold">Admin dashboard</div>
      <h1 class="mt-2 text-4xl font-black text-white">Welcome back, <?= e($_SESSION['admin_name'] ?? 'Administrator') ?></h1>
    </div>
    <a href="<?= e(APP_URL) ?>?page=admin&logout=1" class="secondary-button">Logout</a>
  </div>

  <?php if (!empty($message)): ?>
    <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-200"><?= e($message) ?></div>
  <?php endif; ?>

  <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-6">
    <div class="glass-panel rounded-2xl p-5">
      <div class="text-sm text-slate-300">Total events</div>
      <div class="mt-2 text-3xl font-black text-gold"><?= $eventCount ?></div>
    </div>
    <div class="glass-panel rounded-2xl p-5">
      <div class="text-sm text-slate-300">Featured</div>
      <div class="mt-2 text-lg font-bold text-white"><?= e($featuredEvent['title'] ?? 'Not set') ?></div>
    </div>
    <div class="glass-panel rounded-2xl p-5">
      <div class="text-sm text-slate-300">Verified amount</div>
      <div class="mt-2 text-2xl font-black text-white"><?= e(formatCurrency($verifiedContributions)) ?></div>
    </div>
    <div class="glass-panel rounded-2xl p-5">
      <div class="text-sm text-slate-300">Verified contributors</div>
      <div class="mt-2 text-3xl font-black text-white"><?= $verifiedContributors ?></div>
    </div>
    <div class="glass-panel rounded-2xl p-5">
      <div class="text-sm text-slate-300">Pending</div>
      <div class="mt-2 text-3xl font-black text-white"><?= $pending ?></div>
    </div>
    <div class="glass-panel rounded-2xl p-5">
      <div class="text-sm text-slate-300">Batches</div>
      <div class="mt-2 text-3xl font-black text-white"><?= $batches ?></div>
    </div>
  </section>

  <section class="mt-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
    <div class="glass-panel rounded-[2rem] p-6">
      <h2 class="text-2xl font-bold text-white">Create event</h2>
      <?php if ($canManageDashboard): ?>
      <form method="post" enctype="multipart/form-data" class="mt-6 grid gap-4 md:grid-cols-2">
        <input type="hidden" name="create_event" value="1">
        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
        <label class="block text-sm text-slate-300 md:col-span-2">
          Event title
          <input name="title" required class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>
        <label class="block text-sm text-slate-300 md:col-span-2">
          Tagline
          <input name="tagline" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>
        <label class="block text-sm text-slate-300 md:col-span-2">
          Description
          <textarea name="description" rows="3" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white"></textarea>
        </label>
        <label class="block text-sm text-slate-300">
          Event poster image
          <input type="file" name="event_poster" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white file:mr-3 file:rounded-lg file:border-0 file:bg-gold file:px-3 file:py-2 file:text-slate-950" />
        </label>
        <label class="block text-sm text-slate-300">
          Or choose an image already in the gallery
          <select name="event_poster_asset" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white">
            <option value="">Use the default event image</option>
            <?php foreach ($eventImageAssets as $asset): ?>
              <option value="<?= e($asset) ?>"><?= e(basename($asset)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="block text-sm text-slate-300">
          Payment QR image
          <input type="file" name="event_qr" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white file:mr-3 file:rounded-lg file:border-0 file:bg-gold file:px-3 file:py-2 file:text-slate-950" />
        </label>
        <label class="block text-sm text-slate-300">
          Event date
          <input type="date" name="event_date" value="<?= e(date('Y-m-d')) ?>" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>
        <label class="block text-sm text-slate-300">
          Venue
          <input name="venue" required class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>
        <label class="block text-sm text-slate-300">
          Start time
          <input type="time" name="start_time" value="16:00" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>
        <label class="block text-sm text-slate-300">
          End time
          <input type="time" name="end_time" value="20:00" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>
        <label class="block text-sm text-slate-300">
          Fundraising goal
          <input type="number" name="goal_amount" min="0" step="0.01" value="250000" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>
        <label class="block text-sm text-slate-300">
          UPI ID
          <input name="upi_id" value="navrang@upi" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>
        <button type="submit" class="primary-button md:col-span-2">Create event</button>
      </form>
      <?php else: ?>
        <p class="mt-4 text-sm text-slate-400">This account has view-only access.</p>
      <?php endif; ?>
    </div>

    <div class="space-y-6">
      <div class="glass-panel rounded-[2rem] p-6">
        <h2 class="text-2xl font-bold text-white">Recent activity</h2>
        <ul class="mt-6 space-y-3 text-sm text-slate-300">
          <li>• Admin login secured with session validation.</li>
          <li>• Event details are stored and can be updated in the admin database.</li>
          <li>• Contribution submissions are queued for verification.</li>
          <li>• Public totals reflect verified records only.</li>
        </ul>
      </div>
      <div class="glass-panel rounded-[2rem] p-6">
        <h2 class="text-2xl font-bold text-white">Quick actions</h2>
        <div class="mt-4 flex flex-wrap gap-3">
          <a href="<?= e(APP_URL) ?>?page=home" class="secondary-button">View site</a>
          <a href="<?= e(APP_URL) ?>?page=contribute" class="secondary-button">Contribution form</a>
        </div>
      </div>
    </div>
  </section>

  <section class="mt-12 glass-panel rounded-[2rem] p-6">
    <div class="mb-5 flex items-center justify-between gap-3">
      <h2 class="text-2xl font-bold text-white">Manage events</h2>
      <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-[0.2em] text-slate-300"><?= count($events) ?> total</span>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-left text-sm text-slate-300">
        <thead class="border-b border-white/10 text-xs uppercase tracking-[0.2em] text-slate-400">
          <tr>
            <th class="px-3 py-3">Event</th>
            <th class="px-3 py-3">Date</th>
            <th class="px-3 py-3">Status</th>
            <th class="px-3 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $event): ?>
            <tr class="border-b border-white/10 align-top">
              <td class="px-3 py-4">
                <div class="font-semibold text-white"><?= e($event['title']) ?></div>
                <div class="text-xs text-slate-400"><?= e($event['slug']) ?></div>
              </td>
              <td class="px-3 py-4"><?= e(date('d M Y', strtotime($event['event_date'] ?? date('Y-m-d')))) ?></td>
              <td class="px-3 py-4">
                <span class="rounded-full border border-gold/30 bg-gold/10 px-2.5 py-1 text-[10px] uppercase tracking-[0.2em] text-gold"><?= e($event['status'] ?? 'draft') ?></span>
              </td>
              <td class="px-3 py-4">
                <?php if ($canManageDashboard): ?>
                <div class="flex flex-wrap gap-2">
                  <form method="post" class="inline-block">
                    <input type="hidden" name="event_action" value="1">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">
                    <select name="status" class="rounded-lg border border-white/10 bg-slate-900 px-2 py-2 text-white">
                      <option value="draft" <?= (($event['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
                      <option value="published" <?= (($event['status'] ?? 'draft') === 'published') ? 'selected' : '' ?>>Published</option>
                      <option value="archived" <?= (($event['status'] ?? 'draft') === 'archived') ? 'selected' : '' ?>>Archived</option>
                      <option value="cancelled" <?= (($event['status'] ?? 'draft') === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="secondary-button ml-2">Update</button>
                  </form>
                  <form method="post" class="inline-block" onsubmit="return confirm('Delete this event? This action cannot be undone.');">
                    <input type="hidden" name="delete_event" value="1">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">
                    <button type="submit" class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-red-200">Delete</button>
                  </form>
                </div>
                <?php else: ?>
                  <span class="text-xs text-slate-500">View only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="mt-12 glass-panel rounded-[2rem] p-6">
    <div class="mb-5 flex items-center justify-between gap-3">
      <h2 class="text-2xl font-bold text-white">Contribution verification</h2>
      <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-[0.2em] text-slate-300"><?= count($contributions) ?> records</span>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-left text-sm text-slate-300">
        <thead class="border-b border-white/10 text-xs uppercase tracking-[0.2em] text-slate-400">
          <tr>
            <th class="px-3 py-3">Contributor</th>
            <th class="px-3 py-3">Event</th>
            <th class="px-3 py-3">Amount</th>
            <th class="px-3 py-3">Status</th>
            <th class="px-3 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($contributions as $contribution): ?>
            <tr class="border-b border-white/10 align-top">
              <td class="px-3 py-4">
                <div class="font-semibold text-white"><?= e($contribution['contributor_name'] ?? 'Anonymous') ?></div>
                <div class="text-xs text-slate-400"><?= e($contribution['transaction_ref'] ?? '—') ?></div>
                <?php if (!empty($contribution['payment_reference'])): ?>
                  <div class="mt-1 text-xs text-slate-400">Payment ref: <?= e($contribution['payment_reference']) ?></div>
                <?php endif; ?>
                <?php if (!empty($contribution['photo_url'])): ?>
                  <img src="<?= e(publicFileUrl($contribution['photo_url'])) ?>" alt="<?= e('Private submission photo for ' . ($contribution['contributor_name'] ?? 'contributor')) ?>" class="mt-3 h-16 w-16 rounded-full border border-white/10 object-cover">
                <?php endif; ?>
                <?php if (!empty($contribution['evidence_path'])): ?>
                  <a href="<?= e(publicFileUrl($contribution['evidence_path'])) ?>" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs text-gold underline">View private payment proof</a>
                <?php endif; ?>
                <div class="mt-2 space-y-1 text-xs text-slate-400">
                  <div>Public wall: <?= !empty($contribution['is_public']) ? 'enabled' : 'not enabled' ?> · Consent: <?= e($contribution['consent_status'] ?? 'none') ?></div>
                  <div>Name <?= !empty($contribution['display_name']) && empty($contribution['is_anonymous']) ? 'visible' : 'hidden' ?> · Photo <?= !empty($contribution['display_photo']) ? 'visible' : 'hidden' ?></div>
                  <div>Department/batch <?= !empty($contribution['display_batch']) ? 'visible' : 'hidden' ?> · Amount <?= !empty($contribution['display_amount']) ? 'visible' : 'hidden' ?> · Message <?= !empty($contribution['display_message']) ? 'visible' : 'hidden' ?></div>
                  <?php if (!empty($contribution['verified_at'])): ?>
                    <div>Last reviewed: <?= e($contribution['verified_at']) ?></div>
                  <?php endif; ?>
                </div>
              </td>
              <td class="px-3 py-4"><?= e($contribution['event_title'] ?? 'Unassigned') ?></td>
              <td class="px-3 py-4"><?= e(formatCurrency((float) ($contribution['amount'] ?? 0))) ?></td>
              <td class="px-3 py-4">
                <span class="rounded-full border border-gold/30 bg-gold/10 px-2.5 py-1 text-[10px] uppercase tracking-[0.2em] text-gold"><?= e($contribution['status'] ?? 'pending') ?></span>
              </td>
              <td class="px-3 py-4">
                <?php if ($canManageDashboard): ?>
                <form method="post" class="space-y-2">
                  <input type="hidden" name="verify_contribution" value="1">
                  <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                  <input type="hidden" name="contribution_id" value="<?= (int) ($contribution['id'] ?? 0) ?>">
                  <select name="decision" class="w-full rounded-lg border border-white/10 bg-slate-900 px-2 py-2 text-white">
                    <option value="verified" <?= (($contribution['status'] ?? 'pending') === 'verified') ? 'selected' : '' ?>>Verified</option>
                    <option value="rejected" <?= (($contribution['status'] ?? 'pending') === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    <option value="refunded" <?= (($contribution['status'] ?? 'pending') === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                    <option value="duplicate" <?= (($contribution['status'] ?? 'pending') === 'duplicate') ? 'selected' : '' ?>>Duplicate</option>
                  </select>
                  <textarea name="verification_note" rows="2" placeholder="Internal note" class="w-full rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-white"></textarea>
                  <button type="submit" class="secondary-button">Update</button>
                </form>
                <?php else: ?>
                  <span class="text-xs text-slate-500">View only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
