<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * Repository de base — Hôtel Le Lézard Bleu & Spa
 * Fournit les méthodes CRUD sécurisées communes à tous les repositories.
 * Utilise exclusivement PDO + requêtes préparées.
 */
abstract class BaseRepository
{
    protected PDO    $pdo;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── Find ─────────────────────────────────────────────────────────

    public function findById(int|string $id): ?array
    {
        $sql  = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function findAll(int $limit = 100, int $offset = 0): array
    {
        // limit et offset sont des int — pas de paramètre lié nécessaire
        $sql  = "SELECT * FROM `{$this->table}` LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Trouver par une colonne (whitelist obligatoire dans les sous-classes). */
    protected function findBy(string $column, mixed $value, int $limit = 1): array
    {
        $this->assertColumnAllowed($column);
        $sql  = "SELECT * FROM `{$this->table}` WHERE `{$column}` = :value LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':value' => $value]);
        return $stmt->fetchAll();
    }

    protected function findOneBy(string $column, mixed $value): ?array
    {
        $rows = $this->findBy($column, $value, 1);
        return $rows[0] ?? null;
    }

    // ── Count ────────────────────────────────────────────────────────

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->table}`");
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }

    // ── Insert ───────────────────────────────────────────────────────

    protected function insert(array $data): int|string
    {
        $columns = array_keys($data);
        foreach ($columns as $col) {
            $this->assertColumnAllowed($col);
        }

        $cols   = implode('`, `', $columns);
        $params = implode(', ', array_map(fn($c) => ":{$c}", $columns));
        $sql    = "INSERT INTO `{$this->table}` (`{$cols}`) VALUES ({$params})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->prefixKeys($data));
        return $this->pdo->lastInsertId();
    }

    // ── Update ───────────────────────────────────────────────────────

    protected function update(int|string $id, array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        $columns = array_keys($data);
        foreach ($columns as $col) {
            $this->assertColumnAllowed($col);
        }

        $sets = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", $columns));
        $sql  = "UPDATE `{$this->table}` SET {$sets} WHERE `{$this->primaryKey}` = :__pk";

        $params           = $this->prefixKeys($data);
        $params[':__pk']  = $id;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // ── Delete (soft delete préféré dans les repositories enfants) ────

    protected function hardDelete(int|string $id): int
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    // ── Transactions ─────────────────────────────────────────────────

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    // ── Utilitaires ──────────────────────────────────────────────────

    private function prefixKeys(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[':' . $key] = $value;
        }
        return $result;
    }

    /**
     * Whitelist de colonnes autorisées pour les requêtes dynamiques.
     * Chaque repository enfant doit surcharger cette méthode.
     */
    protected function getAllowedColumns(): array
    {
        return [];
    }

    protected function assertColumnAllowed(string $column): void
    {
        $allowed = $this->getAllowedColumns();
        if (!empty($allowed) && !in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Colonne non autorisée : '{$column}' dans la table '{$this->table}'"
            );
        }
    }
}
