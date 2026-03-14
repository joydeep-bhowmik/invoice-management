<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="container mb-20">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
