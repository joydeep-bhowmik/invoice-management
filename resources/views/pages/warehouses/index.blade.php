<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Laravel\Folio\name;
use Livewire\Attributes\Computed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

name('warehouses.index');

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $managerFilter = '';
    public $perPage = 10;
    public $transferFrom;
    public $transferTo;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'managerFilter' => ['except' => ''],
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

    public function updatedManagerFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'managerFilter']);
        $this->resetPage();
    }

    public function delete($id)
    {
        $warehouse = Warehouse::withCount('products')->findOrFail($id);

        // Check if warehouse has products before deleting
        if ($warehouse->products_count > 0) {
            Flux::toast('Cannot delete warehouse with existing products. Please move or delete the products first.', variant: 'danger');

            return;
        }

        $warehouse->delete();
        Flux::toast('Warehouse deleted successfully', variant: 'success');
    }

    #[Computed]
    public function managers()
    {
        return User::where('role', 'manager')->orWhere('is_manager', true)->orderBy('name')->get();
    }

    function openTransferModal()
    {
        $this->reset();
        Flux::modal('transfer-product')->show();
    }

    function transferProducts()
    {
        try {
            $this->validate([
                'transferFrom' => 'required|exists:warehouses,id|different:transferTo',
                'transferTo' => 'required|exists:warehouses,id',
            ]);
            $affected = Product::where('warehouse_id', $this->transferFrom)->update(['warehouse_id' => $this->transferTo]);

            if ($affected) {
                Flux::toast('Product transferred successfully', variant: 'success');
            }
        } catch (ValidationException $e) {
            Flux::modal('confirm-transfer')->close();
            throw $e;
        }
    }

    #[Computed]
    public function warehouses()
    {
        $query = Warehouse::with(['manager', 'products'])
            ->withCount('products')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('manager', function (Builder $subQuery) {
                            $subQuery->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->managerFilter, function (Builder $query) {
                $query->where('manager_id', $this->managerFilter);
            })
            ->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }
};
?>

