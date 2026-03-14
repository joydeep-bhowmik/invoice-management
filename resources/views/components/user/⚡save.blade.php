<?php

use Flux\Flux;
use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component {
    public $id;
    public $name;
    public $email;
    public $is_active = true;
    public array $selected_roles = [];
    public array $selected_permissions = [];

    function mount($id = null)
    {
        if (!$id) {
            return;
        }

        $user = User::find($id);

        if ($id && !$user) {
            abort(404);
        }

        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_active = (bool) $user->is_active;
        $this->selected_roles = $user->roles->pluck('name')->toArray();
        $this->selected_permissions = $user->getDirectPermissions()->pluck('name')->toArray();
    }

    function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->id,
            'is_active' => 'boolean',
        ]);

        $user = User::find($this->id);
        $user->name = $this->name;
        $user->email = $this->email;
        $user->is_active = $this->is_active;

        if ($user->save()) {
            // Sync roles
            $user->syncRoles($this->selected_roles);

            // Sync direct permissions
            $user->syncPermissions($this->selected_permissions);

            $this->dispatch(
                'toast-show',
                heading: 'User saved',
                text: 'User saved successfully',
                variant: 'success',
                actions: [
                    [
                        'label' => 'View',
                        'href' => route('users.index'),
                        'type' => 'link',
                    ],
                ],
            );
        }
    }

    function with()
    {
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::all();

        return compact('roles', 'permissions');
    }
};
?>

<form wire:submit="save" {{ $attributes->merge(['class' => 'space-y-6']) }}>
    <flux:heading size="xl">
        Edit User
    </flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('users.index')" wire:navigate>Users</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $id ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <flux:card class="space-y-6">
            <flux:heading size="lg">Basic Information</flux:heading>

            <flux:input name="name" label="Full Name" wire:model="name" required />

            <flux:input name="email" label="Email Address" type="email" wire:model="email" required />

            <flux:checkbox name="is_active" label="Active User" wire:model="is_active">
                User is active and can access the system
            </flux:checkbox>
        </flux:card>

        <!-- Roles & Permissions -->
        <div class="space-y-6">
            <!-- Roles Section -->
            <flux:card class="space-y-6">
                <flux:heading size="lg">Roles</flux:heading>
                <flux:subheading>
                    Assign roles to this user. Roles provide a set of permissions.
                </flux:subheading>

                <flux:checkbox.group wire:model="selected_roles" class="space-y-3">
                    @foreach ($roles as $role)
                        <flux:checkbox :label="$role->name" :value="$role->name"
                            :description="'Permissions: ' . $role->permissions->count()">
                        </flux:checkbox>
                    @endforeach
                </flux:checkbox.group>

                @if (count($selected_roles) === 0)
                    <flux:text variant="info" icon="information-circle">

                    </flux:text>
                @endif
            </flux:card>

            <!-- Direct Permissions Section -->
            <flux:card class=" space-y-6">
                <flux:heading size="lg">Direct Permissions</flux:heading>
                <flux:subheading>
                    Assign additional permissions directly to this user (overrides role permissions).
                </flux:subheading>

                <div class="space-y-4  overflow-y-auto pr-2">
                    @foreach ($permissions as $permission)
                        <div class="space-y-3">
                            <flux:checkbox :label="ucfirst(str_replace(['.', '_'], ' ', $permission->name))"
                                :value="$permission->name" wire:model="selected_permissions" size="sm" />
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>
    </div>

    <x-bottom-nav>
        <flux:button type="submit" variant="primary" class="md:w-auto w-full" wire:transition
            wire:loading.attr="disabled">
            Save
        </flux:button>
    </x-bottom-nav>
</form>
