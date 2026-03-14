<?php
use function Laravel\Folio\name;
name('warehouses.edit');
?>
<x-layouts::app :title="__('Warehouses / Edit')">

    <livewire:warehouse.save :id="request('id')" class="max-w-3xl " />
</x-layouts::app>
