<?php

use function Laravel\Folio\name;

name('dashboard');

?>

<x-layouts::app :title="__('Dashboard')">
    <flux:heading size="xl">
        Dashboard
    </flux:heading>

    <x-mobile-nav />

    <div class="space-y-6 mt-5">
        <div class="flex items-center gap-5">
            <div>
                <flux:heading size="lg" class="mb-4">
                    Quick Actions
                </flux:heading>
                <flux:subheading>
                    Get started by creating products or warehouses.
                </flux:subheading>
            </div>

        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:button href="{{ route('products.create') }}" icon="plus" wire:navigate>
                Create Product
            </flux:button>

            <flux:button href="{{ route('products.index') }}" icon="eye" wire:navigate>
                View Products
            </flux:button>

            <flux:button href="{{ route('warehouses.create') }}" icon="plus" wire:navigate>
                Create Warehouse
            </flux:button>

            <flux:button href="{{ route('warehouses.index') }}" icon="eye" wire:navigate>
                View Warehouses
            </flux:button>
        </div>
        @can('view_stats')
            <livewire:dashboard.stats />
        @endcan
    </div>
</x-layouts::app>
