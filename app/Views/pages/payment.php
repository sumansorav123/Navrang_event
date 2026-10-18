<?php
$reference = trim((string) ($_GET['reference'] ?? ''));
$record = $reference !== '' ? ContributionModel::findByReference($reference) : null;
$notice = '';

if (!$record && !empty($_GET['id'])) {
    $record = ContributionModel::findByReference((string) $_GET['id']);
}

if ($record) {
    $event = EventModel::findById((int) ($record['event_id'] ?? 0)) ?: EventModel::getFeatured();
} else {
    $event = EventModel::getFeatured();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_submit']) && $record) {
    $paymentRef = trim((string) ($_POST['payment_reference'] ?? ''));
    $evidencePath = null;
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $notice = 'Your form session expired. Please refresh the page and try again.';
    } elseif (preg_match('/^[A-Za-z0-9._-]{4,191}$/D', $paymentRef) !== 1) {
        $notice = 'Enter a valid transaction reference using letters, numbers, periods, underscores, or hyphens.';
    } elseif (!in_array($record['status'], ['pending_payment', 'pending', 'pending_verification'], true)) {
        $notice = 'This contribution can no longer be updated.';
    } else {
        if ((int) ($_FILES['payment_evidence']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $evidencePath = saveUploadedImage($_FILES['payment_evidence'], true);
            if ($evidencePath === null) {
                $notice = 'Payment proof must be a valid JPG, PNG, or WebP image under 2MB.';
            }
        }

        $pdo = requireDatabase();
        if ($notice === '' && $pdo) {
            $statement = $pdo->prepare('UPDATE contributions SET payment_reference = :payment_ref, evidence_path = COALESCE(:evidence_path, evidence_path), status = "pending_verification", updated_at = NOW() WHERE id = :id AND status IN ("pending_payment", "pending", "pending_verification")');
            $statement->execute([
                ':payment_ref' => $paymentRef,
                ':evidence_path' => $evidencePath,
                ':id' => (int) $record['id'],
            ]);
            if ($statement->rowCount() > 0) {
                $notice = 'Your payment details have been submitted. Your contribution is awaiting admin verification.';
                $record['payment_reference'] = $paymentRef;
                $record['status'] = 'pending_verification';
            } else {
                $notice = 'We could not update your payment details. Please reload and try again.';
            }
        } elseif ($notice === '') {
            $notice = 'Payment confirmation is temporarily unavailable. Please try again later.';
        }
    }
}
?>
<main class="page-shell py-16">
  <section class="mb-10 text-center">
    <div class="mb-4 inline-flex rounded-full border border-gold/30 bg-gold/10 px-3 py-1 text-xs uppercase tracking-[0.3em] text-gold">Payment Confirmation</div>
    <h1 class="text-4xl font-black text-white md:text-5xl">Complete your contribution payment</h1>
  </section>

  <?php if ($record && ($record['status'] ?? '') === 'verified'): ?>
    <div class="mb-8 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-4 text-emerald-100" role="status">
      <strong>Thank you for your contribution.</strong> Your payment has been verified.
    </div>
  <?php elseif ($record && ($record['status'] ?? '') === 'rejected'): ?>
    <div class="mb-8 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-4 text-red-100" role="status">Your payment could not be verified. Please contact the event administrators for assistance.</div>
  <?php elseif ($notice !== ''): ?>
    <div class="mb-8 rounded-2xl border border-gold/30 bg-gold/10 px-4 py-3 text-gold" role="status"><?= e($notice) ?></div>
  <?php elseif ($record && in_array(($record['status'] ?? ''), ['pending_payment', 'pending', 'pending_verification', 'approved'], true)): ?>
    <div class="mb-8 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-slate-200" role="status">Your contribution is pending payment verification. A thank-you confirmation will appear here after an administrator verifies it.</div>
  <?php endif; ?>

  <?php if (!$record): ?>
    <div class="glass-panel rounded-[2rem] p-8 text-center text-slate-300">
      We could not find the contribution for this payment page. Please return to the contribution form and submit again.
    </div>
  <?php else: ?>
    <section class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
      <div class="glass-panel rounded-[2rem] p-6 md:p-8">
        <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-5">
          <div>
            <div class="text-xs uppercase tracking-[0.3em] text-gold">Event</div>
            <h2 class="mt-2 text-3xl font-black text-white"><?= e($event['title'] ?? 'Navrang event') ?></h2>
          </div>
          <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-[0.2em] text-slate-300">Status: <?= e($record['status'] ?? 'pending') ?></span>
        </div>

        <div class="mt-6 space-y-4 text-slate-300">
          <div class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-900/60 p-4">
            <span>Alumni</span>
            <strong class="text-white"><?= e($record['contributor_name'] ?? 'Contributor') ?></strong>
          </div>
          <div class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-900/60 p-4">
            <span>Contribution reference</span>
            <strong class="text-white"><?= e($reference) ?></strong>
          </div>
          <div class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-900/60 p-4">
            <span>Amount</span>
            <strong class="text-white"><?= e(formatCurrency((float) ($record['amount'] ?? 0))) ?></strong>
          </div>
        </div>

        <div class="mt-8 rounded-[1.5rem] border border-dashed border-white/10 bg-slate-900/60 p-5 text-center">
          <?php if (!empty($event['upi_qr_path'])): ?>
            <img src="<?= e(publicFileUrl($event['upi_qr_path'], '')) ?>" alt="UPI QR code" class="mx-auto h-52 w-52 rounded-2xl object-cover" />
          <?php else: ?>
            <div class="flex h-52 items-center justify-center rounded-2xl border border-dashed border-white/10 text-slate-300">No QR code configured yet. Please ask the admin to add the payment QR.</div>
          <?php endif; ?>
          <div class="mt-4 text-sm text-slate-300">UPI ID: <?= e($event['upi_id'] ?? 'Not configured') ?></div>
        </div>
      </div>

      <aside class="space-y-6">
        <div class="glass-panel rounded-[2rem] p-6">
          <h3 class="text-2xl font-bold text-white">Payment instructions</h3>
          <ol class="mt-4 space-y-3 text-sm text-slate-300">
            <li>1. Scan the QR code or use the payment UPI ID shown above.</li>
            <li>2. Pay the exact amount shown on this page.</li>
            <li>3. Enter the transaction reference below and submit.</li>
            <li>4. Your contribution will stay pending until an admin verifies it.</li>
          </ol>
        </div>

        <?php if (in_array(($record['status'] ?? ''), ['pending_payment', 'pending', 'pending_verification'], true)): ?>
        <div class="glass-panel rounded-[2rem] p-6">
          <h3 class="text-xl font-bold text-white">Submit payment confirmation</h3>
          <form method="post" enctype="multipart/form-data" class="mt-5 space-y-4">
            <input type="hidden" name="payment_submit" value="1">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <label class="block text-sm text-slate-300">
              UPI transaction / reference ID
              <input name="payment_reference" required class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
            </label>
            <label class="block text-sm text-slate-300">
              Payment proof (optional)
              <input type="file" name="payment_evidence" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white file:mr-3 file:rounded-lg file:border-0 file:bg-gold file:px-3 file:py-2 file:text-slate-950" />
              <span class="mt-1 block text-xs text-slate-400">Private to authorized administrators; JPG, PNG, or WebP, up to 2MB.</span>
            </label>
            <button type="submit" class="primary-button w-full">Confirm payment</button>
          </form>
        </div>
        <?php endif; ?>
      </aside>
    </section>
  <?php endif; ?>
</main>
