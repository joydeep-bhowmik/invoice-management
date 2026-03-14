<?php

use Livewire\Component;

new class extends Component {};
?>
<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Company Settings</flux:heading>

    <x-settings.layout :heading="__('Company')" :subheading="__('Update the settings for your company')">
        <livewire:company.save />
    </x-settings.layout>
</section>
