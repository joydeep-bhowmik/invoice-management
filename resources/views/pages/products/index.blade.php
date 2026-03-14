<?php

namespace App\Livewire;
use Flux\Flux;
use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Laravel\Folio\{name};
use Livewire\Attributes\Computed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
name('products.index');

new class extends Component {
    use WithPagination;
    public $transferTo;
    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $warehouseFilter = '';
    public $priceRange = ['min' => null, 'max' => null];
    public $perPage = 10;
    public $selected_product_ids = [];
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'warehouseFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        // Initialize price range if needed
        $this->priceRange = [
            'min' => Product::min('price'),
            'max' => Product::max('price'),
        ];
    }

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

    public function updatedWarehouseFilter()
    {
        $this->resetPage();
    }

    public function updatedPriceRange()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'warehouseFilter', 'priceRange']);
        $this->resetPage();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get();
    }
    function delete($id)
    {
        $product = Product::find($id);

        if ($product?->delete()) {
            Flux::toast('Product Deleted Successfully', variant: 'success');
        }
    }

    function transferProducts()
    {
        try {
            $this->validate([
                'transferTo' => 'required|exists:warehouses,id',
                'selected_product_ids' => 'required|array|min:1',
                'selected_product_ids.*' => 'exists:products,id',
            ]);

            $affected = Product::whereIn('id', $this->selected_product_ids)->update([
                'warehouse_id' => $this->transferTo,
            ]);

            if ($affected === 0) {
                // Be honest: nothing happened
                Flux::toast('No products were transferred', variant: 'warning');
                return;
            }

            Flux::toast("{$affected} products transferred successfully", variant: 'success');
            Flux::modals()->close();
        } catch (ValidationException $e) {
            Flux::modal('confirm-transfer')->close();
            throw $e;
        }
    }

    function deleteSelected()
    {
        $this->validate([
            'selected_product_ids' => 'required|array|min:1',
            'selected_product_ids.*' => 'exists:products,id',
        ]);

        $deleted = Product::whereIn('id', $this->selected_product_ids)->delete();

        if ($deleted === 0) {
            Flux::toast('No products were deleted', variant: 'warning');
            return;
        }

        Flux::toast("{$deleted} products deleted successfully", variant: 'success');

        // Cleanup UI state
        $this->reset('selected_product_ids');

        Flux::modal('confirm-delete')->close();
    }

    #[Computed]
    public function products()
    {
        $query = Product::with('warehouse')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('warehouse', function (Builder $subQuery) {
                            $subQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->warehouseFilter, function (Builder $query) {
                $query->where('warehouse_id', $this->warehouseFilter);
            })
            ->when($this->priceRange['min'], function (Builder $query) {
                $query->where('price', '>=', $this->priceRange['min']);
            })
            ->when($this->priceRange['max'], function (Builder $query) {
                $query->where('price', '<=', $this->priceRange['max']);
            })
            ->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }
};

?>

