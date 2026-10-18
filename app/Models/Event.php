<?php

declare(strict_types=1);

require_once APP_PATH . '/Models/BaseModel.php';

class EventModel extends BaseModel
{
    public static function getFeatured(): ?array
    {
        $pdo = self::db();
        if (!$pdo) {
            return null;
        }

        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        $statement = $pdo->prepare('SELECT * FROM events WHERE status = "published" AND (event_date > :today OR (event_date = :event_today AND start_time >= :current_time)) ORDER BY event_date ASC, start_time ASC LIMIT 1');
        $statement->execute([
            ':today' => $today,
            ':event_today' => $today,
            ':current_time' => $currentTime,
        ]);
        $event = $statement->fetch();

        if (!$event) {
            $statement = $pdo->query('SELECT * FROM events WHERE status = "published" ORDER BY event_date DESC, created_at DESC LIMIT 1');
            $event = $statement->fetch();
        }

        if (!$event) {
            $statement = $pdo->query('SELECT * FROM events WHERE status = "archived" ORDER BY event_date DESC, created_at DESC LIMIT 1');
            $event = $statement->fetch();
        }

        return $event ?: null;
    }

    public static function getAll(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [];
        }

        $statement = $pdo->query('SELECT * FROM events ORDER BY event_date DESC, created_at DESC');
        return $statement->fetchAll();
    }

    public static function getActive(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [];
        }

        $statement = $pdo->query('SELECT * FROM events WHERE status IN ("published", "archived") ORDER BY event_date DESC, created_at DESC');
        return $statement->fetchAll();
    }

    public static function getContributionOpen(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [];
        }

        $statement = $pdo->query('SELECT * FROM events WHERE status = "published" AND is_contribution_open = 1 ORDER BY event_date ASC, start_time ASC');
        return $statement->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::db();
        if (!$pdo) {
            return null;
        }

        $statement = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        return $statement->fetch() ?: null;
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $allowed = ['draft', 'published', 'archived', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $pdo = self::db();
        if (!$pdo) {
            return false;
        }

        $statement = $pdo->prepare('UPDATE events SET status = :status, updated_at = NOW() WHERE id = :id');
        $statement->execute([':status' => $status, ':id' => $id]);
        return $statement->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $pdo = self::db();
        if (!$pdo) {
            return false;
        }

        $statement = $pdo->prepare('DELETE FROM events WHERE id = :id');
        $statement->execute([':id' => $id]);
        return $statement->rowCount() > 0;
    }

    public static function getEventBySlug(string $slug): ?array
    {
        $pdo = self::db();
        if (!$pdo) {
            return null;
        }

        $statement = $pdo->prepare('SELECT * FROM events WHERE slug = :slug LIMIT 1');
        $statement->execute([':slug' => $slug]);
        return $statement->fetch() ?: null;
    }

    public static function getArchive(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [];
        }

        $statement = $pdo->query('SELECT * FROM events WHERE status = "archived" ORDER BY event_date DESC');
        return $statement->fetchAll();
    }

    public static function getGallery(int $eventId): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [];
        }

        $statement = $pdo->prepare('SELECT * FROM event_gallery WHERE event_id = :event_id ORDER BY sort_order ASC, created_at DESC');
        $statement->execute([':event_id' => $eventId]);
        return $statement->fetchAll();
    }

    public static function getSiteSettings(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [
                'site_name' => 'Navrang Events',
                'tagline' => 'Nine Colors. One Campus. One Celebration.',
                'homepage_title' => 'NAVRANG 1.0 – Dandiya Night',
                'contact_email' => 'hello@navrang.events',
            ];
        }

        $statement = $pdo->query('SELECT * FROM site_settings');
        $settings = [];
        foreach ($statement->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

}
