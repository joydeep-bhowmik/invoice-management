<?php
use function Laravel\Folio\name;

name('artisan');
?>
<x-layouts::app :title="__('Products / All')">



    <div class=" h-[90vh] overflow-y-auto  p-5" x-data
        @terminal-command-processed.window="$el.scroll({ top: $el.scrollHeight }); console.log('x')">
        <livewire:dev.artisan />
    </div>
</x-layouts::app>
