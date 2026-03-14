<?php

use function Laravel\Folio\{name, middleware};

name('users.edit');
middleware(['auth', 'verified']);

?>
<x-layouts::app :title="__('Users / Edit')">

    <livewire:user.save :id="request('id')" />
</x-layouts::app>
