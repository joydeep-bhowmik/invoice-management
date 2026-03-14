<?php

use Flux\Flux;
use App\Models\User;
use Livewire\Component;
use App\Models\Warehouse;

new class extends Component {
    public $id;
    public $name;
    public $description;
    public $manager_id;

    public function mount($id = null)
    {
        if (!$id) {
            return;
        }
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            abort(404);
        }
        $this->id = $warehouse->id;
        $this->name = $warehouse->name;
        $this->description = $warehouse->description;
        $this->manager_id = $warehouse->manager_id;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:warehouses,name,' . $this->id,
            'description' => 'required|string',
            'manager_id' => 'required|exists:users,id',
        ]);

        $warehouse = $this->id ? Warehouse::find($this->id) : new Warehouse();
        $warehouse->name = $this->name;
        $warehouse->description = $this->description;
        $warehouse->manager_id = $this->manager_id;

        if ($warehouse->save()) {
            $this->dispatch(
                'toast-show',
                heading: 'Saved successfully',
                text: 'Warehouse details have been successfully .',
                variant: 'success',
                actions: [
                    [
                        'label' => 'View',
                        'href' => route('warehouses.index', ['id' => $warehouse->id]),
                        'type' => 'link',
                    ],
                ],
            );
        }
    }
};
?>



<form wire:submit.prevent="save" {{ $attributes->merge(['class' => 'space-y-6']) }}>
    <flux:heading size="xl">
        {{ $id ? 'Edit Warehouse' : 'Create Warehouse' }}
    </flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('warehouses.index')" wire:navigate>Warehouses</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $id ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    <flux:input label="Name" wire:model="name" required placeholder="e.g., Main Warehouse, West Coast Distribution" />

    <flux:textarea label="Description" wire:model="description" required
        placeholder="Describe the warehouse location, purpose, or special notes" rows="4" />

    <flux:select label="Manager" wire:model="manager_id" required>
        <option value="">Select a manager</option>
        @foreach (User::all() as $user)
            <option value="{{ $user->id }}">
                {{ $user->name }} ({{ $user->email }})
            </option>
        @endforeach
    </flux:select>

    <x-bottom-nav>
        <flux:button type="submit" variant="primary" class="md:w-auto w-full">
            Save
        </flux:button>
    </x-bottom-nav>
</form>
