<?php
use function Laravel\Folio\name;
name('invoices.edit');
?>
<x-layouts::app :title="__('Invoices / Edit')">

    <livewire:invoice.save :id="request('id')" />
</x-layouts::app>
