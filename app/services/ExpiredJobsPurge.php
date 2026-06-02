<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class ExpiredJobsPurge
{
    public static function run(PDO $pdo): int
    {
        $stmt = $pdo->prepare(
            "DELETE FROM jobs
             WHERE valid_through IS NOT NULL
             AND TRIM(valid_through) != ''
             AND valid_through < ?"
        );
        $stmt->execute([date('c')]);

        return $stmt->rowCount();
    }
}
