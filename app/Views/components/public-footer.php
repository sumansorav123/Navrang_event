<footer class="border-t border-white/10 bg-slate-950/90">
  <div class="page-shell grid gap-8 py-12 md:grid-cols-3">
    <div>
      <div class="mb-4 flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-gold to-magenta font-black text-slate-950">N</div>
        <div>
          <div class="text-sm uppercase tracking-[0.25em] text-gold">Navrang</div>
          <div class="text-xs text-slate-300">Events & Alumni</div>
        </div>
      </div>
      <p class="text-sm text-slate-300">Celebrating campus culture, alumni pride, and community contribution through vibrant student events.</p>
    </div>

    <div>
      <h3 class="mb-4 text-lg font-semibold text-white">Explore</h3>
      <ul class="space-y-2 text-sm text-slate-300">
        <li><a href="<?= e(APP_URL) ?>?page=event" class="hover:text-gold">Event Details</a></li>
        <li><a href="<?= e(APP_URL) ?>?page=gallery" class="hover:text-gold">Gallery</a></li>
        <li><a href="<?= e(APP_URL) ?>?page=archive" class="hover:text-gold">Previous Events</a></li>
        <li><a href="<?= e(APP_URL) ?>?page=contribute" class="hover:text-gold">Contribute</a></li>
      </ul>
    </div>

    <div>
      <h3 class="mb-4 text-lg font-semibold text-white">Connect</h3>
      <ul class="space-y-2 text-sm text-slate-300">
        <li>Email: 25mscsckk0013@pondiuni.ac.in</li>
        <li>Phone: +91 9905987463 , +91 6207768657</li>
        <li>Campus: Pondicherry University, Karaikal</li>
      </ul>
    </div>
  </div>

  <div class="border-t border-white/10 py-4 text-center text-xs text-slate-400">
    © <?= date('Y') ?> Navrang Events. All rights reserved.
    <a href="<?= e(APP_URL) ?>?page=privacy" class="ml-3 hover:text-gold">Privacy</a>
    <a href="<?= e(APP_URL) ?>?page=terms" class="ml-3 hover:text-gold">Terms</a>
  </div>
</footer>
