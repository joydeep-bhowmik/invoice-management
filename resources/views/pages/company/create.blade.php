<?php
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use function Laravel\Folio\name;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
name('company.create');

new class extends Component {
    #[On('company-saved')]
    function redirectToDashboard()
    {
        $user = Auth::user();

        // Create or get admin role
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Give admin all permissions
        $permissions = Permission::all();

        $adminRole->syncPermissions($permissions);

        // Assign role to user (avoid duplicates)
        if (!$user->hasRole('admin')) {
            $user->assignRole($adminRole);
        }

        //activate the user
        $user->is_active = true;

        $user->save();

        $this->redirect(route('dashboard'), navigate: true);
    }
};

?>
<x-layouts::auth.split>
    @volt('company.create')
        <div class="w-full max-w-md">
            <div class="space-y-2">
                <flux:heading size="xl" class="font-semibold tracking-tight">
                    Create your company
                </flux:heading>

                <flux:subheading class="text-muted-foreground">
                    Tell us a few details to get your workspace set up.
                </flux:subheading>
            </div>

            <livewire:company.save class="w-full" />

        </div>
    @endvolt
</x-layouts::auth.split>
