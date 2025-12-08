<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class MigrationRunner
{
    private string $migrationsPath;

    public function __construct()
    {
        $this->migrationsPath = __DIR__ . '/../../database/migrations';
    }

    public function runPending(): array
    {
        $db = Database::getConnection();
        $this->ensureMigrationsTable($db);

        $applied = $this->getAppliedMigrations($db);
        $files = $this->getMigrationFiles();

        $pending = array_diff($files, $applied);
        $results = [];

        foreach ($pending as $migration) {
            try {
                $this->runMigration($db, $migration);
                $results[] = ['migration' => $migration, 'status' => 'success'];
            } catch (\Exception $e) {
                $results[] = ['migration' => $migration, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function getPendingMigrations(): array
    {
        $db = Database::getConnection();
        $this->ensureMigrationsTable($db);

        $applied = $this->getAppliedMigrations($db);
        $files = $this->getMigrationFiles();

        return array_values(array_diff($files, $applied));
    }

    public function getAppliedMigrationsList(): array
    {
        $db = Database::getConnection();
        $this->ensureMigrationsTable($db);
        return $this->getAppliedMigrations($db);
    }

    private function ensureMigrationsTable(PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function getAppliedMigrations(PDO $db): array
    {
        $stmt = $db->query("SELECT migration FROM migrations ORDER BY migration");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $migrations[] = basename($file, '.php');
            }
        }

        sort($migrations);
        return $migrations;
    }

    private function runMigration(PDO $db, string $migration): void
    {
        $file = $this->migrationsPath . '/' . $migration . '.php';
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Arquivo de migration não encontrado: {$file}");
        }

        // MySQL não permite DDL (CREATE TABLE, ALTER TABLE) dentro de transações
        // em algumas versões/configurações, então executamos sem transação
        // As migrations devem ser idempotentes (usar IF NOT EXISTS, etc.)
        try {
            require $file;
            
            $stmt = $db->prepare("INSERT INTO migrations (migration, applied_at) VALUES (:migration, NOW())");
            $stmt->execute(['migration' => $migration]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}

