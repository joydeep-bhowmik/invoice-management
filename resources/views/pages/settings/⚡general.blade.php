<?php

use App\Models\Setting;
use Livewire\Component;

new class extends Component {
    public $settings = [];

    function mount() {}
    function save() {}
};
?>
<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">General Settings</flux:heading>

    <x-settings.layout :heading="__('General')" :subheading="__('Update the General for your company')">
        <form action="" wire:submit="save">
            <flux:switch wire:model.live="allow_manual_invoice_edit" label="Allow manual edits to invoice items" />
        </form>
    </x-settings.layout>
</section>
