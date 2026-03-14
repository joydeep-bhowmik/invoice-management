<?php
use function Laravel\Folio\name;
name('warehouses.create');
?>
<x-layouts::app :title="__('Warehouses/ create')">

    <livewire:warehouse.save class="max-w-3xl " />
</x-layouts::app>
