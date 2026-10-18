<?php

declare(strict_types=1);

class BaseModel
{
    protected static function db(): ?PDO
    {
        return requireDatabase();
    }
}
