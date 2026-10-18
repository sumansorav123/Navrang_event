<?php
$events = EventModel::getActive();
$gallery = [];
foreach ($events as $event) {
    foreach (EventModel::getGallery((int) $event['id']) as $image) {
        $gallery[] = $image;
    }
}
if (empty($gallery)) {
    foreach (availableEventImageAssets() as $asset) {
        $gallery[] = [
            'image_url' => $asset,
            'title' => basename($asset),
        ];
    }
}
?>
<main class="page-shell py-16">
  <section class="mb-10 text-center">
    <div class="mb-4 inline-flex rounded-full border border-gold/30 bg-gold/10 px-3 py-1 text-xs uppercase tracking-[0.3em] text-gold">Event Gallery</div>
    <h1 class="text-4xl font-black text-white md:text-5xl">Moments worth revisiting</h1>
  </section>

  <!-- <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($gallery as $image): ?>
      <img src="<?= e(eventImageUrl($image['image_url'] ?? null)) ?>" data-fallback="<?= e(eventImageFallbackUrl()) ?>" onerror="this.onerror=null;this.src=this.dataset.fallback" alt="<?= e($image['title'] ?? 'Event gallery image') ?>" class="h-72 w-full rounded-3xl object-cover shadow-xl" loading="lazy" />
    <?php endforeach; ?>
  </div> -->
</main>
