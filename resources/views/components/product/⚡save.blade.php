<?php

use Flux\Flux;
use App\Models\Product;
use Livewire\Component;
use Milon\Barcode\DNS1D;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public $id;
    public $name;
    public $slug;
    public $description;
    public $price;
    public $sku;
    public $code;
    public $warehouse_id;
    public $quantity;
    public $product_code;

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:products,slug',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'quantity' => 'requried',
        'sku' => 'nullable|string|max:255',
        'code' => 'nullable|string|max:255',
        'warehouse_id' => 'required|exists:warehouses,id',
    ];

    public function mount($id = null)
    {
        if (!$id) {
            return;
        }

        $product = Product::find($id);
        if (!$product) {
            abort(404);
        }

        $this->id = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->sku = $product->sku;
        $this->code = $product->code;
        $this->warehouse_id = $product->warehouse_id;
        $this->quantity = $product->quantity;
        $this->product_code = $product->code;
    }
    #[Renderless]
    function generateSvg()
    {
        $product = Product::find($this->id);

        if (!$product) {
            return null; // or throw an exception
        }

        $code = '4445645656';
        $type = 'C39+';
        $scale = 3;
        $height = 33;
        $d = new DNS1D();
        // Generate barcode in base64
        $barcodeBase64 = $d->getBarcodePNG($product->code, $type, $scale, $height);

        // Decode base64 to binary
        $barcodeBinary = base64_decode($barcodeBase64);

        // Create a filename
        $filename = 'barcodes/' . $code . '.png';

        // Save file to public disk
        Storage::disk('public')->put($filename, $barcodeBinary);

        // Return public URL to the file
        return Storage::disk('public')->url($filename);
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $this->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required',
            'sku' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $product = $this->id ? Product::find($this->id) : new Product();

        $product->name = $this->name;
        $product->slug = $this->slug ?: Str::slug($this->name);
        $product->description = $this->description;
        $product->price = $this->price;
        $product->sku = $this->sku;
        $product->code = $this->code;
        $product->warehouse_id = $this->warehouse_id;
        $product->quantity = $this->quantity;
        $this->product_code = $product->code;
        if ($product->save()) {
            $this->dispatch(
                'toast-show',
                heading: 'Saved successfully',
                text: 'Product details have been successfully .',
                variant: 'success',
                actions: [
                    [
                        'label' => 'View',
                        'href' => route('products.index', ['id' => $product->id]),
                        'type' => 'link',
                    ],
                ],
            );
        }
    }
};
?>







<form wire:submit.prevent="save" x-data="{
    handleNameChange($event) {
            const value = $event.target.value;
            $refs.slug.value = value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '') // remove invalid chars
                .trim()
                .replace(/\s+/g, '-') // spaces → dashes
                .replace(/-+/g, '-');
            $refs.slug.dispatchEvent(new Event('input', { bubbles: true }))
        },
        encodeSvg(svg) {
            return btoa(
                new TextEncoder().encode(svg)
                .reduce((data, byte) => data + String.fromCharCode(byte), '')
            );
        },
        async printSvg() {
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';


            iframe.src = await $wire.generateSvg();

            console.log(iframe.src);

            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            };

            document.body.appendChild(iframe);
        }

}" {{ $attributes->merge(['class' => 'space-y-6']) }}>
    <flux:heading size="xl">
        {{ $id ? 'Edit Product' : 'Create Product' }}
    </flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('products.index')" wire:navigate>Products</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $id ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <flux:input label="Name" name="name" wire:model="name" required @input="handleNameChange" />

        <flux:input label="Slug" name="slug" x-ref="slug" wire:model="slug"
            hint="Automatically generated from name" />
    </div>

    <flux:textarea label="Description" wire:model="description" name="description" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <flux:input type="number" step="0.01" label="Price" name="price" wire:model="price" required />

        <flux:input type="number" step="1" label="Quantity" wire:model="quantity" name="quantity" required />

    </div>
    <flux:input label="SKU" name="sku"
        description="Optional. Enter a Stock Keeping Unit to identify this product internally." wire:model="sku" />

    <flux:input label="Code" name="code" wire:model.live="code"
        description="Optional. Enter a barcode or product code for scanning and inventory purposes." />



    <flux:select label="Warehouse" wire:model="warehouse_id" name="warehouse_id" required>
        <option value="">Select warehouse</option>
        @foreach (\App\Models\Warehouse::all() as $warehouse)
            <option value="{{ $warehouse->id }}">
                {{ $warehouse->name }}
            </option>
        @endforeach
    </flux:select>

    <x-bottom-nav>

        @if ($id && $code === $product_code)
            <flux:button icon="printer" @click="printSvg" class="md:w-auto w-full" wire:transition
                wire:loading.attr="disabled">Print Barcode
            </flux:button>
        @endif
        <flux:button type="submit" variant="primary" class="md:w-auto w-full" wire:transition
            wire:loading.attr="disabled">
            Save
        </flux:button>
    </x-bottom-nav>

</form>
