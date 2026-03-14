<?php
use function Laravel\Folio\name;
name('products.create');
?>
<x-layouts::app :title="__('Products / create')">

    <livewire:product.save class="max-w-3xl " />
</x-layouts::app>
