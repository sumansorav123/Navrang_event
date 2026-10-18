<?php
$events = EventModel::getContributionOpen();
$selectedEvent = $events[0] ?? EventModel::getFeatured();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contribution_submit'])) {
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $batch = trim((string) ($_POST['batch'] ?? ''));
    $department = trim((string) ($_POST['department'] ?? ''));
    $amount = (float) ($_POST['amount'] ?? 0);
    $email = trim((string) ($_POST['email'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $isPublic = !empty($_POST['public_consent']) ? 1 : 0;
    $isAnonymous = !empty($_POST['is_anonymous']) ? 1 : 0;
    $photoUrl = '';
    $uploadError = '';
    $errors = [];

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your form session expired. Please refresh the page and try again.';
    }

    $selectedEvent = null;
    foreach ($events as $event) {
        if ((int) $event['id'] === $eventId) {
            $selectedEvent = $event;
            break;
        }
    }

    if (!$selectedEvent) {
        $errors[] = 'Please select an event that is accepting contributions.';
    }
    if ($fullName === '' || strlen($fullName) > 191) {
        $errors[] = 'Enter a name up to 191 characters long.';
    }
    if (!is_finite($amount) || $amount <= 0 || $amount > 9999999999.99) {
        $errors[] = 'Please enter a valid contribution amount.';
    }
    if ($email !== '' && (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 191)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($batch) > 100 || strlen($department) > 191 || strlen($message) > 2000) {
        $errors[] = 'Batch, department, or message exceeds the allowed length.';
    }

    $photoUploadError = (int) ($_FILES['alumni_photo']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($photoUploadError === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Capture and confirm your alumni photo before continuing.';
    } elseif (empty($errors)) {
        $photoUrl = saveUploadedImage($_FILES['alumni_photo'], true) ?? '';
        if ($photoUrl === '') {
            $uploadError = 'Please upload a valid JPG, PNG, or WebP photo under 2MB.';
        }
    }

    if ($uploadError !== '') {
        $notice = $uploadError;
    } elseif (empty($errors)) {
        $reference = ContributionModel::createPending([
            'event_id' => $eventId,
            'full_name' => $fullName,
            'batch' => $batch,
            'department' => $department,
            'amount' => $amount,
            'email' => $email,
            'message' => $message,
            'photo_url' => $photoUrl,
            'is_public' => $isPublic,
            'is_anonymous' => $isAnonymous,
            'display_name' => !empty($_POST['display_name']),
            'display_photo' => !empty($_POST['display_photo']),
            'display_batch' => !empty($_POST['display_batch']),
            'display_amount' => !empty($_POST['display_amount']),
            'display_message' => !empty($_POST['display_message']),
        ]);

        if ($reference) {
            redirect(APP_URL . '?page=payment&reference=' . urlencode($reference));
        }

        $notice = 'We could not save your submission. Please try again.';
    } else {
        $notice = implode(' ', $errors);
    }
}
?>
<main class="page-shell py-16">
  <section class="mb-10 text-center">
    <div class="mb-4 inline-flex rounded-full border border-gold/30 bg-gold/10 px-3 py-1 text-xs uppercase tracking-[0.3em] text-gold">Contribution Submission</div>
    <h1 class="text-4xl font-black text-white md:text-5xl">Support the next unforgettable celebration</h1>
  </section>

  <?php if ($notice !== ''): ?>
    <div class="mb-8 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-200"><?= e($notice) ?></div>
  <?php endif; ?>

  <section class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
    <form id="contribution-form" method="post" class="glass-panel rounded-[2rem] p-6 md:p-8" enctype="multipart/form-data">
      <input type="hidden" name="contribution_submit" value="1">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <input id="alumni-photo-upload" class="hidden" type="file" name="alumni_photo" accept="image/jpeg,image/png,image/webp">
      <div class="grid gap-5 md:grid-cols-2">
        <label class="block text-sm text-slate-300 md:col-span-2">
          Event
          <select name="event_id" required <?= $events === [] ? 'disabled' : '' ?> class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white">
            <?php foreach ($events as $event): ?>
              <option value="<?= (int) $event['id'] ?>" <?= ($selectedEvent && (int) $selectedEvent['id'] === (int) $event['id']) ? 'selected' : '' ?>><?= e($event['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="block text-sm text-slate-300">
          Full name
          <input name="full_name" required class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>

        <label class="block text-sm text-slate-300">
          Batch / passing year
          <input name="batch" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>

        <label class="block text-sm text-slate-300">
          Department
          <input name="department" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>

        <label class="block text-sm text-slate-300">
          Contribution amount (INR)
          <input type="number" min="1" step="0.01" name="amount" required class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>

        <label class="block text-sm text-slate-300">
          Email (optional)
          <input type="email" name="email" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white" />
        </label>

        <label class="block text-sm text-slate-300 md:col-span-2">
          Appreciation message
          <textarea name="message" rows="4" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white"></textarea>
        </label>
      </div>

      <section class="mt-7 rounded-2xl border border-white/10 bg-slate-900/60 p-4 sm:p-6" aria-labelledby="photo-heading">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 id="photo-heading" class="text-lg font-semibold text-white">Alumni photo</h2>
            <p class="mt-1 text-sm text-slate-400">Choose a photo from your gallery or use your camera. JPG, PNG, or WebP, up to 2MB.</p>
          </div>
          <span class="rounded-full border border-gold/30 bg-gold/10 px-3 py-1 text-xs text-gold">Photo required</span>
        </div>
        <div class="mt-4 overflow-hidden rounded-xl border border-white/10 bg-black">
          <video id="photo-camera-video" class="hidden aspect-video max-h-[28rem] w-full object-cover" autoplay playsinline muted aria-label="Live camera preview"></video>
          <img id="alumni-photo-preview" class="hidden aspect-video max-h-[28rem] w-full object-contain" alt="Selected alumni photo preview">
          <div id="photo-placeholder" class="flex aspect-video items-center justify-center px-6 text-center text-sm text-slate-400">Your photo preview will appear here.</div>
        </div>
        <p id="photo-message" class="mt-3 min-h-6 text-sm text-slate-300" role="status" aria-live="polite">Your photo is submitted privately with your contribution.</p>
        <div class="mt-4 flex flex-wrap gap-3">
          <button id="photo-gallery-select" type="button" class="secondary-button">
            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="3"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-5-5L5 21"></path></svg>
            Choose from Gallery
          </button>
          <button id="photo-camera-start" type="button" class="primary-button">
            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 5h-4l-2 3H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-3l-2-3Z"></path><circle cx="12" cy="14" r="3"></circle></svg>
            Take a Photo
          </button>
          <button id="photo-camera-fallback" type="button" class="secondary-button hidden">Use device camera instead</button>
          <button id="photo-camera-switch" type="button" class="secondary-button hidden">Switch Camera</button>
          <button id="photo-camera-capture" type="button" class="primary-button hidden">Capture Photo</button>
          <button id="photo-camera-retake" type="button" class="secondary-button hidden">Retake Photo</button>
          <button id="photo-camera-use" type="button" class="primary-button hidden">Use This Photo</button>
          <button id="photo-camera-cancel" type="button" class="secondary-button hidden">Cancel Camera</button>
          <button id="photo-change" type="button" class="secondary-button hidden">Change Photo</button>
          <button id="photo-remove" type="button" class="secondary-button hidden">Remove Photo</button>
        </div>
      </section>

      <fieldset class="mt-8 rounded-2xl border border-white/10 p-4 sm:p-5">
        <legend class="px-2 font-semibold text-white">Optional public contribution card</legend>
        <p class="mb-4 text-sm text-slate-400">Your contribution stays private unless you explicitly opt in. Payments must be verified before anything is shown.</p>
        <label class="flex items-start gap-3 text-sm text-slate-200">
          <input type="checkbox" name="public_consent" value="1" class="mt-1 h-4 w-4 rounded border-white/10 bg-slate-900">
          <span>I explicitly consent to my selected details being shown on the public NAVRANG contribution wall after payment verification.</span>
        </label>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <label class="flex items-center gap-3 text-sm text-slate-300"><input type="checkbox" name="is_anonymous" value="1" class="h-4 w-4 rounded border-white/10 bg-slate-900"> Show me as an anonymous supporter</label>
          <label class="flex items-center gap-3 text-sm text-slate-300"><input type="checkbox" name="display_name" value="1" class="h-4 w-4 rounded border-white/10 bg-slate-900"> Display my name</label>
          <label class="flex items-center gap-3 text-sm text-slate-300"><input type="checkbox" name="display_photo" value="1" class="h-4 w-4 rounded border-white/10 bg-slate-900"> Display my captured photo</label>
          <label class="flex items-center gap-3 text-sm text-slate-300"><input type="checkbox" name="display_batch" value="1" class="h-4 w-4 rounded border-white/10 bg-slate-900"> Display my department and batch</label>
          <label class="flex items-center gap-3 text-sm text-slate-300"><input type="checkbox" name="display_amount" value="1" class="h-4 w-4 rounded border-white/10 bg-slate-900"> Display my contribution amount</label>
          <label class="flex items-center gap-3 text-sm text-slate-300 sm:col-span-2"><input type="checkbox" name="display_message" value="1" class="h-4 w-4 rounded border-white/10 bg-slate-900"> Display my dedication message</label>
        </div>
      </fieldset>

      <button id="contribution-submit" type="submit" <?= $events === [] ? 'disabled' : '' ?> class="primary-button mt-8 disabled:cursor-not-allowed disabled:opacity-50"><?= $events === [] ? 'Contributions are closed' : 'Continue to payment' ?></button>
    </form>

    <aside class="space-y-6">
      

      <div class="glass-panel rounded-[2rem] p-6">
        <h3 class="text-xl font-bold text-white">Before submitting</h3>
        <ul class="mt-4 space-y-3 text-sm text-slate-300">
          <li>• Never share UPI PIN, OTP, or banking passwords.</li>
          <li>• Keep the payment screenshot or proof ready for admin review.</li>
          <li>• Contributions appear publicly only after payment verification and with your consent.</li>
        </ul>
      </div>
    </aside>
  </section>
</main>

<script>
  (() => {
    const form = document.getElementById('contribution-form');
    const uploadInput = document.getElementById('alumni-photo-upload');
    const video = document.getElementById('photo-camera-video');
    const preview = document.getElementById('alumni-photo-preview');
    const placeholder = document.getElementById('photo-placeholder');
    const message = document.getElementById('photo-message');
    const galleryButton = document.getElementById('photo-gallery-select');
    const startButton = document.getElementById('photo-camera-start');
    const fallbackButton = document.getElementById('photo-camera-fallback');
    const switchButton = document.getElementById('photo-camera-switch');
    const captureButton = document.getElementById('photo-camera-capture');
    const retakeButton = document.getElementById('photo-camera-retake');
    const useButton = document.getElementById('photo-camera-use');
    const cancelButton = document.getElementById('photo-camera-cancel');
    const changeButton = document.getElementById('photo-change');
    const removeButton = document.getElementById('photo-remove');
    const submitButton = document.getElementById('contribution-submit');
    let stream = null;
    let capturedBlob = null;
    let selectedSource = '';
    let previewUrl = '';
    let facingMode = 'environment';
    let selectedCameraId = '';
    let cameras = [];
    let cameraIndex = 0;

    if (!form || !uploadInput || !video || !preview || !galleryButton || !startButton) return;

    const setMessage = (text, isError = false) => {
      message.textContent = text;
      message.classList.toggle('text-red-300', isError);
      message.classList.toggle('text-slate-300', !isError);
    };

    const stopStream = () => {
      if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
      }
      video.srcObject = null;
    };

    const selectedFile = () => uploadInput.files?.[0] || null;

    const clearPreviewUrl = () => {
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = '';
      preview.removeAttribute('src');
    };

    const renderSelectedPhoto = () => {
      const file = selectedFile();
      video.classList.add('hidden');
      if (!file) {
        clearPreviewUrl();
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        changeButton.classList.add('hidden');
        removeButton.classList.add('hidden');
        retakeButton.classList.add('hidden');
        return;
      }

      clearPreviewUrl();
      previewUrl = URL.createObjectURL(file);
      preview.src = previewUrl;
      preview.classList.remove('hidden');
      placeholder.classList.add('hidden');
      changeButton.classList.remove('hidden');
      removeButton.classList.remove('hidden');
      retakeButton.classList.toggle('hidden', selectedSource !== 'camera');
    };

    const showCamera = (active, showFallback = false) => {
      galleryButton.classList.toggle('hidden', active);
      startButton.classList.toggle('hidden', active);
      fallbackButton.classList.toggle('hidden', active || !showFallback);
      switchButton.classList.toggle('hidden', !active || cameras.length < 2);
      captureButton.classList.toggle('hidden', !active || Boolean(capturedBlob));
      cancelButton.classList.toggle('hidden', !active);
      changeButton.classList.toggle('hidden', active || !selectedFile());
      removeButton.classList.toggle('hidden', active || !selectedFile());
      retakeButton.classList.toggle('hidden', active ? !capturedBlob : selectedSource !== 'camera' || !selectedFile());
      useButton.classList.toggle('hidden', !active || !capturedBlob);
    };

    const startCamera = async () => {
      if (!window.isSecureContext) {
        setMessage('Camera access requires HTTPS or localhost.', true);
        showCamera(false, true);
        return;
      }
      if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
        setMessage('This browser does not support live camera access. Choose from your gallery or use the device camera option.', true);
        showCamera(false, true);
        return;
      }

      stopStream();
      capturedBlob = null;
      cameras = [];
      showCamera(true);
      video.classList.remove('hidden');
      preview.classList.add('hidden');
      placeholder.classList.add('hidden');
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: {
            ...(selectedCameraId ? { deviceId: { exact: selectedCameraId } } : { facingMode: { ideal: facingMode } }),
            width: { ideal: 1280 },
            height: { ideal: 960 }
          }
        });
        video.srcObject = stream;
        video.classList.remove('hidden');
        preview.classList.add('hidden');
        placeholder.classList.add('hidden');
        await video.play();
        showCamera(true);
        cameras = await navigator.mediaDevices.enumerateDevices()
          .then((devices) => devices.filter((device) => device.kind === 'videoinput'))
          .catch(() => []);
        cameraIndex = cameras.findIndex((camera) => camera.deviceId === stream.getVideoTracks()[0]?.getSettings().deviceId);
        if (cameraIndex < 0) cameraIndex = 0;
        selectedCameraId = cameras[cameraIndex]?.deviceId || '';
        showCamera(true);
        setMessage('Camera is ready. Capture your photo, then confirm it before submitting.');
      } catch (error) {
        stopStream();
        cameras = [];
        showCamera(false);
        video.classList.add('hidden');
        renderSelectedPhoto();
        showCamera(false, true);
        if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
          setMessage('Camera permission was denied. Allow access in browser settings, or choose a photo from your gallery.', true);
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
          setMessage('No camera was found. Choose a photo from your gallery or use the device camera option.', true);
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
          setMessage('The camera is unavailable or being used by another application. You can choose a gallery photo instead.', true);
        } else {
          setMessage('Could not start the camera. Check browser permissions or choose a gallery photo.', true);
        }
      }
    };

    startButton.addEventListener('click', startCamera);
    galleryButton.addEventListener('click', () => {
      uploadInput.removeAttribute('capture');
      uploadInput.click();
    });
    fallbackButton.addEventListener('click', () => {
      uploadInput.setAttribute('capture', 'environment');
      uploadInput.click();
    });
    uploadInput.addEventListener('change', () => {
      const file = selectedFile();
      if (!file) return;
      const photoWasCaptured = uploadInput.hasAttribute('capture');
      uploadInput.removeAttribute('capture');
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size <= 0 || file.size > 2 * 1024 * 1024) {
        uploadInput.value = '';
        selectedSource = '';
        renderSelectedPhoto();
        setMessage('Choose a JPG, PNG, or WebP image no larger than 2MB.', true);
        return;
      }

      stopStream();
      capturedBlob = null;
      selectedSource = photoWasCaptured ? 'camera' : 'gallery';
      showCamera(false);
      renderSelectedPhoto();
      setMessage('Photo selected and ready to submit.');
    });
    switchButton.addEventListener('click', async () => {
      if (cameras.length < 2) return;
      cameraIndex = (cameraIndex + 1) % cameras.length;
      const label = cameras[cameraIndex].label.toLowerCase();
      facingMode = label.includes('front') || label.includes('user') ? 'user' : 'environment';
      selectedCameraId = cameras[cameraIndex].deviceId;
      await startCamera();
    });
    captureButton.addEventListener('click', () => {
      if (!stream || !video.videoWidth || !video.videoHeight) {
        setMessage('Wait for the live camera preview before capturing.', true);
        return;
      }
      const maxDimension = 1600;
      const scale = Math.min(1, maxDimension / Math.max(video.videoWidth, video.videoHeight));
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(video.videoWidth * scale);
      canvas.height = Math.round(video.videoHeight * scale);
      const context = canvas.getContext('2d', { alpha: false });
      if (!context) {
        setMessage('The camera photo could not be processed. Please try again.', true);
        return;
      }
      context.drawImage(video, 0, 0, canvas.width, canvas.height);
      canvas.toBlob((blob) => {
        if (!blob) {
          setMessage('The photo could not be captured. Please try again.', true);
          return;
        }
        capturedBlob = blob;
        clearPreviewUrl();
        previewUrl = URL.createObjectURL(blob);
        preview.src = previewUrl;
        video.classList.add('hidden');
        preview.classList.remove('hidden');
        showCamera(true);
        setMessage('Check your photo. Retake it or confirm to attach it to your contribution.');
      }, 'image/jpeg', 0.88);
    });
    retakeButton.addEventListener('click', async () => {
      if (capturedBlob && stream) {
        capturedBlob = null;
        clearPreviewUrl();
        preview.classList.add('hidden');
        video.classList.remove('hidden');
        showCamera(true);
        setMessage('Camera is ready. Capture another photo when you are ready.');
      } else {
        await startCamera();
      }
    });
    useButton.addEventListener('click', () => {
      if (!capturedBlob) {
        setMessage('Capture a photo before confirming it.', true);
        return;
      }
      try {
        const transfer = new DataTransfer();
        transfer.items.add(new File([capturedBlob], 'alumni-photo.jpg', { type: 'image/jpeg' }));
        uploadInput.files = transfer.files;
      } catch (error) {
        setMessage('This browser cannot attach the captured photo. Choose a photo from your gallery instead.', true);
        return;
      }

      selectedSource = 'camera';
      capturedBlob = null;
      stopStream();
      showCamera(false);
      renderSelectedPhoto();
      setMessage('Photo confirmed and ready to submit.');
    });
    cancelButton.addEventListener('click', () => {
      stopStream();
      capturedBlob = null;
      renderSelectedPhoto();
      showCamera(false);
      setMessage(selectedFile() ? 'Camera closed. Your previously selected photo is unchanged.' : 'Camera stopped. Choose a gallery photo or start the camera when ready.');
    });
    changeButton.addEventListener('click', () => {
      uploadInput.removeAttribute('capture');
      uploadInput.click();
    });
    removeButton.addEventListener('click', () => {
      stopStream();
      uploadInput.value = '';
      selectedSource = '';
      capturedBlob = null;
      showCamera(false);
      renderSelectedPhoto();
      setMessage('Photo removed. Choose a gallery photo or take a new photo.');
    });
    form.addEventListener('submit', (event) => {
      const file = selectedFile();
      if (!file) {
        event.preventDefault();
        setMessage('Choose a photo or capture and confirm one before continuing.', true);
        galleryButton.focus();
        return;
      }
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size <= 0 || file.size > 2 * 1024 * 1024) {
        event.preventDefault();
        setMessage('Choose a JPG, PNG, or WebP image no larger than 2MB.', true);
        return;
      }
      if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
      }
      form.dataset.submitting = 'true';
      submitButton.disabled = true;
      submitButton.textContent = 'Submitting...';
    });
    window.addEventListener('pagehide', () => {
      stopStream();
      clearPreviewUrl();
    }, { once: true });
  })();
</script>
