<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Create test user
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'id' => 1,
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Create additional users
        $users = User::factory(5)->create();
        $allUsers = collect([$testUser])->merge($users);

        // Create warehouses with managers
        $warehouses = Warehouse::factory(5)->create([
            'manager_id' => $testUser->id,
        ]);

        // Create products in warehouses
        foreach ($warehouses as $warehouse) {
            Product::factory(80)->create([
                'warehouse_id' => $warehouse->id,
            ]);
        }

        // Create invoices with items
        Invoice::factory(200)
            ->has(
                \App\Models\InvoiceItem::factory()->count(3),
                'items'
            )
            ->create();

        $this->call(NoteSeeder::class);
        $this->call(PermissionSeeder::class);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());
        $testUser->assignRole($adminRole);
    }
}
