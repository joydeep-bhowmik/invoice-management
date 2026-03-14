@props([
    'sortable' => false,
    'direction' => null,
])

<th scope="col" {{ $attributes->class(['px-6 py-3']) }}>
    @if ($sortable)
        <button type="button" class="flex items-center gap-1">
            {{ $slot }}
            @if ($direction)
                <flux:icon name="{{ $direction === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-4 h-4" />
            @endif
        </button>
    @else
        {{ $slot }}
    @endif
</th>
