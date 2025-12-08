<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Tenant\TenantContext;

class StoreDashboardController extends Controller
{
    public function index(): void
    {
        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/store/dashboard-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Dashboard'
        ]);
    }
}

