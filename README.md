# Navrang Events

A reusable multi-event management website built with PHP 8.2, Tailwind CSS, and MySQL.

## Local setup

1. Start XAMPP and ensure Apache and MySQL are running.
2. Open phpMyAdmin and create a database named `navrang_events`.
3. Import `database/schema.sql`.
4. Copy `.env.example` to `.env` and adjust the database settings.
5. Install frontend dependencies:
   ```bash
   npm install
   ```
6. Build Tailwind CSS:
   ```bash
   npm run build
   ```
7. Configure Apache to serve the `public/` directory as the web root (the sample XAMPP URL uses `/navrang/public`). Do not expose the project root as the web root; the `storage/` directory contains private photos and payment proof.
8. Create the first admin from the admin login page (`/admin`).

## Alumni contribution photos

The contribution form lets alumni choose a JPG, PNG, or WebP photo from their gallery or capture one with the live camera. Live camera permission is available on `http://localhost` during local XAMPP development; deployed sites must use HTTPS. If live camera access is unavailable, the form offers the device's camera/file-picker capture option as a fallback. Photos are submitted with the contribution, validated server-side (maximum 2MB), and stored in the private uploads directory. Authorized admin sessions can view private submission photos and optional payment-proof images; a photo is served publicly only after payment verification and explicit photo-display consent.

Public contribution cards use verified contribution records and only show the individual fields the alumnus opted to display. Event poster uploads are stored under `public/uploads`; administrators can also select a validated existing image from `public/assets/images`. Frontend event images use the stored path and fall back to the existing Navrang poster image if that file is missing. Existing database rows and uploaded files are not deleted by these features.

## Default admin

Do not commit a default password or hardcoded admin credential in source control. Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in your local `.env` before the first launch, and the application will create the initial admin user securely if no admin account exists yet.

## Deployment

Use a PHP-compatible host with MySQL, configure environment variables through the host, set the web root to `public/`, enable HTTPS, protect the `config` directory and private uploads, and run `npm run build` before deployment.