<x-layouts::app :title="__('Products / All')">
    <x-mobile-nav />
    <div class="space-y-6">
        <flux:heading size="xl">
            Products
        </flux:heading>

        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('products.index')" wire:navigate>Products</flux:breadcrumbs.item>
        </flux:breadcrumbs>



        @volt('products.index')
            <div>
                <!-- Search and Filter Controls -->
                <div class="mb-6 space-y-4">
                    <!-- Search Bar -->
                    <div class="flex flex-wrap md:flex-nowrap  items-center gap-2 max-w-fit">
                        <flux:input wire:model.live.debounce.300ms="search"
                            placeholder="Search by name, SKU, code, or warehouse..." icon="magnifying-glass" />

                        <!-- Warehouse Filter -->
                        <flux:dropdown>
                            <flux:button variant="outline" icon="adjustments-horizontal" />
                            <flux:menu class="space-y-5">
                                <div class="p-4 space-y-5">
                                    <flux:select wire:model.live.debounce.300ms="warehouseFilter" label="Warehouse">
                                        <flux:select.option value="">All Warehouses</flux:select.option>

                                        @foreach ($this->warehouses as $warehouse)
                                            <flux:select.option value="{{ $warehouse->id }}">
                                                {{ $warehouse->name }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <div class="space-y-4">
                                        <flux:heading>Price Range</flux:heading>

                                        <div class="flex items-center gap-3">
                                            <flux:input wire:model.live.debounce.300ms="priceRange.min" type="number"
                                                placeholder="Min" size="sm" />
                                            <span class="text-gray-400">to</span>
                                            <flux:input wire:model.live.debounce.300ms="priceRange.max" type="number"
                                                placeholder="Max" size="sm" />
                                        </div>
                                    </div>

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

                        <flux:modal.trigger name="bulk-action">
                            <flux:button x-bind:disabled="$wire.selected_product_ids.length ? false : true"> Bulk <span
                                    x-show="$wire.selected_product_ids.length"> <span
                                        x-text="$wire.selected_product_ids.length"></span></span>
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="bulk-action" class="md:w-96">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Take bulk action</flux:heading>
                                    <flux:text class="mt-2">chose what to do with selected products</flux:text>
                                </div>

                                <flux:modal.trigger name="confirm-delete">
                                    <flux:button variant="danger">Delete
                                    </flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="confirm-delete" class="min-w-[22rem]">
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Delete products?</flux:heading>

                                            <flux:text class="mt-2">
                                                You're about to delete selected products.<br>
                                                This action cannot be reversed.
                                            </flux:text>
                                        </div>

                                        <div class="flex gap-2">
                                            <flux:spacer />

                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cancel</flux:button>
                                            </flux:modal.close>

                                            <flux:button wire:click="deleteSelected" variant="danger">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                                <flux:modal.trigger name="transfer-products">
                                    <flux:button>Transfer
                                    </flux:button>
                                </flux:modal.trigger>
                                <flux:modal name="transfer-products" class="md:w-96">
                                    <div class="space-y-6">
                                        <flux:heading size="lg">Transfer Products</flux:heading>

                                        <flux:select label="Transfer To" wire:model="transferTo" name="transferTo">
                                            <option value="">Select</option>
                                            @foreach (Warehouse::all() as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </flux:select>

                                        <div class="flex">
                                            <flux:spacer />
                                            <flux:modal.trigger name="confirm-transfer">
                                                <flux:button variant="primary" x-bind:disabled="!$wire.transferTo">
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

                            </div>
                        </flux:modal>
                        <flux:button href="{{ route('products.create') }}" icon="plus" class=" -ml-2" variant="primary"
                            wire:navigate>
                            Create
                        </flux:button>

                    </div>
                </div>

                <!-- Products Table -->
                <flux:table :paginate="$this->products" x-data="{
                    allChekced: false,
                    init() {
                        $watch('allChekced', (val) => {
                            if (val) {
                                $dispatch('select-all');
                            } else {
                                $dispatch('deselect-all');
                            }
                        })
                
                    }
                }">
                    <flux:table.columns>
                        <flux:table.column>
                            <flux:checkbox x-model="allChekced" />
                        </flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'price'" :direction="$sortDirection"
                            wire:click="sort('price')">Price</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'quantity'" :direction="$sortDirection"
                            wire:click="sort('quantity')">Quantity</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'warehouse_id'" :direction="$sortDirection"
                            wire:click="sort('warehouse_id')">Warehouse</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'sku'" :direction="$sortDirection"
                            wire:click="sort('sku')">SKU</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection"
                            wire:click="sort('code')">Code</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                            wire:click="sort('created_at')">Created</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->products as $product)
                            <flux:table.row :key="$product->id">
                                <flux:table.cell variant="strong">
                                    <flux:checkbox wire:model="selected_product_ids" value="{{ $product->id }}"
                                        @select-all.window="$el.checked = true;$el.dispatchEvent(new Event('input'))"
                                        @deselect-all.window="$el.checked = false;$el.dispatchEvent(new Event('input'))" />
                                </flux:table.cell>
                                <flux:table.cell class="flex items-center gap-3">
                                    <flux:avatar size="xs" :src="$product->image_url ?? ''" :alt="$product->name"
                                        :initials="substr($product->name, 0, 2)" />
                                    <flux:text>{{ $product->name }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell variant="strong">
                                    {{ number_format($product->price, 2) }}
                                </flux:table.cell>

                                <flux:table.cell variant="strong">
                                    {{ $product->quantity }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" color="green" inset="top bottom">
                                        <a href="{{ route('warehouses.index', ['search' => $product->warehouse->name]) }}"
                                            wire:navigate>{{ $product->warehouse->name }}</a>
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" color="gray" inset="top bottom">
                                        {{ $product->sku ?? 'N/A' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" color="blue" inset="top bottom">
                                        {{ $product->code ?? 'N/A' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">
                                    {{ $product->created_at->format('M d, Y') }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                            inset="top bottom"></flux:button>
                                        <flux:menu>
                                            <flux:menu.item icon="pencil"
                                                @click="Livewire.navigate(`{{ route('products.edit', ['id' => $product->id]) }}`)">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger"
                                                @click="Flux.modal(`{{ 'delete-confirm' . $product->id }}`).show()">
                                                Delete
                                            </flux:menu.item>

                                        </flux:menu>
                                    </flux:dropdown>

                                    <flux:modal :name="'delete-confirm'.$product->id" class="min-w-[22rem]">
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
                                                <flux:button wire:click="delete({{ $product->id }})" variant="danger">
                                                    Delete
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </flux:table.cell>


                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="text-center py-8 text-gray-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <flux:icon name="inbox" class="h-12 w-12 text-gray-300" />
                                        <div class="text-lg font-medium">No products found</div>
                                        @if ($search || $warehouseFilter || $priceRange['min'] || $priceRange['max'])
                                            <div class="text-sm">Try adjusting your search or filters</div>
                                            <flux:button variant="outline" size="sm" wire:click="clearFilters"
                                                class="mt-2">
                                                Clear
                                            </flux:button>
                                        @else
                                            <div class="text-sm">No products have been added yet</div>
                                        @endif
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
