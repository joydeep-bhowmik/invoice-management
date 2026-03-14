<?php

use App\Models\Invoice;
use Livewire\Volt\Component;
use function Laravel\Folio\name;
name('invoices.single');

new class extends Component {
    public Invoice $invoice;

    public function mount($id)
    {
        if (!$id) {
            return;
        }

        $invoice = Invoice::find($id);
        if (!$invoice) {
            abort(404);
        }

        $this->invoice = $invoice->load('items');
    }

    public function markAsPaid()
    {
        $this->invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->invoice->refresh();
        Flux::toast('Invoice marked as paid!', variant: 'success');
    }

    public function markAsSent()
    {
        $this->invoice->update([
            'status' => 'sent',
        ]);

        $this->invoice->refresh();
        Flux::toast('Invoice marked as sent!', variant: 'success');
    }
};
?>
<x-layouts::app>
    @volt('invoices.singleaa')
        <x-slot name="title">
            {{ __('Invoice') }} #{{ $invoice->invoice_number }}
        </x-slot>

        <div x-data="{
            printInvoice() {
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = '{{ route('invoices.stream', $invoice) }}';
        
                    iframe.onload = () => {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    };
        
                    document.body.appendChild(iframe);
                },
                downloadInvoice() {
                    window.location.href = '{{ route('invoices.pdf', $invoice) }}';
                }
        }" x-on:print-invoice.window="printInvoice()" class="space-y-6 max-w-3xl">

            <!-- Header with title and breadcrumbs -->
            <flux:heading size="xl">
                Invoice #{{ $invoice->invoice_number }}
            </flux:heading>

            <flux:breadcrumbs class="no-print">
                <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate>
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('invoices.index')" wire:navigate>Invoices
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $invoice->invoice_number }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>




            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 no-print ">
                <flux:button :href="route('invoices.edit', ['id'=>$invoice->id])" variant="outline" size="xs"
                    icon="pencil" wire:navigate>
                    Edit
                </flux:button>

                <flux:button variant="outline" icon="printer" x-on:click="printInvoice()" size="xs">
                    Print
                </flux:button>

                <flux:button variant="outline" icon="document-arrow-down" x-on:click="downloadInvoice()" size="xs">
                    PDF
                </flux:button>

                @if ($invoice->status === 'draft')
                    <flux:button variant="outline" icon="paper-airplane" wire:click="markAsSent" size="xs">
                        Mark as Sent
                    </flux:button>
                @endif

                @if ($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                    <flux:button variant="primary" icon="check-circle" wire:click="markAsPaid" size="xs">
                        Mark as Paid
                    </flux:button>
                @endif
                <!-- Status Badge -->
                @php
                    $statusColors = [
                        'draft' => 'zinc',
                        'issued' => 'yellow',
                        'paid' => 'green',
                        'cancelled' => 'red',
                    ];

                @endphp
                <flux:badge color="{{ $statusColors[$invoice->status] ?? 'bg-gray-100' }}" size="sm">
                    {{ ucfirst($invoice->status) }}
                    @if ($invoice->paid_at)
                        on: {{ $invoice->paid_at->format('M d, Y') }}
                    @endif
                </flux:badge>

            </div>
            <!-- Invoice Details Card -->
            <div class="print:border-none print:shadow-none divide-y divide-zinc-200 dark:divide-zinc-700 space-y-10 ">

                {{-- Header --}}
                <div class="flex flex-col md:flex-row justify-between gap-6">
                    <div>
                        <flux:heading size="lg">Seller Information</flux:heading>
                        <flux:subheading>#{{ $invoice->invoice_number }}</flux:subheading>
                    </div>

                    <div class="md:text-right">
                        <flux:heading size="base">{{ $invoice->seller_name }}</flux:heading>

                        @if ($invoice->seller_address)
                            <flux:text>{{ $invoice->seller_address }}</flux:text>
                        @endif

                        @if ($invoice->seller_phone)
                            <flux:text>Phone: {{ $invoice->seller_phone }}</flux:text>
                        @endif

                        @if ($invoice->seller_tax_id)
                            <flux:text>Tax ID: {{ $invoice->seller_tax_id }}</flux:text>
                        @endif
                    </div>
                </div>

                {{-- Client & Invoice Details --}}
                <flux:table>

                    <flux:heading size="lg">Client & Invoice Details</flux:heading>
                    <flux:table.columns>
                        <flux:table.column>Bill To</flux:table.column>
                        <flux:table.column>Invoice Details</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:text>{{ $invoice->client_name }}</flux:text>

                                @if ($invoice->client_address)
                                    <flux:text>{{ $invoice->client_address }}</flux:text>
                                @endif

                                @if ($invoice->client_phone)
                                    <flux:text>Phone: {{ $invoice->client_phone }}</flux:text>
                                @endif

                                @if ($invoice->client_tax_id)
                                    <flux:text>Tax ID: {{ $invoice->client_tax_id }}</flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>

                                <flux:text>
                                    Invoice Date:
                                    {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') }}
                                </flux:text>

                                @if ($invoice->due_date)
                                    <flux:text>
                                        Due Date:
                                        {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}
                                    </flux:text>
                                @endif

                                <flux:text>Currency: {{ $invoice->currency }}</flux:text>
                                <flux:text>Status: {{ ucfirst($invoice->status) }}</flux:text>

                                @if ($invoice->paid_at)
                                    <flux:text>Paid Date: {{ $invoice->paid_at->format('M d, Y') }}</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>

                {{-- Items --}}
                <flux:table>
                    <flux:heading size="lg">Items</flux:heading>
                    <flux:table.columns>
                        <flux:table.column>Description</flux:table.column>
                        <flux:table.column>Qty</flux:table.column>
                        <flux:table.column>Unit Price</flux:table.column>
                        <flux:table.column>Line Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($invoice->items as $item)
                            <flux:table.row>
                                <flux:table.cell>{{ $item->description }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($item->quantity, 2) }}</flux:table.cell>
                                <flux:table.cell>
                                    {{ $invoice->currency_symbol }} {{ number_format($item->unit_price, 2) }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $invoice->currency_symbol }}{{ number_format($item->line_total, 2) }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                {{-- Additional Charges --}}
                @php
                    $customFields = $invoice->custom_charges ? json_decode($invoice->custom_charges, true) : [];

                    $customTotal = collect($customFields)->sum(fn($f) => $f['stored_amount'] ?? 0);
                @endphp

                @if ($customFields)
                    <flux:table>
                        <flux:heading size="lg">Additional Charges</flux:heading>
                        <flux:table.columns>
                            <flux:table.column>Charges</flux:table.column>
                            <flux:table.column></flux:table.column>
                            <flux:table.column class="text-right">Amount</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($customFields as $field)
                                <flux:table.row>
                                    <flux:table.cell>
                                        {{ $field['label'] }}
                                        @isset($field['stored_percentage'])
                                            ({{ $field['stored_percentage'] }}%)
                                        @endisset
                                    </flux:table.cell>
                                    <flux:table.cell></flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        {{ $invoice->currency_symbol }}
                                        {{ number_format($field['stored_amount'] ?? 0, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif

                {{-- Totals --}}
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>{{ $invoice->currency_symbol }} {{ number_format($invoice->subtotal, 2) }}</span>
                    </div>

                    @if ($invoice->shipping_total > 0)
                        <div class="flex justify-between">
                            <span>Shipping</span>
                            <span>{{ $invoice->currency_symbol }} {{ number_format($invoice->shipping_total, 2) }}</span>
                        </div>
                    @endif

                    @if ($invoice->tax_total > 0)
                        <div class="flex justify-between">
                            <span>Tax</span>
                            <span>{{ $invoice->currency_symbol }} {{ number_format($invoice->tax_total, 2) }}</span>
                        </div>
                    @endif

                    @if ($customTotal > 0)
                        <div class="flex justify-between">
                            <span>Additional Charges</span>
                            <span>{{ $invoice->currency_symbol }}
                                {{ number_format($customTotal, 2) }}</span>
                        </div>
                    @endif

                    <div class="border-t dark:border-zinc-400 pt-2 mt-2 flex justify-between font-bold">
                        <span>Total</span>
                        <span>{{ $invoice->currency_symbol }} {{ number_format($invoice->total, 2) }}</span>
                    </div>
                </div>

                {{-- Notes --}}
                @if ($invoice->legal_notes)
                    <div class="border-t pt-4 dark:border-zinc-600">
                        <flux:heading size="base">Notes</flux:heading>
                        <flux:text class="whitespace-pre-line">
                            {{ $invoice->legal_notes }}
                        </flux:text>
                    </div>
                @endif

            </div>


        </div>
    @endvolt


</x-layouts::app>
