<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Folio::path(resource_path('views/pages'))->middleware([
            '*' => [
                'auth',
                'company.required',
                'user.isactive',
            ],
            'admin/*' => 'company.owner',
            'products/*' => 'can:manage_products',
            'invoices/*' => 'can:manage_invoices',
            'warehouses/*' => 'can:manage_warehouses',
            'dev/*' => 'can:access_dev',
        ]);
    }
}
