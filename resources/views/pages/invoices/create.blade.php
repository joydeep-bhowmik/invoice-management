<?php
use function Laravel\Folio\name;
name('invoices.create');
?>
<x-layouts::app :title="__('Invoices / create')">

    <livewire:invoice.save />
</x-layouts::app>
