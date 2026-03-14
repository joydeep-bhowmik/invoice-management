<?php

namespace App\Livewire;

use Flux\Flux;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use function Laravel\Folio\{name, middleware};

name('roles.index');
middleware(['auth', 'verified']);

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $permissionsFilter = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'permissionsFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPermissionsFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'permissionsFilter']);
        $this->resetPage();
    }

    public function delete($id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->users_count > 0) {
            Flux::toast('Cannot delete a role assigned to users.', variant: 'danger');
            return;
        }

        $role->delete();
        Flux::toast('Role deleted successfully', variant: 'success');
    }

    #[Computed]
    public function permissions()
    {
        return Permission::all();
    }

    #[Computed]
    public function roles()
    {
        $query = Role::query()
            ->withCount(['permissions', 'users'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->permissionsFilter, function (Builder $query) {
                $query->whereHas('permissions', function (Builder $subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->permissionsFilter . '%');
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }
};
?>

<x-layouts::app :title="__('Roles / All')">

    <div class="space-y-6">
        <flux:heading size="xl">
            Roles
        </flux:heading>

        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('roles.index')" wire:navigate>Roles</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        @volt('roles.index')
            <div>
                <!-- Search and Filter Controls -->
                <div class="mb-6 space-y-4">
                    <!-- Search Bar -->
                    <div class="flex flex-wrap md:flex-nowrap items-center gap-2 max-w-fit">
                        <flux:input c="search" placeholder="Search by name or description..." icon="magnifying-glass" />

                        <!-- Filter Dropdown -->
                        <flux:dropdown>
                            <flux:button variant="outline" icon="adjustments-horizontal" />
                            <flux:menu class="space-y-5">
                                <div class="p-4 space-y-5">
                                    <flux:select wire:model.live="permissionsFilter" label="Filter by Permission">
                                        <flux:select.option value="">All Permissions</flux:select.option>
                                        @foreach ($this->permissions as $permission)
                                            <flux:select.option value="{{ $permission->name }}">
                                                {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <div class="space-y-2">
                                        <flux:heading size="sm">Items per page</flux:heading>
                                        <flux:select wire:model.live="perPage" size="sm">
                                            <flux:select.option value="5">5 per page</flux:select.option>
                                            <flux:select.option value="10">10 per page</flux:select.option>
                                            <flux:select.option value="25">25 per page</flux:select.option>
                                            <flux:select.option value="50">50 per page</flux:select.option>
                                        </flux:select>
                                    </div>
                                </div>
                                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                                    Clear
                                </flux:button>
                            </flux:menu>
                        </flux:dropdown>

                        <flux:button :href="route('roles.create')" icon="plus" variant="primary" wire:navigate>
                            Create
                        </flux:button>
                    </div>
                </div>

                <!-- Roles Table -->
                <flux:table :paginate="$this->roles">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                            wire:click="sort('name')">Name</flux:table.column>
                        <flux:table.column>Description</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'permissions_count'" :direction="$sortDirection"
                            wire:click="sort('permissions_count')">Permissions</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'users_count'" :direction="$sortDirection"
                            wire:click="sort('users_count')">Users</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                            wire:click="sort('created_at')">Created</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->roles as $role)
                            <flux:table.row :key="$role->id">
                                <flux:table.cell class="flex items-center gap-3">
                                    <flux:avatar size="xs" :initials="substr($role->name, 0, 2)"
                                        class="bg-purple-100 text-purple-800" />
                                    <flux:text class="font-medium">{{ $role->name }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="max-w-xs">
                                        <flux:text>
                                            {{ $role->description ?? Str::limit($role->name . ' role for system permissions', 60) }}
                                        </flux:text>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:badge size="sm"
                                            :color="$role->permissions_count > 0 ? 'purple' : 'gray'" inset="top bottom">
                                            {{ $role->permissions_count }}
                                        </flux:badge>
                                        @if ($role->permissions_count > 0)
                                            <flux:dropdown>
                                                <flux:button variant="ghost" size="xs" icon="eye" />
                                                <flux:menu>
                                                    <flux:menu.heading>Permissions</flux:menu.heading>
                                                    @foreach ($role->permissions as $permission)
                                                        <flux:menu.item>
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </flux:menu.item>
                                                    @endforeach
                                                </flux:menu>
                                            </flux:dropdown>
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$role->users_count > 0 ? 'blue' : 'gray'"
                                        inset="top bottom">
                                        {{ $role->users_count }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">
                                    <flux:text> {{ $role->created_at->format('M d, Y') }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                            inset="top bottom"></flux:button>
                                        <flux:menu>
                                            <flux:menu.item icon="pencil"
                                                @click="Livewire.navigate(`{{ route('roles.edit', ['id' => $role->id]) }}`)">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger"
                                                @click="Flux.modal(`{{ 'delete-confirm' . $role->id }}`).show()">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>

                                    <flux:modal :name="'delete-confirm'.$role->id" class="min-w-[22rem]">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Delete Role?</flux:heading>
                                                <flux:text class="mt-2">
                                                    Are you sure you want to delete the "{{ $role->name }}" role?<br>
                                                    @if ($role->users_count > 0)
                                                        <span class="font-medium text-red-600">
                                                            This role is assigned to {{ $role->users_count }} user(s).
                                                        </span><br>
                                                    @endif
                                                    This action cannot be reversed.
                                                </flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer />
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button wire:click="delete({{ $role->id }})" variant="danger">
                                                    Delete
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center py-12 ">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <flux:icon name="shield-check" class="h-16 w-16 text-gray-300" />
                                        <div class="space-y-2">
                                            <div class="text-lg font-medium">No roles found</div>
                                            @if ($search || $permissionsFilter)
                                                <flux:text>Try adjusting your search or filters</flux:text>
                                                <flux:button variant="outline" size="sm" wire:click="clearFilters"
                                                    class="mt-2">
                                                    Clear Filters
                                                </flux:button>
                                            @else
                                                <flux:text>No roles have been created yet</flux:text>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        @endvolt
    </div>
</x-layouts::app>
