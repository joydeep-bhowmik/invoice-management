<?php
// resources/views/livewire/notes.blade.php
use Flux\Flux;
use App\Models\Note;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Auth;
use function Laravel\Folio\{name, middleware};

name('notes.index');
middleware(['auth', 'verified']);

new class extends Component {
    use WithPagination;
    public $page = 1;
    public $hasMorePages = true;

    function loadNextPage()
    {
        $this->page++;
    }
    #[Renderless]
    public function deleteNote($id)
    {
        // Only allow deleting notes that belong to the current user
        $note = Note::where('user_id', Auth::id())->findOrFail($id);
        $note->delete();
        $this->dispatch('note-deleted', id: $note->id);
        Flux::toast("Note '{$note->title}' deleted!", variant: 'success');
    }

    function with()
    {
        $notes = Note::where('user_id', Auth::id())->latest()->paginate(10, page: $this->page);

        $this->hasMorePages = $notes->hasMorePages();

        return compact('notes');
    }
};

?>
<x-layouts::app :title="__('My Notes')">
    <x-mobile-nav />
    @volt('notes.index')
        <div>
            <flux:heading size="xl">My Notes</flux:heading>
            <flux:subheading size="lg" class="mb-6">
                Manage your personal notes. Only you can see and edit your notes.
            </flux:subheading>

            <flux:button variant="primary" :href="route('notes.create')" icon="plus" wire:navigate>Create </flux:button>


            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-5">
                @island('notes')
                    @forelse($notes as $note)
                        <flux:card x-data @note-deleted.window="if ($event.detail.id === {{ $note['id'] }}) { $el.remove(); }"
                            wire:transition>
                            <div class="flex justify-between items-start mb-4" x-ref="div">
                                <div @click="Flux.modal(`view-note-{{ $note['id'] }}`).show()" class=" cursor-pointer">
                                    <flux:heading size="lg">{{ $note['title'] }}</flux:heading>
                                    <flux:subheading> Created:
                                        {{ \Carbon\Carbon::parse($note['created_at'])->format('M d, Y') }}
                                    </flux:subheading>
                                </div>

                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom">
                                    </flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="pencil"
                                            @click="Livewire.navigate(`{{ route('notes.edit', ['id' => $note['id']]) }}`)">
                                            Edit
                                        </flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger"
                                            @click="Flux.modal(`delete-note-{{ $note['id'] }}`).show()">
                                            Delete

                                        </flux:menu.item>

                                    </flux:menu>
                                </flux:dropdown>


                            </div>

                            <flux:modal.trigger name="view-note-{{ $note['id'] }}" class="cursor-pointer">
                                <flux:text>
                                    {{ Str::limit($note['content'], 150) }}
                                </flux:text>
                            </flux:modal.trigger>

                            <!-- View Note Modal -->
                            <flux:modal name="view-note-{{ $note['id'] }}" class="md:w-96 space-y-5">
                                <flux:heading size="lg">{{ $note['title'] }}</flux:heading>
                                <flux:subheading>
                                    Created:
                                    {{ \Carbon\Carbon::parse($note['created_at'])->format('F d, Y \a\t h:i A') }}
                                </flux:subheading>
                                <flux:text>{{ $note['content'] }}</flux:text>
                            </flux:modal>

                            <!-- Delete Confirmation Modal -->
                            <flux:modal name="delete-note-{{ $note['id'] }}">
                                <flux:heading size="lg">Delete Note?</flux:heading>
                                <flux:text class="mt-2">This action cannot be undone.</flux:text>

                                <div class="flex gap-2 mt-6">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>

                                    <flux:button variant="danger" wire:click="deleteNote({{ $note['id'] }})">
                                        Delete
                                    </flux:button>
                                </div>
                            </flux:modal>
                        </flux:card>
                    @empty
                        <div class="col-span-full text-center p-12">

                            @if ($notes->onFirstPage())
                                <flux:icon name="document-text" class="w-10 h-10 text-gray-400 mx-auto mb-4" />
                                <p class="text-gray-500">No notes created yet. Create your first note!</p>
                            @else
                                <p class="text-gray-500">No more notes to load.</p>
                            @endif

                        </div>
                    @endforelse
                @endisland
            </div>




            <div x-data x-show="$wire.hasMorePages" x-cloak style="display: none" wire:intersect="loadNextPage"
                wire:island.append="notes" class="h-24 grid place-items-center w-full ">
                <flux:icon.loading class="mx-auto mt-6" wire:loading />
            </div>
        </div>
    @endvolt
</x-layouts::app>
