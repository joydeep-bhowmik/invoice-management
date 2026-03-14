<?php
use function Laravel\Folio\name;
name('roles.edit');
?>
<x-layouts::app :title="__('Roles / Edit')">

    <livewire:role.save :id="request('id')" class="max-w-3xl " />
</x-layouts::app>
