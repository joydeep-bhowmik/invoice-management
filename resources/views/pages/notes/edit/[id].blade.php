<?php
use function Laravel\Folio\name;
name('notes.edit');
?>
<x-layouts::app :title="__('Notes / Edit')">

    <livewire:note.save :id="request('id')" class="max-w-3xl " />
</x-layouts::app>
