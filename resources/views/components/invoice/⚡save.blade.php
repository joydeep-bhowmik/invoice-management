<?php
use Flux\Flux;
use App\Models\Invoice;
use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;

new class extends Component {
    use WithPagination;
    public int $step = 1;
    public array $unlockedStep = [1];
    public $steps = [
        1 => 'Basic',
        2 => 'Seller Details',
        3 => 'Client Details',
        4 => 'Items',
        5 => 'Charges & Totals',
        6 => 'Additional Info',
        7 => 'Review',
    ];
    public $id;
    public $invoice_number;
    public $invoice_date;
    public $due_date;
    public $country_code;
    public $seller_name;
    public $seller_phone;
    public $seller_address;
    public $seller_tax_id;
    public $client_name;
    public $client_phone;
    public $client_address;
    public $client_tax_id;
    public $subtotal = 0;
    public $shipping_total = 0;
    public $tax_total = 0;
    public $total = 0;
    public $currency = 'INR';
    public $status = 'draft';
    public $legal_notes;
    public $tax_meta;
    public $paid_at;
    public $custom_charges = [];
    public $items = [];
    public $code;
    public $search;
    public $products;

    public $currencies = ['AED', 'AFN', 'ALL', 'AMD', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CZK', 'DKK', 'DOP', 'EGP', 'ETB', 'EUR', 'FJD', 'GBP', 'GHS', 'GMD', 'GNF', 'GTQ', 'HKD', 'HNL', 'HRK', 'HUF', 'IDR', 'ILS', 'INR', 'JMD', 'JPY', 'KES', 'KRW', 'KWD', 'LKR', 'MAD', 'MXN', 'MYR', 'NGN', 'NOK', 'NZD', 'PEN', 'PHP', 'PKR', 'PLN', 'RON', 'RUB', 'SAR', 'SEK', 'SGD', 'THB', 'TND', 'TRY', 'TWD', 'UAH', 'UGX', 'USD', 'UYU', 'VEF', 'VND', 'XAF', 'XOF', 'ZAR'];

    public function mount($id = null)
    {
        if (!$id) {
            return;
        }

        $invoice = Invoice::with('items')->find($id);
        if (!$invoice) {
            abort(404);
        }

        $this->id = $invoice->id;
        $this->invoice_number = $invoice->invoice_number;
        $this->invoice_date = $invoice->invoice_date?->format('Y-m-d');
        $this->due_date = $invoice->due_date?->format('Y-m-d');
        $this->country_code = $invoice->country_code;
        $this->seller_name = $invoice->seller_name;
        $this->seller_phone = $invoice->seller_phone;
        $this->seller_address = $invoice->seller_address;
        $this->seller_tax_id = $invoice->seller_tax_id;
        $this->client_name = $invoice->client_name;
        $this->client_phone = $invoice->client_phone;
        $this->client_address = $invoice->client_address;
        $this->client_tax_id = $invoice->client_tax_id;
        $this->subtotal = $invoice->subtotal;
        $this->shipping_total = $invoice->shipping_total;
        $this->tax_total = $invoice->tax_total;
        $this->total = $invoice->total;
        $this->currency = $invoice->currency;
        $this->status = $invoice->status;
        $this->legal_notes = $invoice->legal_notes;
        $this->tax_meta = $invoice->tax_meta ? json_decode($invoice->tax_meta, true) : null;
        $this->paid_at = $invoice->paid_at;

        // Decode custom charges with default values for new charges
        $this->custom_charges = $invoice->custom_charges ? json_decode($invoice->custom_charges, true) : [];

        // Ensure all custom charges have the new charges and calculate amounts
        foreach ($this->custom_charges as &$charge) {
            if (!isset($charge['type'])) {
                $charge['type'] = 'fixed';
            }
            if (!isset($charge['percentage'])) {
                $charge['percentage'] = 0;
            }
            if (!isset($charge['amount'])) {
                $charge['amount'] = 0;
            }

            // Calculate the amount for percentage charges based on stored data
            if ($charge['type'] === 'percentage' && isset($charge['stored_percentage'])) {
                $charge['percentage'] = $charge['stored_percentage'];
                $charge['calculated_amount'] = ($this->subtotal * $charge['stored_percentage']) / 100;
            } elseif ($charge['type'] === 'fixed' && isset($charge['stored_amount'])) {
                $charge['amount'] = $charge['stored_amount'];
                $charge['calculated_amount'] = $charge['stored_amount'];
            } else {
                $charge['calculated_amount'] = 0;
            }
        }

        $this->items = $invoice->items
            ->map(function ($item) {
                return [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ];
            })
            ->toArray();

        $this->recalculateTotals();
    }

    protected function rulesForStep($step)
    {
        return match ($step) {
            // Step 1 — Invoice Details
            1 => [
                'invoice_number' => ['required', 'string', Rule::unique('invoices', 'invoice_number')->ignore($this->id)],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date'],
            ],
            // Step 2 — Seller
            2 => [
                'seller_name' => ['required', 'string'],
            ],
            // Step 3 — Client
            3 => [
                'client_name' => ['required', 'string'],
            ],
            // Step 4 — Items
            4 => [
                'items' => ['required', 'array', 'min:1'],
                'items.*.description' => ['required', 'string'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            ],
            // Step 5 — Custom Charges
            5 => [
                'custom_charges.*.label' => ['nullable', 'string'],
                'custom_charges.*.type' => ['nullable', 'in:fixed,percentage'],
                'custom_charges.*.amount' => ['nullable', 'numeric', 'min:0'],
                'custom_charges.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ],
            // Step 6 — Totals
            6 => [
                'shipping_total' => ['nullable', 'numeric', 'min:0'],
                'tax_total' => ['nullable', 'numeric', 'min:0'],
            ],

            default => [],
        };
    }

    public function nextStep()
    {
        $this->goToStep($this->step);
        return $this->step++;
    }
    public function goToStep($step)
    {
        $this->validate($this->rulesForStep($step));
        array_push($this->unlockedStep, $step);
        return $this->step = $step;
    }

    public function prevStep()
    {
        $this->goToStep($this->step);
        return $this->step--;
    }

    public function isLastStep()
    {
        return $this->step == 7;
    }

    public function isFirstStep()
    {
        return $this->step == 1;
    }

    public function updatedCode($value)
    {
        $this->insetProductByCode($value);
    }

    function insetProductByCode($value)
    {
        $product = Product::where('code', $value)->first();

        if (!$product) {
            Flux::toast('No product found with the provided code.', variant: 'danger');
            return;
        }

        // 1. Block out-of-stock products immediately
        if ($product->quantity <= 0) {
            Flux::toast("{$product->name} is out of stock.", variant: 'danger');
            return;
        }

        // 2. Check if product already exists in items
        foreach ($this->items as $index => $item) {
            if ($item['id'] === $product->id) {
                // 3. Prevent exceeding stock
                if ($item['quantity'] + 1 > $product->quantity) {
                    Flux::toast("Not enough stock for {$product->name}.", variant: 'danger');
                    return;
                }

                // 4. Increase quantity + recalc line total
                $this->items[$index]['quantity']++;
                $this->items[$index]['line_total'] = $this->items[$index]['quantity'] * $this->items[$index]['unit_price'];
                Flux::toast("{$product->name} quantity increased to. {$this->items[$index]['quantity']}", variant: 'success');
                $this->code = '';
                return;
            }
        }

        // 5. Product not in items → add new line

        $this->addItem(id: $product->id, description: $product->name, price: $product->price);

        $this->code = '';

        $this->dispatch('scroll-to-bottom', container: 'items-container');
    }

    public function addItem($id = null, $qty = 1, $description = '', $price = 0)
    {
        $this->items[] = [
            'id' => $id ?? uniqid('product_'),
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'line_total' => $price * $qty,
        ];

        // Scroll to bottom of items container
        $this->dispatch('scroll-to-bottom', container: 'items-container');
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculateTotals();
    }
    public function removeAllItems()
    {
        $this->items = [];
    }

    public function removeAllCharges()
    {
        $this->custom_charges = [];
    }

    public function addCustomcharge()
    {
        $this->custom_charges[] = [
            'label' => '',
            'type' => 'fixed',
            'amount' => 0,
            'percentage' => 0,
            'calculated_amount' => 0,
        ];

        // Scroll to bottom of custom charges container
        $this->dispatch('scroll-to-bottom', container: 'custom-charges-container');
    }

    public function removeCustomcharge($index)
    {
        unset($this->custom_charges[$index]);
        $this->custom_charges = array_values($this->custom_charges);
        $this->recalculateTotals();
    }

    // Check if seller or client exists based on phone number
    public function updatedSellerPhone($value)
    {
        if (!empty($value)) {
            $this->suggestSellerDetails($value);
        }
    }

    public function updatedClientPhone($value)
    {
        if (!empty($value)) {
            $this->suggestClientDetails($value);
        }
    }

    private function suggestSellerDetails($phone)
    {
        // Find previous invoices with this seller phone
        $existingInvoice = Invoice::where('seller_phone', $phone)
            ->orWhere('seller_phone', 'like', '%' . $phone . '%')
            ->latest()
            ->first();

        if ($existingInvoice && empty($this->seller_name)) {
            $this->seller_name = $existingInvoice->seller_name;
            if (empty($this->seller_address)) {
                $this->seller_address = $existingInvoice->seller_address;
            }
            if (empty($this->seller_tax_id)) {
                $this->seller_tax_id = $existingInvoice->seller_tax_id;
            }

            // Show notification
            Flux::toast('Seller details auto-filled from previous invoice', variant: 'info');
        }
    }

    private function suggestClientDetails($phone)
    {
        // Find previous invoices with this client phone
        $existingInvoice = Invoice::where('client_phone', $phone)
            ->orWhere('client_phone', 'like', '%' . $phone . '%')
            ->latest()
            ->first();

        if ($existingInvoice && empty($this->client_name)) {
            $this->client_name = $existingInvoice->client_name;
            if (empty($this->client_address)) {
                $this->client_address = $existingInvoice->client_address;
            }
            if (empty($this->client_tax_id)) {
                $this->client_tax_id = $existingInvoice->client_tax_id;
            }

            // Show notification
            Flux::toast('Client details auto-filled from previous invoice', variant: 'info');
        }
    }

    public function updatedItems()
    {
        $this->recalculateTotals();
    }

    public function updatedShippingTotal()
    {
        $this->recalculateTotals();
    }

    public function updatedTaxTotal()
    {
        $this->recalculateTotals();
    }

    public function updated($property, $value)
    {
        // Check if any custom charge property was updated
        if (str_starts_with($property, 'custom_charges.')) {
            $this->recalculateTotals();
        }

        switch ($property) {
            case 'subtotal':
            case 'tax_total':
            case 'shipping_total':
            case 'customFeesTotal':
                $this->$property = (float) $value;

                break;
        }
    }

    public function recalculateTotals()
    {
        $subtotal = 0;
        foreach ($this->items as &$item) {
            $lineTotal = (int) $item['quantity'] * (float) $item['unit_price'];
            $item['line_total'] = $lineTotal;
            $subtotal += $lineTotal;
        }
        $this->subtotal = $subtotal;

        // Calculate sum of custom charges
        $customchargesTotal = 0;
        if ($this->custom_charges) {
            foreach ($this->custom_charges as &$charge) {
                if (!empty($charge['label'])) {
                    if ($charge['type'] === 'percentage') {
                        // Calculate percentage of subtotal
                        $percentage = (float) ($charge['percentage'] ?? 0);
                        $calculatedAmount = ($subtotal * $percentage) / 100;
                        $charge['calculated_amount'] = $calculatedAmount;
                        $customchargesTotal += $calculatedAmount;
                    } else {
                        // Fixed amount
                        $amount = (float) ($charge['amount'] ?? 0);
                        $charge['calculated_amount'] = $amount;
                        $customchargesTotal += $amount;
                    }
                } else {
                    $charge['calculated_amount'] = 0;
                }
            }
        }

        $this->total = (float) $this->subtotal + (float) $this->shipping_total + (float) $this->tax_total + (float) $customchargesTotal;
    }

    public function save()
    {
        $this->validate([
            'invoice_number' => ['required', 'string', Rule::unique('invoices', 'invoice_number')->ignore($this->id)],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'seller_name' => ['required', 'string'],
            'client_name' => ['required', 'string'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'shipping_total' => ['numeric', 'min:0'],
            'tax_total' => ['numeric', 'min:0'],
            'custom_charges.*.label' => ['required_with:custom_charges.*.type', 'string'],
            'custom_charges.*.type' => ['required_with:custom_charges.*.label', 'in:fixed,percentage'],
            'custom_charges.*.amount' => ['required_if:custom_charges.*.type,fixed', 'numeric', 'min:0'],
            'custom_charges.*.percentage' => ['required_if:custom_charges.*.type,percentage', 'numeric', 'min:0', 'max:100'],
        ]);

        $invoice = $this->id ? Invoice::find($this->id) : new Invoice();

        $invoice->invoice_number = $this->invoice_number;
        $invoice->invoice_date = $this->invoice_date;
        $invoice->due_date = $this->due_date;
        $invoice->country_code = $this->country_code;
        $invoice->seller_name = $this->seller_name;
        $invoice->seller_phone = $this->seller_phone;
        $invoice->seller_address = $this->seller_address;
        $invoice->seller_tax_id = $this->seller_tax_id;
        $invoice->client_name = $this->client_name;
        $invoice->client_phone = $this->client_phone;
        $invoice->client_address = $this->client_address;
        $invoice->client_tax_id = $this->client_tax_id;
        $invoice->subtotal = $this->subtotal;
        $invoice->shipping_total = $this->shipping_total;
        $invoice->tax_total = $this->tax_total;
        $invoice->total = $this->total;
        $invoice->currency = $this->currency;
        $invoice->status = $this->status;
        $invoice->legal_notes = $this->legal_notes;
        $invoice->tax_meta = $this->tax_meta ? json_encode($this->tax_meta) : null;
        $invoice->paid_at = $this->paid_at;

        // Handle custom charges
        if (!empty($this->custom_charges)) {
            // Remove empty charges
            $validCustomcharges = array_filter($this->custom_charges, function ($charge) {
                return !empty($charge['label']);
            });

            // Store the data for each charge
            $storedcharges = [];
            foreach ($validCustomcharges as $charge) {
                $storedcharge = [
                    'label' => $charge['label'],
                    'type' => $charge['type'],
                ];

                if ($charge['type'] === 'percentage') {
                    $storedcharge['stored_percentage'] = (float) ($charge['percentage'] ?? 0);
                    $storedcharge['stored_amount'] = $charge['calculated_amount'] ?? 0;
                } else {
                    $storedcharge['stored_amount'] = (float) ($charge['amount'] ?? 0);
                }

                $storedcharges[] = $storedcharge;
            }

            $invoice->custom_charges = count($storedcharges) > 0 ? json_encode($storedcharges) : null;
        } else {
            $invoice->custom_charges = null;
        }

        if ($invoice->save()) {
            // Delete existing items and recreate them
            if ($this->id) {
                $invoice->items()->delete();
            }

            foreach ($this->items as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $this->dispatch(
                'toast-show',
                heading: 'Saved successfully',
                text: 'Invoice details have been successfully .',
                variant: 'success',
                actions: [
                    [
                        'label' => 'View',
                        'href' => route('invoices.single', ['id' => $invoice->id]),
                        'type' => 'link',
                    ],
                ],
            );
        }
    }

    function getCurrencySymbol($cur)
    {
        return new Invoice()->getCurrencySymbol($cur);
    }

    function with()
    {
        $products = $this->search
            ? Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%'])
                ->orWhereRaw('LOWER(code) LIKE ?', ['%' . strtolower($this->search) . '%'])
                ->paginate(10)
            : Product::take(4)->get();

        return compact('products');
    }
};
?>





<form wire:submit.prevent="save" {{ $attributes->merge(['class' => 'space-y-6']) }}>

    <flux:heading size="xl">
        {{ $id ? 'Edit Invoice' : 'Create Invoice' }}
    </flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('invoices.index')" wire:navigate>Invoices</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $id ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>


    <div class="container mx-auto px-4 py-8">

        <!-- Timeline Wrapper -->
        <div class="relative">

            <!-- Desktop Progress Line -->
            <div class="hidden md:block absolute left-0 top-5 h-[1px] w-full bg-gray-200  dark:bg-zinc-600"></div>

            <!-- Mobile Progress Line -->
            <div class="md:hidden absolute left-5 top-0 h-full w-[1px] bg-gray-200  dark:bg-zinc-600"></div>

            <!-- Steps -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-8 md:gap-0">

                @foreach ($steps as $key => $s)
                    <div class="relative flex md:flex-col items-center md:items-center z-2 cursor-pointer gap-4 md:gap-0"
                        @if (in_array($key, $this->unlockedStep)) wire:click="goToStep({{ $key }})" @endif>

                        <!-- Step Circle -->
                        <div @class([
                            'w-10 h-10 rounded-full flex items-center justify-center border shrink-0',
                        
                            // Active step
                            'bg-blue-600 text-white border-blue-600' => $key <= $step,
                        
                            // Unlocked but not active
                            'bg-white text-blue-500 border-blue-500' =>
                                in_array($key, $this->unlockedStep) && $key > $step,
                        
                            // Locked step
                            'bg-white text-gray-400 border-gray-300' =>
                                !in_array($key, $this->unlockedStep) && $key > $step,
                        ])>
                            {{ $key }}
                        </div>

                        <!-- Step Label -->
                        <span @class([
                            'text-sm font-medium',
                            'text-gray-400' => !($key <= $step || in_array($key, $this->unlockedStep)),
                            'text-blue-600' => $key <= $step || in_array($key, $this->unlockedStep),
                            'md:mt-2' => true,
                        ])>
                            {{ $s }}
                        </span>

                    </div>
                @endforeach

            </div>
        </div>
    </div>
    <div>

        <!-- Invoice Details -->
        <flux:card class="space-y-4" wire:show="step === 1" wire:transition x-cloak>
            <flux:heading size="lg">Invoice Details</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <flux:input label="Invoice Number" name="invoice_number" wire:model="invoice_number" required />
                <flux:input type="date" label="Invoice Date" name="invoice_date" wire:model="invoice_date"
                    required />
            </div>
            <flux:input type="date" label="Due Date" name="due_date" wire:model="due_date" />
        </flux:card>

        <!-- Seller Information -->
        <flux:card class="space-y-4" wire:show="step === 2" wire:transition x-cloak>
            <flux:heading size="lg">Seller Information</flux:heading>
            <flux:input label="Name" name="seller_name" wire:model="seller_name" required />
            <flux:input label="Phone" name="seller_phone" wire:model.live.debounce.300ms="seller_phone"
                placeholder="Optional - Enter to auto-fill from previous invoices" />
            <flux:textarea label="Address" wire:model="seller_address" name="seller_address" placeholder="Optional" />
            <flux:input label="Tax ID" name="seller_tax_id" wire:model="seller_tax_id" placeholder="Optional" />
        </flux:card>

        <!-- Client Information -->
        <flux:card class="space-y-4" wire:show="step === 3" wire:transition x-cloak>
            <flux:heading size="lg">Client Information</flux:heading>
            <flux:input label="Name" name="client_name" wire:model="client_name" required />
            <flux:input label="Phone" name="client_phone" wire:model.live.debounce.300ms="client_phone"
                placeholder="Optional - Enter to auto-fill from previous invoices" />
            <flux:textarea label="Address" wire:model="client_address" name="client_address" placeholder="Optional" />
            <flux:input label="Tax ID" name="client_tax_id" wire:model="client_tax_id" placeholder="Optional" />
        </flux:card>



        @php
            $itemCount = count($items);
        @endphp

        <!-- Items -->
        <flux:card class="space-y-4" wire:show="step === 4" wire:transition x-cloak>
            <flux:heading size="lg">Items {{ $itemCount ? '(' . $itemCount . ')' : '' }}</flux:heading>
            @if (!count($items))
                <x-error name="items" />
            @endif
            <div id="items-container"
                class="max-h-[60vh] overflow-y-auto space-y-4 divide-y divide-zinc-100 dark:divide-zinc-600 "
                x-data="{
                    scrollToBottom() {
                        const container = this.$el;
                        setTimeout(() => {
                            container.scrollTo({
                                top: container.scrollHeight,
                                behavior: 'smooth'
                            });
                        }, 100);
                    }
                }"
                x-on:scroll-to-bottom.window="
                             if ($event.detail.container === 'items-container') {
                                 scrollToBottom();
                             }
                         ">
                @forelse($items as $index => $item)
                    <div class=" space-y-4 p-4">
                        <div class="flex justify-between items-center">
                            <flux:heading size="sm">Item {{ $index + 1 }}</flux:heading>
                            <flux:button type="button" variant="outline" size="sm"
                                wire:click="removeItem({{ $index }})" class="text-red-600">
                                Remove
                            </flux:button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <flux:input label="Description"
                                    wire:model.live.debounce.300ms="items.{{ $index }}.description" required
                                    name="items.[{{ $index }}].description" />

                                @error("items.$index.description")
                                    <x-error :message="str_replace('items.' . $index . '.description', '', $message)" />
                                @enderror
                            </div>


                            <div>
                                <flux:input type="number" label="Quantity" min="1"
                                    wire:model.live.debounce.500ms="items.{{ $index }}.quantity"
                                    name="items[{{ $index }}][quantity]"
                                    x-bind:disabled="$wire.items[{{ $index }}]?.description.trim().length === 0"
                                    required />
                                @error("items.$index.quantity")
                                    <x-error :message="str_replace('items.' . $index . '.quantity', '', $message)" />
                                @enderror
                            </div>

                            <div>
                                <flux:input type="number" label="Unit Price" step="0.01" min="0"
                                    wire:model.live.debounce.500ms="items.{{ $index }}.unit_price"
                                    name="items[{{ $index }}][unit_price]"
                                    x-bind:disabled="$wire.items[{{ $index }}]?.description.trim().length === 0"
                                    required />
                                @error("items.$index.unit_price")
                                    <x-error :message="str_replace('items.' . $index . '.unit_price', '', $message)" />
                                @enderror
                            </div>
                        </div>

                        <div class="text-right font-semibold">
                            <flux:text> Line Total:
                                {{ $this->getCurrencySymbol($this->currency) }}{{ number_format($item['line_total'], 2) }}
                            </flux:text>
                        </div>
                    </div>
                @empty
                    <flux:text> No items added yet.</flux:text>
                @endforelse
            </div>

            <div x-data="{
                showbarCode: false,
                init() {
                    $watch('showbarCode', value => {
                        if (value) {
                            // Focus the input when shown
                            this.$nextTick(() => {
                                const input = this.$el.querySelector('input');
                                if (input) {
                                    input.focus();
                                }
                            });
                        }
                    });
                }
            }" class="space-y-5">
                <div class="grid grid-cols-1 gap-2 md:gap-6 md:grid-cols-3">
                    <flux:button type="button" variant="outline" wire:click="addItem" icon="plus" class="w-full"
                        @click="showbarCode=false">
                        Add Item
                    </flux:button>
                    <flux:button type="button" variant="outline" icon="arrow-up-tray" class="w-full"
                        @click="showbarCode=false;Flux.modal(`products`).show()">
                        Import
                    </flux:button>

                    <flux:button type="button" variant="outline" icon="queue-list" class="w-full"
                        @click="showbarCode=!showbarCode">
                        <span x-show="!showbarCode">Code</span>
                        <span x-show="showbarCode">Close</span>
                    </flux:button>
                </div>
                <flux:input x-show="showbarCode" x-cloak placeholder="Enter code"
                    wire:model.live.debounce.500ms="code" />


                <flux:modal name="products">
                    <div class=" py-8">
                        <flux:input wire:model.live.debounces.500ms="search" placeholder="search" />
                        @if ($products?->count())
                            <div class=" divide-y divide-zinc-600  divide-black/20 h-80 overflow-y-auto mt-5">
                                @foreach ($products as $product)
                                    <div class=" flex items-center gap-2 py-3">
                                        <flux:button size="xs"
                                            wire:click="insetProductByCode(`{{ $product->code }}`)">
                                            Import
                                        </flux:button>
                                        <flux:avatar size="xs" :src="$product->image_url ?? ''"
                                            :alt="$product->name" :initials="substr($product->name, 0, 2)" />
                                        <flux:text class="truncate">{{ $product->name }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </flux:modal>
            </div>
        </flux:card>




        <!-- Custom charges -->
        <flux:card class="space-y-4" wire:show="step === 5" wire:transition x-cloak>
            @php
                $custom_chargesCount = count($custom_charges);
            @endphp
            <flux:heading size="lg">Custom Charges
                {{ $custom_chargesCount ? '(' . $custom_chargesCount . ')' : '' }}
            </flux:heading>
            <x-error name="custom_charges" />
            <div id="custom-charges-container"
                class="max-h-[60vh] overflow-y-auto space-y-4 divide-y divide-zinc-100 dark:divide-zinc-600 "
                x-data="{
                    scrollToBottom() {
                        const container = this.$el;
                        setTimeout(() => {
                            container.scrollTo({
                                top: container.scrollHeight,
                                behavior: 'smooth'
                            });
                        }, 100);
                    }
                }"
                x-on:scroll-to-bottom.window="
                             if ($event.detail.container === 'custom-charges-container') {
                                 scrollToBottom();
                             }
                         ">
                @if ($custom_charges && $custom_chargesCount > 0)
                    @foreach ($custom_charges as $index => $charge)
                        <div class=" space-y-4 p-4">
                            <div class="flex justify-between items-center">
                                <flux:heading size="sm">Charge {{ $index + 1 }}</flux:heading>
                                <flux:button type="button" variant="outline" size="sm"
                                    wire:click="removeCustomcharge({{ $index }})" class="text-red-600">
                                    Remove
                                </flux:button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                                <div class="md:col-span-2">
                                    <flux:input label="Label"
                                        wire:model.live.debounce.500ms="custom_charges.{{ $index }}.label"
                                        placeholder="e.g., Environmental Fee" required />
                                </div>

                                <div>
                                    <flux:select label="Type"
                                        x-bind:disabled="$wire.custom_charges[{{ $index }}].label.trim().length === 0"
                                        wire:model.live="custom_charges.{{ $index }}.type" required>
                                        <flux:select.option value="fixed">Fixed Amount</flux:select.option>
                                        <flux:select.option value="percentage">Percentage</flux:select.option>
                                    </flux:select>
                                </div>

                                <div>

                                    @if ($charge['type'] === 'percentage')
                                        <flux:input type="number" label="Percentage" step="0.01" min="0"
                                            max="100"
                                            x-bind:disabled="$wire.custom_charges[{{ $index }}].label.trim().length === 0"
                                            wire:model.live.debounce.500ms="custom_charges.{{ $index }}.percentage"
                                            placeholder="0.00" suffix="%" required />
                                    @else
                                        <flux:input type="number" label="Amount" step="0.01" min="0"
                                            x-bind:disabled="$wire.custom_charges[{{ $index }}].label.trim().length === 0"
                                            wire:model.live.debounce.500ms="custom_charges.{{ $index }}.amount"
                                            placeholder="0.00" required />
                                    @endif
                                </div>
                            </div>

                            <div class="text-right font-semibold text-sm">
                                <flux:text> Calculated Amount:
                                    {{ $this->getCurrencySymbol($this->currency) }}{{ number_format($charge['calculated_amount'] ?? 0, 2) }}
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                @else
                    <flux:text>No custom Charges added yet.</flux:text>
                @endif
            </div>

            <flux:button type="button" variant="outline" wire:click="addCustomcharge" icon="plus"
                class="w-full">
                Add Custom Charge
            </flux:button>
        </flux:card>



        <!-- Totals -->
        <flux:card class="space-y-4" wire:show="step === 6" wire:transition x-cloak>
            <flux:heading size="lg">Totals</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <flux:input type="number" label="Shipping" step="0.01" min="0"
                    wire:model.live.debounce.500ms="shipping_total" name="shipping_total" placeholder="Optional" />

                <flux:input type="number" label="Tax" step="0.01" min="0"
                    wire:model.live.debounce.500ms="tax_total" name="tax_total" placeholder="Optional" />
            </div>

            <div class="border-t pt-4 space-y-2">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span
                        class="font-semibold">{{ $this->getCurrencySymbol($this->currency) }}{{ number_format($subtotal, 2) }}</span>
                </div>

                <!-- Display each custom charge -->
                @if ($custom_charges)
                    @foreach ($custom_charges as $charge)
                        @if (!empty($charge['label']))
                            <div class="flex justify-between">
                                <span>{{ $charge['label'] }}:</span>
                                <span>
                                    @if ($charge['type'] === 'percentage')
                                        {{ $this->getCurrencySymbol($this->currency) }}{{ number_format($charge['calculated_amount'] ?? 0, 2) }}
                                        <span class="text-xs text-gray-500">({{ $charge['percentage'] ?? 0 }}%)</span>
                                    @else
                                        {{ $this->getCurrencySymbol($this->currency) }}{{ number_format($charge['calculated_amount'] ?? 0, 2) }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    @endforeach
                @endif

                <div class="flex justify-between">
                    <span>Shipping:</span>
                    <span>{{ $this->getCurrencySymbol($this->currency) }}{{ number_format($shipping_total, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Tax:</span>
                    <span>{{ $this->getCurrencySymbol($this->currency) }}{{ number_format($tax_total, 2) }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t pt-2">
                    <span>Total:</span>
                    <span>{{ $this->getCurrencySymbol($this->currency) }}{{ number_format($total, 2) }}</span>
                </div>
            </div>
        </flux:card>


        <!-- Additional Information -->
        <flux:card class="space-y-4" wire:show="step === 7" wire:transition x-cloak>
            <flux:heading size="lg">Additional Information</flux:heading>
            <flux:textarea label="Legal Notes" wire:model="legal_notes" name="legal_notes" placeholder="Optional" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <flux:select label="Status" wire:model="status" name="status">
                    <flux:select.option value="">Select</flux:select.option>
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="issued">Issued</flux:select.option>
                    <flux:select.option value="paid">Paid</flux:select.option>
                    <flux:select.option value="cancelled">Cancelled</flux:select.option>
                </flux:select>

                <flux:select label="Currency" wire:model="currency" name="currency">
                    <flux:select.option value="">Select</flux:select.option>
                    @foreach ($currencies as $code)
                        <flux:select.option value="{{ $code }}">{{ $code }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

    </div>

    <!-- Save Button -->
    <x-bottom-nav>

        @if (!$this->isLastStep())
            <flux:button class="md:w-auto w-full" wire:click="nextStep" wire:transition>
                Next
            </flux:button>
        @endif
        @if (!$this->isFirstStep())
            <flux:button variant="filled" class="md:w-auto w-full" wire:click="prevStep" wire:transition>
                Back
            </flux:button>
        @endif

        @if ($step == 4 && count($items))
            <flux:button variant="ghost" class="md:w-auto w-full" wire:click="removeAllItems" wire:transition>
                Clear
            </flux:button>
        @endif

        @if ($step == 5 && count($custom_charges))
            <flux:button variant="ghost" class="md:w-auto w-full" wire:click="removeAllCharges" wire:transition>
                Clear
            </flux:button>
        @endif


        @if ($this->isLastStep())
            <flux:button type="submit" variant="primary" class="md:w-auto w-full">
                Save
            </flux:button>
        @endif
    </x-bottom-nav>
</form>
