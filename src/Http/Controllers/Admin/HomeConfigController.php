<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Tenant\TenantContext;

class HomeConfigController extends Controller
{
    public function index(): void
    {
        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/home/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Home da Loja'
        ]);
    }
}


