<?php

use Flux\Flux;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component {
    public $id;
    public $name;
    public array $selected_permissions = [];

    function mount($id = null)
    {
        if (!$id) {
            return;
        }

        $role = Role::find($id);

        if ($id && !$role) {
            abort(404);
        }

        $this->id = $role->id;
        $this->name = $role->name;
        $this->selected_permissions = $role->permissions->pluck('name')->toArray();
    }

    function save()
    {
        $this->validate([
            'name' => 'required|unique:roles,name,' . $this->id,
        ]);

        $role = $this->id ? Role::findOrFail($this->id) : new Role(['guard_name' => 'web']);

        $role->name = $this->name;

        if ($role->save()) {
            $role->syncPermissions($this->selected_permissions);

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            $this->dispatch(
                'toast-show',
                heading: 'Role saved',
                text: 'Role saved successfully',
                variant: 'success',
                actions: [
                    [
                        'label' => 'View',
                        'href' => route('roles.index', ['search' => $role->name]),
                        'type' => 'link',
                    ],
                ],
            );
        }
    }

    function with()
    {
        $permissions = Permission::all();

        return compact('permissions');
    }
};
?>

<form wire:submit="save" {{ $attributes->merge(['class' => 'space-y-6']) }}>
    <flux:heading size="xl">
        {{ $id ? 'Edit Role' : 'Create Role' }}
    </flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('roles.index')" wire:navigate>Roles</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $id ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:input name="name" label="Name" wire:model="name" />


    <flux:checkbox.group wire:model="selected_permissions" label="Permissions" class="space-y-5">
        @foreach ($permissions as $permission)
            <flux:checkbox :label="ucfirst(str_replace('_', ' ', $permission->name))" :value="$permission->name" />
        @endforeach
    </flux:checkbox.group>

    <x-bottom-nav>
        <flux:button type="submit" variant="primary" class="md:w-auto w-full" wire:transition
            wire:loading.attr="disabled">
            Save
        </flux:button>
    </x-bottom-nav>

</form>