<x-layouts::app :title="__('Warehouses / All')">

    <div class="space-y-6">
        <flux:heading size="xl">
            Warehouses
        </flux:heading>

        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('warehouses.index')" wire:navigate>Warehouses</flux:breadcrumbs.item>
        </flux:breadcrumbs>


        @volt('warehouses.index')
            <div>


                <!-- Search and Filter Controls -->
                <div class="mb-6 space-y-4">
                    <!-- Search Bar -->
                    <div class="flex flex-wrap md:flex-nowrap items-center gap-2 max-w-fit">
                        <flux:input wire:model.live.debounce.300ms="search"
                            placeholder="Search by name, description, or manager..." icon="magnifying-glass" />

                        <!-- Filter Dropdown -->
                        <flux:dropdown>
                            <flux:button variant="outline" icon="adjustments-horizontal" />
                            <flux:menu class="space-y-5">
                                <div class="p-4 space-y-5">
                                    <flux:select wire:model="managerFilter" label="Manager">
                                        <flux:select.option value="">All Managers</flux:select.option>
                                        @foreach ($this->managers as $manager)
                                            <flux:select.option value="{{ $manager->id }}">
                                                {{ $manager->name }} ({{ $manager->email }})
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
                                    Clear Filters
                                </flux:button>
                            </flux:menu>
                        </flux:dropdown>
                        <flux:button wire:click="openTransferModal">Transfer </flux:button>
                        <flux:modal name="transfer-product" class="md:w-96">
                            <div class="space-y-6">
                                <flux:heading size="lg">Transfer Products</flux:heading>
                                <flux:subheading>Transfer Products from one warehouse to another
                                </flux:subheading>
                                <flux:select label="From" wire:model="transferFrom" name="transferFrom">
                                    <option value="">Select</option>
                                    @foreach ($this->warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </flux:select>

                                <flux:select label="To" wire:model="transferTo" name="transferTo">
                                    <option value="">Select</option>
                                    @foreach ($this->warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </flux:select>

                                <div class="flex">
                                    <flux:spacer />
                                    <flux:modal.trigger name="confirm-transfer">
                                        <flux:button variant="primary"
                                            x-bind:disabled="!$wire.transferTo || !$wire.transferFrom">
                                            Transfer</flux:button>
                                    </flux:modal.trigger>
                                    <flux:modal name="confirm-transfer" class="min-w-[22rem]">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Are you sure?</flux:heading>
                                                <flux:text class="mt-2">
                                                    This will trasnfer all the products to another warehouse
                                                </flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer />
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger" wire:click="transferProducts">Transfer
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </div>
                            </div>
                        </flux:modal>

                        <flux:button href="{{ route('warehouses.create') }}" icon="plus" variant="primary" class="-ml-2"
                            wire:navigate> Create</flux:button>



                    </div>
                </div>



                <!-- Warehouses Table -->
                <flux:table :paginate="$this->warehouses">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                            wire:click="sort('name')">Name</flux:table.column>
                        <flux:table.column>Description</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'manager_id'" :direction="$sortDirection"
                            wire:click="sort('manager_id')">Manager</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'products_count'" :direction="$sortDirection"
                            wire:click="sort('products_count')">Products</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                            wire:click="sort('created_at')">Created</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->warehouses as $warehouse)
                            <flux:table.row :key="$warehouse->id">
                                <flux:table.cell class="flex items-center gap-3">
                                    <flux:avatar size="xs" :initials="substr($warehouse->name, 0, 2)"
                                        class="bg-blue-100 text-blue-800" />
                                    <flux:text>{{ $warehouse->name }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="max-w-xs">
                                        <flux:text> {{ Str::limit($warehouse->description, 60) }}</flux:text>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($warehouse->manager)
                                        <div class="flex items-center gap-2">
                                            <flux:avatar size="xs" :src="$warehouse->manager->avatar_url ?? ''"
                                                :initials="implode('', array_map(fn($w) => strtoupper($w[0]), preg_split('/\s+/', trim($warehouse->manager->name))))" />
                                            <div class="text-sm">
                                                <div class="font-medium">{{ $warehouse->manager->name }}</div>
                                                <flux:text class="text-xs">{{ $warehouse->manager->email }}</flux:text>

                                            </div>
                                        </div>
                                    @else
                                        <flux:text>No manager assigned</flux:text>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:badge size="sm"
                                            :color="$warehouse->products_count > 0 ? 'green' : 'gray'" inset="top bottom">
                                            {{ $warehouse->products_count }}
                                        </flux:badge>
                                        @if ($warehouse->products_count > 0)
                                            <flux:button variant="ghost" size="xs" icon="eye"
                                                :href="route('products.index', ['warehouseFilter' => $warehouse->id])"
                                                wire:navigate title="View products in this warehouse" />
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">
                                    <flux:text> {{ $warehouse->created_at->format('M d, Y') }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                            inset="top bottom"></flux:button>
                                        <flux:menu>
                                            <flux:menu.item icon="pencil"
                                                @click="Livewire.navigate(`{{ route('warehouses.edit', ['id' => $warehouse->id]) }}`)">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger"
                                                @click="Flux.modal(`{{ 'delete-confirm' . $warehouse->id }}`).show()">
                                                Delete
                                            </flux:menu.item>

                                        </flux:menu>
                                    </flux:dropdown>

                                    <flux:modal :name="'delete-confirm'.$warehouse->id" class="min-w-[22rem]">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Delete?</flux:heading>
                                                <flux:text class="mt-2">
                                                    Are your sure?.<br>
                                                    This action cannot be reversed.
                                                </flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer />
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button wire:click="delete({{ $warehouse->id }})" variant="danger">
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
                                        <flux:icon name="building-storefront" class="h-16 w-16 text-gray-300" />
                                        <div class="space-y-2">
                                            <div class="text-lg font-medium">No warehouses found</div>
                                            @if ($search || $managerFilter)
                                                <flux:text>Try adjusting your search or filters</flux:text>
                                                <flux:button variant="outline" size="sm" wire:click="clearFilters"
                                                    class="mt-2">
                                                    Clear Filters
                                                </flux:button>
                                            @else
                                                <flux:text>No warehouses have been created yet</flux:text>
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
