<?php

declare(strict_types=1);

require_once APP_PATH . '/Models/BaseModel.php';

class SettingsModel extends BaseModel
{
    public static function getAll(): array
    {
        $pdo = self::db();
        if (!$pdo) {
            return [
                'site_name' => 'Navrang Events',
                'homepage_title' => 'NAVRANG 1.0 – Dandiya Night',
                'homepage_description' => 'Nine Colors. One Campus. One Celebration.',
            ];
        }

        $statement = $pdo->query('SELECT setting_key, setting_value FROM site_settings');
        $settings = [];
        foreach ($statement->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
