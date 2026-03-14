<div class="flex aspect-square  items-center justify-center rounded-md bg-accent-content text-accent-foreground">
    <x-app-logo-icon class="fill-current text-white dark:text-black" />
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span
        class="mb-0.5 truncate leading-tight font-semibold dark:text-white">{{ $brandingCompany?->name ?? config('app.name') }}</span>
</div>
