<?php

use Flux\Flux;
use App\Models\Note;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $id;
    public $title;
    public $content;

    public function mount($id = null)
    {
        if (!$id) {
            return;
        }

        $note = Note::where('user_id', Auth::id())->find($id);
        if (!$note) {
            abort(404);
        }

        $this->id = $note->id;
        $this->title = $note->title;
        $this->content = $note->content;
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|min:2|max:255',
            'content' => 'required|string|min:2',
        ]);

        $note = $this->id ? Note::where('user_id', Auth::id())->findOrFail($this->id) : new Note();

        $note->title = $this->title;
        $note->content = $this->content;
        $note->user_id = Auth::id();
        $note->save();

        $this->dispatch(
            'toast-show',
            heading: 'Saved successfully',
            text: $this->id ? 'Note updated successfully.' : 'Note created successfully.',
            variant: 'success',
            actions: [
                [
                    'label' => 'View All Notes',
                    'href' => route('notes.index'),
                    'type' => 'link',
                ],
            ],
        );

        // Reset form if creating new
        if (!$this->id) {
            $this->resetForm();
        }
    }

    public function resetForm()
    {
        $this->id = null;
        $this->title = '';
        $this->content = '';
    }
};
?>

<form wire:submit.prevent="save" {{ $attributes->merge(['class' => 'space-y-6']) }}>

    <flux:heading size="xl">
        {{ $id ? 'Edit Note' : 'Create Note' }}
    </flux:heading>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate></flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('notes.index')" wire:navigate>Notes</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $id ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    <flux:input label="Title" wire:model="title" required placeholder="Note title" />
    @error('title')
        <flux:error>{{ $message }}</flux:error>
    @enderror

    <flux:textarea label="Content" wire:model="content" required placeholder="Write your note here..." rows="auto"
        class="max-h-80 min-h-32" />
    @error('content')
        <flux:error>{{ $message }}</flux:error>
    @enderror

    <x-bottom-nav>
        <flux:button type="submit" variant="primary" class="md:w-auto w-full">
            Save
        </flux:button>
    </x-bottom-nav>
</form>
