<?php

declare(strict_types=1);

/**
 * Gestionnaire de migrations — Hôtel Le Lézard Bleu & Spa
 *
 * Usage CLI :
 *   php migrations/MigrationRunner.php run
 *   php migrations/MigrationRunner.php status
 *   php migrations/MigrationRunner.php rollback 001
 *
 * Les migrations sont des fichiers SQL numérotés : 001_xxx.sql, 002_xxx.sql …
 * Elles sont exécutées dans l'ordre numérique et leur état est tracé
 * dans la table `migrations`.
 */

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

class MigrationRunner
{
    private \PDO $pdo;
    private string $migrationsDir;

    public function __construct(\PDO $pdo, string $migrationsDir)
    {
        $this->pdo           = $pdo;
        $this->migrationsDir = $migrationsDir;
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
              `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `migration`   VARCHAR(255) NOT NULL UNIQUE,
              `executed_at` DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),
              `checksum`    VARCHAR(64)  NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function getPending(): array
    {
        $all = glob($this->migrationsDir . '/[0-9]*.sql');
        if ($all === false) return [];
        sort($all);

        $executed = $this->getExecuted();
        $pending  = [];
        foreach ($all as $file) {
            $name = basename($file, '.sql');
            if (!in_array($name, $executed, true)) {
                $pending[] = $file;
            }
        }
        return $pending;
    }

    public function getExecuted(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id ASC");
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
    }

    public function run(): void
    {
        $pending = $this->getPending();
        if (empty($pending)) {
            echo "✓ Aucune migration en attente.\n";
            return;
        }

        foreach ($pending as $file) {
            $name = basename($file, '.sql');
            echo "→ Exécution : {$name} ... ";

            $sql      = file_get_contents($file);
            $checksum = hash('sha256', $sql);

            try {
                // Exécuter chaque instruction séparément
                // PDO::exec ne supporte pas les instructions multiples en toute sécurité
                // On sépare sur les ';' de fin de ligne en tenant compte des DELIMITER
                $this->executeSqlFile($sql);

                $stmt = $this->pdo->prepare(
                    "INSERT INTO migrations (migration, checksum) VALUES (:m, :c)"
                );
                $stmt->execute([':m' => $name, ':c' => $checksum]);

                echo "✓ OK\n";
            } catch (\Exception $e) {
                echo "✗ ERREUR : " . $e->getMessage() . "\n";
                exit(1);
            }
        }
        echo "✓ Toutes les migrations ont été exécutées.\n";
    }

    private function executeSqlFile(string $sql): void
    {
        // Supprimer les commentaires SQL simples
        $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
        // Supprimer les commentaires multilignes
        $sql = preg_replace('#/\*.*?\*/#s', '', $sql ?? '');

        // Gérer les DELIMITER pour les triggers
        $statements = [];
        $delimiter  = ';';
        $buffer     = '';

        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            if (str_starts_with(strtoupper($trimmed), 'DELIMITER')) {
                $parts     = preg_split('/\s+/', $trimmed);
                $delimiter = $parts[1] ?? ';';
                continue;
            }
            $buffer .= $line . "\n";
            if ($delimiter !== ';' && str_ends_with(rtrim($trimmed), $delimiter)) {
                // Remplacer le delimiter personnalisé par ;
                $statements[] = rtrim(substr(rtrim($buffer), 0, -strlen($delimiter)));
                $buffer       = '';
            } elseif ($delimiter === ';' && str_ends_with(rtrim($trimmed), ';')) {
                $statements[] = rtrim($buffer);
                $buffer       = '';
            }
        }

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') continue;
            $this->pdo->exec($statement);
        }
    }

    public function status(): void
    {
        $executed = $this->getExecuted();
        $pending  = $this->getPending();
        $all      = glob($this->migrationsDir . '/[0-9]*.sql') ?: [];
        sort($all);

        echo str_pad('Migration', 50) . "  Statut\n";
        echo str_repeat('-', 62) . "\n";

        foreach ($all as $file) {
            $name = basename($file, '.sql');
            $status = in_array($name, $executed, true) ? '✓ exécutée' : '⏳ en attente';
            echo str_pad($name, 50) . "  {$status}\n";
        }

        echo str_repeat('-', 62) . "\n";
        echo count($executed) . " exécutée(s) / " . count($pending) . " en attente\n";
    }
}

// ── Point d'entrée CLI ────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    exit('Ce script ne peut être exécuté qu\'en ligne de commande.');
}

$pdo = \Config\Database::getInstance();
if ($pdo === null) {
    // En cas d'absence de la DB, exécuter le CREATE DATABASE du SQL d'abord
    echo "Base de données inaccessible. Vérifiez les paramètres dans .env\n";
    exit(1);
}

$runner = new MigrationRunner($pdo, __DIR__);
$command = $argv[1] ?? 'run';

match ($command) {
    'run'    => $runner->run(),
    'status' => $runner->status(),
    default  => print("Commandes disponibles : run | status\n"),
};
