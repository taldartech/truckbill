<?php

namespace App\Pulse;

use Laravel\Pulse\Storage\DatabaseStorage;

class PulseDatabaseStorage extends DatabaseStorage
{
    protected function requiresManualKeyHash(): bool
    {
        return in_array($this->connection()->getDriverName(), ['sqlite', 'mysql', 'mariadb'], true);
    }
}
