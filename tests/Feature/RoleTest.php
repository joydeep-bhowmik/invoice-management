<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (config('permissions') as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]);
    }
});

describe('role permission management', function () {

    it('creates a role with permissions', function () {
        Livewire::test('role.save')
            ->set('name', 'admin')
            ->set('selected_permissions', ['manage_products', 'manage_invoices'])
            ->call('save');

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
        ]);

        $role = Role::where('name', 'admin')->firstOrFail();

        expect($role->hasPermissionTo('manage_products'))->toBeTrue();
        expect($role->hasPermissionTo('manage_invoices'))->toBeTrue();
    });

    it('updates role permissions when editing', function () {
        $role = Role::create(['name' => 'editor']);

        $role->syncPermissions(['manage_products']);

        Livewire::test('role.save', ['id' => $role->id])
            ->set('name', 'editor')
            ->set('selected_permissions', ['manage_products'])
            ->call('save');
        $role->refresh();
        expect($role->hasPermissionTo('manage_products'))->toBeTrue();
        expect($role->hasPermissionTo('manage_invoices'))->toBeFalse();

    });

});
