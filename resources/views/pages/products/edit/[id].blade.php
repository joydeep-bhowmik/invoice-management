<?php
use function Laravel\Folio\name;
name('products.edit');
?>
<x-layouts::app :title="__('Products / Edit')">

    <livewire:product.save :id="request('id')" class="max-w-3xl " />
</x-layouts::app>
