<?php

use Flux\Flux;
use App\Models\Company;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
    public $name;
    public $logo;

    public function mount()
    {
        if ($company = Company::find(1)) {
            $this->name = $company->name;
        }
    }
    public function save()
    {
        $company = Company::find(1) ?? new Company();

        $this->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:1024',
        ]);

        $company->name = $this->name;

        $company->owner_id = auth()->id();

        if (!$company->save()) {
            return;
        }
        if ($this->logo) {
            $company->clearMediaCollection('logos');
            $company->addMedia($this->logo)->toMediaCollection('logos');
            $this->reset('logo');
        }
        $this->dispatch('company-saved');
        Flux::toast('Company updated', variant: 'success');
    }

    function with()
    {
        return [
            'company' => Company::find(1),
        ];
    }
};
?>
<form wire:submit="save" {{ $attributes->merge(['class' => 'space-y-5']) }}>

    <flux:input label="Name" wire:model="name" />

    <flux:input type="file" accept="image/*" label="Logo" wire:model="logo" />
    @php
        $photo = null;
        if ($logo) {
            $photo = $logo->temporaryUrl();
        } elseif ($company) {
            $photo = $company->getFirstMediaUrl('logos');
        }
    @endphp
    @if ($photo)
        <img src="{{ $photo }}" alt="" class=" h-32">
    @endif
    <flux:button type="submit" variant="primary">
        Save
    </flux:button>
</form>
