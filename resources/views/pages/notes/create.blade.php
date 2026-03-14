<?php
use function Laravel\Folio\name;
name('notes.create');
?>
<x-layouts::app :title="__('Notes / Create')">

    <livewire:note.save class="max-w-3xl " />
</x-layouts::app>
