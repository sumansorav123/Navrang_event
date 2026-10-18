<?php

declare(strict_types=1);

require_once APP_PATH . '/Models/BaseModel.php';

class AlumniModel extends BaseModel
{
    public static function getPublicProfiles(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [];
        }

        $statement = $pdo->query('SELECT * FROM alumni WHERE is_visible = 1 ORDER BY created_at DESC LIMIT 12');
        return $statement->fetchAll() ?: [];
    }
}
