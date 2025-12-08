<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\MigrationRunner;

class SystemUpdatesController extends Controller
{
    public function index(): void
    {
        $db = Database::getConnection();
        
        // Buscar versão atual
        $stmt = $db->query("SELECT version FROM system_versions ORDER BY applied_at DESC LIMIT 1");
        $currentVersion = $stmt->fetchColumn() ?: '0.1.0';

        // Buscar migrations pendentes
        $runner = new MigrationRunner();
        $pending = $runner->getPendingMigrations();

        $this->view('admin/system/updates', [
            'currentVersion' => $currentVersion,
            'pendingMigrations' => $pending
        ]);
    }

    public function runMigrations(): void
    {
        $runner = new MigrationRunner();
        $results = $runner->runPending();

        $this->view('admin/system/updates_result', ['results' => $results]);
    }
}



