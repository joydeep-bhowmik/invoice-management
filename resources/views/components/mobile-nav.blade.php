<div
    class="grid grid-cols-4  place-items-center gap-5 fixed bottom-0 left-0 right-0 bg-gray-100 dark:bg-zinc-800 py-4 border-t dark:border-zinc-600 z-10 md:hidden">
    <flux:button icon="home" :href="route('dashboard')"
        :variant="request()->routeIs('dashboard') ? 'primary' : 'ghost'" wire:navigate x-transition />

    <flux:button icon="cube" :href="route('products.index')"
        :variant="request()->routeIs('products.index') ? 'primary' : 'ghost'" wire:navigate x-transition />

    <flux:button icon="document-currency-rupee" :href="route('invoices.index')"
        :variant="request()->routeIs('invoices.index') ? 'primary' : 'ghost'" wire:navigate x-transition />

    <flux:button icon="cog-6-tooth" :href="route('profile.edit')"
        :variant="request()->routeIs('profile.edit') ? 'primary' : 'ghost'" wire:navigate x-transition />
</div>
