<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\DB;
use PDO;

class AoSector
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::getPDO();
    }

    public function all(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ao_sectors ORDER BY id ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
