<?php
use function Laravel\Folio\{name, middleware};

name('roles.create');
middleware(['auth', 'verified']);

?>
<x-layouts::app :title="__('Roles / Create')">

    <livewire:role.save class="max-w-3xl " />
</x-layouts::app>
