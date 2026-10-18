<?php

declare(strict_types=1);

require_once APP_PATH . '/Models/BaseModel.php';

class ContributionModel extends BaseModel
{
    public static function getPublicWall(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [];
        }

        $statement = $pdo->query('SELECT c.id, CASE WHEN c.is_anonymous = 1 OR c.display_name = 0 THEN "Anonymous Supporter" ELSE COALESCE(NULLIF(TRIM(c.contributor_name), ""), "Name Not Available") END AS full_name, CASE WHEN c.display_batch = 1 THEN c.batch ELSE NULL END AS batch, CASE WHEN c.display_batch = 1 THEN c.department ELSE NULL END AS department, CASE WHEN c.display_photo = 1 THEN c.photo_url ELSE NULL END AS photo_url, CASE WHEN c.display_amount = 1 THEN c.amount ELSE NULL END AS amount, CASE WHEN c.display_message = 1 THEN c.message ELSE NULL END AS message, e.title AS event_title FROM contributions c INNER JOIN events e ON e.id = c.event_id WHERE c.status = "verified" AND c.is_public = 1 AND c.consent_status = "explicit" AND e.status IN ("published", "archived") ORDER BY c.verified_at DESC, c.id DESC LIMIT 12');
        return $statement->fetchAll();
    }

    public static function getVerifiedStats(int $eventId): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [
                'total_amount' => '0.00',
                'contributors' => 0,
                'batches' => 0,
                'goal_amount' => '0.00',
            ];
        }

        $query = 'SELECT SUM(amount) AS total_amount, COUNT(DISTINCT contributor_name) AS contributors, COUNT(DISTINCT batch) AS batches FROM contributions WHERE event_id = :event_id AND status = "verified"';
        $statement = $pdo->prepare($query);
        $statement->execute([':event_id' => $eventId]);
        $result = $statement->fetch();

        $goal = $pdo->prepare('SELECT goal_amount FROM events WHERE id = :id LIMIT 1');
        $goal->execute([':id' => $eventId]);
        $goalAmount = $goal->fetchColumn() ?: '0.00';

        return [
            'total_amount' => (string) ($result['total_amount'] ?? '0.00'),
            'contributors' => (int) ($result['contributors'] ?? 0),
            'batches' => (int) ($result['batches'] ?? 0),
            'goal_amount' => (string) $goalAmount,
        ];
    }

    public static function findByReference(string $reference): ?array
    {
        $pdo = self::db();
        if (!$pdo || $reference === '') {
            return null;
        }

        $statement = $pdo->prepare('SELECT * FROM contributions WHERE transaction_ref = :reference LIMIT 1');
        $statement->execute([':reference' => $reference]);
        $record = $statement->fetch();
        return $record ?: null;
    }

    public static function createPending(array $data): ?string
    {
        $pdo = self::db();
        if (!$pdo) {
            return null;
        }

        $eventId = (int) ($data['event_id'] ?? 0);
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $amountInput = $data['amount'] ?? null;
        $amount = is_numeric($amountInput) ? (float) $amountInput : 0.0;
        $photoPath = trim((string) ($data['photo_url'] ?? ''));
        if ($eventId <= 0 || $fullName === '' || strlen($fullName) > 191 || !is_finite($amount) || $amount <= 0 || $amount > 9999999999.99 || $photoPath === '') {
            return null;
        }

        $event = $pdo->prepare('SELECT id FROM events WHERE id = :event_id AND status = "published" AND is_contribution_open = 1 LIMIT 1');
        $event->execute([':event_id' => $eventId]);
        if (!$event->fetchColumn()) {
            return null;
        }

        $reference = generateContributionReference();
        $isPublic = !empty($data['is_public']);
        $isAnonymous = !empty($data['is_anonymous']);
        $displayName = $isPublic && !$isAnonymous && !empty($data['display_name']) ? 1 : 0;
        $displayPhoto = $isPublic && !empty($data['display_photo']) ? 1 : 0;
        $displayBatch = $isPublic && !empty($data['display_batch']) ? 1 : 0;
        $displayAmount = $isPublic && !empty($data['display_amount']) ? 1 : 0;
        $displayMessage = $isPublic && !empty($data['display_message']) ? 1 : 0;
        $statement = $pdo->prepare('INSERT INTO contributions (event_id, contributor_name, batch, department, amount, transaction_ref, email, photo_url, message, is_public, is_anonymous, display_name, display_photo, display_batch, display_amount, display_message, consent_status, status, created_at) VALUES (:event_id, :contributor_name, :batch, :department, :amount, :transaction_ref, :email, :photo_url, :message, :is_public, :is_anonymous, :display_name, :display_photo, :display_batch, :display_amount, :display_message, :consent_status, "pending_payment", NOW())');
        $statement->execute([
            ':event_id' => $eventId,
            ':contributor_name' => $fullName,
            ':batch' => trim((string) ($data['batch'] ?? '')),
            ':department' => trim((string) ($data['department'] ?? '')),
            ':amount' => number_format($amount, 2, '.', ''),
            ':transaction_ref' => $reference,
            ':email' => trim((string) ($data['email'] ?? '')),
            ':photo_url' => $photoPath,
            ':message' => trim((string) ($data['message'] ?? '')),
            ':is_public' => $isPublic ? 1 : 0,
            ':is_anonymous' => $isAnonymous ? 1 : 0,
            ':display_name' => $displayName,
            ':display_photo' => $displayPhoto,
            ':display_batch' => $displayBatch,
            ':display_amount' => $displayAmount,
            ':display_message' => $displayMessage,
            ':consent_status' => $isPublic ? 'explicit' : 'none',
        ]);

        return $reference;
    }

    public static function getAdminList(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [];
        }

        $statement = $pdo->query('SELECT c.*, e.title AS event_title FROM contributions c LEFT JOIN events e ON e.id = c.event_id ORDER BY c.created_at DESC LIMIT 200');
        return $statement->fetchAll();
    }

    public static function updateStatus(int $id, string $status, ?int $adminId = null, ?string $note = null): bool
    {
        $allowed = ['pending_payment', 'pending_verification', 'pending', 'approved', 'verified', 'rejected', 'refunded', 'duplicate'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $pdo = self::db();
        if (!$pdo) {
            return false;
        }

        if ($status === 'verified') {
            $paymentReference = $pdo->prepare('SELECT payment_reference FROM contributions WHERE id = :id LIMIT 1');
            $paymentReference->execute([':id' => $id]);
            $reference = $paymentReference->fetchColumn();
            if (!is_string($reference) || trim($reference) === '') {
                return false;
            }
        }

        $statement = $pdo->prepare('UPDATE contributions SET status = :status, verification_notes = :note, verified_by = :verified_by, verified_at = :verified_at, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            ':status' => $status,
            ':note' => $note ?? '',
            ':verified_by' => $adminId,
            ':verified_at' => $status === 'verified' || $status === 'approved' || $status === 'rejected' ? date('Y-m-d H:i:s') : null,
            ':id' => $id,
        ]);
        return $statement->rowCount() > 0;
    }
}
