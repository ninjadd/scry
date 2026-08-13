<?php

namespace Scry\Inspectors;

class MariadbInspector extends MysqlInspector
{
    /**
     * Get MariaDB server stats.
     */
    public function getServerStats(): array
    {
        $stats = parent::getServerStats();
        $stats['driver'] = 'mariadb';

        return $stats;
    }
}
