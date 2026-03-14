<?php

use Flux\Flux;
use App\Models\Invoice;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Database\Eloquent\Builder;
use function Laravel\Folio\{name, middleware};
name('invoices.index');

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'invoice_date';
    public $sortDirection = 'desc';
    public $statusFilter = '';
    public $dateRange = ['from' => null, 'to' => null];
    public $perPage = 10;
    public $selected_invoice_ids = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'invoice_date'],
        'sortDirection' => ['except' => 'desc'],
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

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedDateRange()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'dateRange']);
        $this->resetPage();
    }

    #[Computed]
    public function statuses()
    {
        return ['draft', 'issued', 'paid', 'overdue', 'cancelled'];
    }

    public function delete($id)
    {
        $invoice = Invoice::find($id);

        if ($invoice?->delete()) {
            Flux::toast('Invoice Deleted Successfully', variant: 'success');
        }
    }

    public function deleteSelected()
    {
        $this->validate([
            'selected_invoice_ids' => 'required|array|min:1',
            'selected_invoice_ids.*' => 'exists:invoices,id',
        ]);

        $deleted = Invoice::whereIn('id', $this->selected_invoice_ids)->delete();

        if ($deleted === 0) {
            Flux::toast('No invoices were deleted', variant: 'warning');
            return;
        }

        Flux::toast("{$deleted} invoices deleted successfully", variant: 'success');
        $this->reset('selected_invoice_ids');
        Flux::modal('confirm-delete')->close();
    }

    #[Computed]
    public function invoices()
    {
        $query = Invoice::query()
            ->when($this->search, function (Builder $q) {
                $q->where(function (Builder $q2) {
                    $q2->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('client_name', 'like', "%{$this->search}%")
                        ->orWhere('seller_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateRange['from'], fn($q) => $q->where('invoice_date', '>=', $this->dateRange['from']))
            ->when($this->dateRange['to'], fn($q) => $q->where('invoice_date', '<=', $this->dateRange['to']))
            ->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }
};
?>

<x-layouts::app :title="__('Invoices / All')">
    <x-mobile-nav />
    <div class="space-y-6">
        <flux:heading size="xl">Invoices</flux:heading>

        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('invoices.index')" wire:navigate>Invoices</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        @volt('invoices.index')
            <div>
                <!-- Search and Filter Controls -->
                <div class="mb-6 space-y-4">
                    <!-- Search Bar -->
                    <div class="flex flex-wrap md:flex-nowrap items-center gap-2 max-w-fit">
                        <flux:input wire:model.live.debounce.300ms="search"
                            placeholder="Search by invoice number, client, or seller..." icon="magnifying-glass" />

                        <!-- Filter Dropdown -->
                        <flux:dropdown>
                            <flux:button variant="outline" icon="adjustments-horizontal" />
                            <flux:menu class="space-y-5">
                                <div class="p-4 space-y-5">
                                    <!-- Status Filter -->
                                    <flux:select wire:model.live.debounce.300ms="statusFilter" label="Status">
                                        <flux:select.option value="">All Statuses</flux:select.option>
                                        @foreach ($this->statuses as $status)
                                            <flux:select.option value="{{ $status }}">
                                                {{ ucfirst($status) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <!-- Date Range Filter -->
                                    <div class="space-y-4">
                                        <flux:heading>Date Range</flux:heading>
                                        <div class="flex items-center gap-3">
                                            <flux:input wire:model.live.debounce.300ms="dateRange.from" type="date"
                                                placeholder="From" size="sm" />
                                            <span class="text-gray-400">to</span>
                                            <flux:input wire:model.live.debounce.300ms="dateRange.to" type="date"
                                                placeholder="To" size="sm" />
                                        </div>
                                    </div>

                                    <!-- Items per page -->
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

                        <!-- Bulk Action Modal Trigger -->
                        <flux:modal.trigger name="bulk-action">
                            <flux:button x-bind:disabled="$wire.selected_invoice_ids.length ? false : true">
                                Bulk <span x-show="$wire.selected_invoice_ids.length">
                                    <span x-text="$wire.selected_invoice_ids.length"></span>
                                </span>
                            </flux:button>
                        </flux:modal.trigger>

                        <!-- Bulk Action Modal -->
                        <flux:modal name="bulk-action" class="md:w-96">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Take bulk action</flux:heading>
                                    <flux:text class="mt-2">Choose what to do with selected invoices</flux:text>
                                </div>

                                <!-- Delete Bulk Action -->
                                <flux:modal.trigger name="confirm-delete">
                                    <flux:button variant="danger">Delete</flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="confirm-delete" class="md:w-96">
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Delete invoices?</flux:heading>
                                            <flux:text class="mt-2">
                                                You're about to delete selected invoices.<br>
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

                                <!-- Add more bulk actions here if needed -->
                            </div>
                        </flux:modal>

                        <!-- Create Button -->
                        <flux:button href="{{ route('invoices.create') }}" icon="plus" class="-ml-2" variant="primary"
                            wire:navigate>
                            Create
                        </flux:button>
                    </div>
                </div>

                <!-- Invoices Table -->
                <flux:table :paginate="$this->invoices" x-data="{
                    allChecked: false,
                    init() {
                        $watch('allChecked', (val) => {
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
                            <flux:checkbox x-model="allChecked" />
                        </flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'invoice_number'" :direction="$sortDirection"
                            wire:click="sort('invoice_number')">Invoice #</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'client_name'" :direction="$sortDirection"
                            wire:click="sort('client_name')">Client</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'invoice_date'" :direction="$sortDirection"
                            wire:click="sort('invoice_date')">Date</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'total'" :direction="$sortDirection"
                            wire:click="sort('total')">Total</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection"
                            wire:click="sort('status')">Status</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->invoices as $invoice)
                            <flux:table.row :key="$invoice->id">
                                <flux:table.cell variant="strong">
                                    <flux:checkbox wire:model="selected_invoice_ids" value="{{ $invoice->id }}"
                                        @select-all.window="$el.checked = true;$el.dispatchEvent(new Event('input'))"
                                        @deselect-all.window="$el.checked = false;$el.dispatchEvent(new Event('input'))" />
                                </flux:table.cell>

                                <flux:table.cell variant="strong">
                                    <div class="flex items-center gap-2">
                                        <flux:badge> {{ $invoice->invoice_number }}</flux:badge>
                                        <flux:button variant="ghost" size="xs" icon="eye"
                                            :href="route('invoices.single', ['id' => $invoice->id])" wire:navigate />
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $invoice->client_name }}
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">
                                    {{ $invoice->invoice_date->format('M d, Y') }}
                                </flux:table.cell>

                                <flux:table.cell variant="strong">
                                    {{ $invoice->currency_symbol }}
                                    {{ number_format($invoice->total, 2) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @php
                                        $statusColors = [
                                            'draft' => 'gray',
                                            'issued' => 'blue',
                                            'paid' => 'green',
                                            'overdue' => 'red',
                                            'cancelled' => 'gray',
                                        ];
                                        $color = $statusColors[$invoice->status] ?? 'gray';
                                    @endphp
                                    <flux:badge size="sm" :color="$color" inset="top bottom">
                                        {{ ucfirst($invoice->status) }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                            inset="top bottom"></flux:button>
                                        <flux:menu>
                                            <flux:menu.item icon="pencil"
                                                @click="Livewire.navigate(`{{ route('invoices.edit', ['id' => $invoice->id]) }}`)">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger"
                                                @click="Flux.modal(`delete-confirm-{{ $invoice->id }}`).show()">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>

                                    <!-- Delete Confirmation Modal -->
                                    <flux:modal :name="'delete-confirm-' . $invoice->id" class="min-w-[22rem]">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Delete?</flux:heading>
                                                <flux:text class="mt-2">
                                                    Are you sure?<br>
                                                    This action cannot be reversed.
                                                </flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer />
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button wire:click="delete({{ $invoice->id }})" variant="danger">
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
                                        <div class="text-lg font-medium">No invoices found</div>
                                        @if ($search || $statusFilter || $dateRange['from'] || $dateRange['to'])
                                            <div class="text-sm">Try adjusting your search or filters</div>
                                            <flux:button variant="outline" size="sm" wire:click="clearFilters"
                                                class="mt-2">
                                                Clear
                                            </flux:button>
                                        @else
                                            <div class="text-sm">No invoices have been created yet</div>
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
