<?php

use Flux\Flux;
use App\Models\Company;
use Livewire\Component;

new class extends Component {
    public $newOwnerId;

    public function transferOwnership()
    {
        $company = Company::find(1);

        if (auth()->id() !== $company->owner_id) {
            abort(403);
        }

        $this->validate([
            'newOwnerId' => 'required|exists:users,id|different:company.owner_id',
        ]);

        $company->update([
            'owner_id' => $this->newOwnerId,
        ]);

        $this->newOwnerId = null;

        Flux::modal('transfer-owner-confirm')->close();

        Flux::toast('Ownership transferred', variant: 'success');
    }
};
?>

<form wire:submit="transferOwnership" {{ $attributes->merge(['class' => 'space-y-5']) }}>

    <flux:select label="Transfer Ownership" wire:model="newOwnerId">
        <option value="">Select</option>
        @foreach (\App\Models\User::whereNot('id', auth()->id())->get() as $user)
            <option value="{{ $user->id }}">{{ $user->name }}</option>
        @endforeach
    </flux:select>

    <flux:modal name="transfer-owner-confirm" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Transfer ownership?</flux:heading>
                <flux:text class="mt-2">
                    You're about to Transfer the Transfer.<br>
                    This action cannot be reversed.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">
                    Transfer
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal.trigger name="transfer-owner-confirm">
        <flux:button variant="danger" x-bind:disabled="!$wire.newOwnerId">
            Transfer Ownership
        </flux:button>
    </flux:modal.trigger>
</form>
