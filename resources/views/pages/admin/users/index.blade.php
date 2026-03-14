<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use function Laravel\Folio\{name, middleware};

name('users.index');
middleware(['auth', 'verified']);

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $roleFilter = '';
    public $statusFilter = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'roleFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
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

    public function updatedRoleFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            Flux::toast('Cannot delete your own account.', variant: 'danger');
            return;
        }

        $user->delete();
        Flux::toast('User deleted successfully', variant: 'success');
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function users()
    {
        $query = User::with(['roles'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function (Builder $query) {
                $query->whereHas('roles', function (Builder $subQuery) {
                    $subQuery->where('id', $this->roleFilter);
                });
            })
            ->when($this->statusFilter, function (Builder $query) {
                if ($this->statusFilter === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }
};
?>

<x-layouts::app :title="__('Users / All')">

    <div class="space-y-6">
        <flux:heading size="xl">
            Users
        </flux:heading>

        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('users.index')" wire:navigate>Users</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        @volt('users.index')
            <div>
                <!-- Search and Filter Controls -->
                <div class="mb-6 space-y-4">
                    <!-- Search Bar -->
                    <div class="flex flex-wrap md:flex-nowrap items-center gap-2 max-w-fit">
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, email, or phone..."
                            icon="magnifying-glass" />

                        <!-- Filter Dropdown -->
                        <flux:dropdown>
                            <flux:button variant="outline" icon="adjustments-horizontal" />
                            <flux:menu class="space-y-5">
                                <div class="p-4 space-y-5">
                                    <flux:select wire:model.live="roleFilter" label="Role">
                                        <flux:select.option value="">All Roles</flux:select.option>
                                        @foreach ($this->roles as $role)
                                            <flux:select.option value="{{ $role->id }}">
                                                {{ $role->name }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <flux:select wire:model.live="statusFilter" label="Status">
                                        <flux:select.option value="">All Status</flux:select.option>
                                        <flux:select.option value="active">Active</flux:select.option>
                                        <flux:select.option value="inactive">Inactive</flux:select.option>
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
                    </div>
                </div>

                <!-- Users Table -->
                <flux:table :paginate="$this->users">
                    <flux:table.columns>
                        <flux:table.column>Avatar</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                            wire:click="sort('name')">Name</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection"
                            wire:click="sort('email')">Email</flux:table.column>
                        <flux:table.column>Roles</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                            wire:click="sort('created_at')">Joined</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell>
                                    <x-profile :$user />
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-col">
                                        <flux:text class="font-medium">{{ $user->name }}</flux:text>
                                        @if ($user->phone)
                                            <flux:text class="text-xs text-gray-500">{{ $user->phone }}</flux:text>
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text>{{ $user->email }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @forelse ($user->roles as $role)
                                            <flux:badge size="sm" color="blue">
                                                {{ $role->name }}
                                            </flux:badge>
                                        @empty
                                            <flux:text class="text-xs text-gray-500">No roles assigned</flux:text>
                                        @endforelse
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">
                                    <flux:text> {{ $user->created_at->format('M d, Y') }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$user->is_active ? 'green' : 'gray'">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                            inset="top bottom"></flux:button>
                                        <flux:menu>

                                            <flux:menu.item icon="pencil"
                                                @click="Livewire.navigate(`{{ route('users.edit', ['id' => $user->id]) }}`)">
                                                Edit
                                            </flux:menu.item>

                                            @if ($user->id !== auth()->id())
                                                <flux:menu.item icon="trash" variant="danger"
                                                    @click="Flux.modal(`{{ 'delete-confirm' . $user->id }}`).show()">
                                                    Delete
                                                </flux:menu.item>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>

                                    @if ($user->id !== auth()->id())
                                        <flux:modal :name="'delete-confirm'.$user->id" class="min-w-[22rem]">
                                            <div class="space-y-6">
                                                <div>
                                                    <flux:heading size="lg">Delete User?</flux:heading>
                                                    <flux:text class="mt-2">
                                                        Are you sure you want to delete "{{ $user->name }}"?<br>
                                                        <span class="font-medium text-red-600">
                                                            This will permanently delete the user account and all associated
                                                            data.
                                                        </span><br>
                                                        This action cannot be reversed.
                                                    </flux:text>
                                                </div>
                                                <div class="flex gap-2">
                                                    <flux:spacer />
                                                    <flux:modal.close>
                                                        <flux:button variant="ghost">Cancel</flux:button>
                                                    </flux:modal.close>
                                                    <flux:button wire:click="delete({{ $user->id }})" variant="danger">
                                                        Delete
                                                    </flux:button>
                                                </div>
                                            </div>
                                        </flux:modal>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="text-center py-12 ">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <flux:icon name="user-group" class="h-16 w-16 text-gray-300" />
                                        <div class="space-y-2">
                                            <div class="text-lg font-medium">No users found</div>
                                            @if ($search || $roleFilter || $statusFilter)
                                                <flux:text>Try adjusting your search or filters</flux:text>
                                                <flux:button variant="outline" size="sm" wire:click="clearFilters"
                                                    class="mt-2">
                                                    Clear Filters
                                                </flux:button>
                                            @else
                                                <flux:text>No users have been created yet</flux:text>
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
