<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>
@php
    $superadmin = auth()->user()->isSuperAdmin();
@endphp

<body class="min-h-screen  bg-gray-100 dark:bg-zinc-800">
    <flux:sidebar sticky stashable
        class="border-e border-zinc-200  bg-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">

            <flux:navlist.item icon="home" class="mb-2" :href="route('dashboard')" wire:navigate cla>
                {{ __('Dashboard') }}
            </flux:navlist.item>

            @canany(['manage_products', 'manage_invoices', 'manage_warehouses'])
                <flux:navlist.group :heading="__('Operations')" expandable>
                    @can('manage_products')
                        <flux:navlist.item :href="route('products.index')" icon="cube" wire:navigate>Products
                        </flux:navlist.item>
                    @endcan
                    @can('manage_invoices')
                        <flux:navlist.item :href="route('invoices.index')" icon="document-currency-rupee" wire:navigate>Invoices
                        </flux:navlist.item>
                    @endcan

                    @can('manage_warehouses')
                        <flux:navlist.item :href="route('warehouses.index')" icon="home-modern" wire:navigate>Warehouses
                        </flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            @endcanany


            <flux:navlist.group :heading="__('User')" expandable>
                <flux:navlist.item icon="document" :href="route('notes.index')" wire:navigate>{{ __('Notes') }}
                </flux:navlist.item>
            </flux:navlist.group>

            @if ($superadmin)
                <flux:navlist.group :heading="__('Admin')" expandable>
                    <flux:navlist.item icon="user-circle" :href="route('roles.index')" wire:navigate>{{ __('Roles') }}
                    </flux:navlist.item>

                    <flux:navlist.item icon="user" :href="route('users.index')" wire:navigate>{{ __('Users') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @endif

            @can('access_dev')
                @if ($superadmin)
                    <flux:navlist.group :heading="__('Dev')" expandable>
                        <flux:navlist.item icon="command-line" :href="route('artisan')" wire:navigate>
                            {{ __('Terminal') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                @endif
            @endcan
        </flux:navlist>

        <flux:spacer />


        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" data-test="sidebar-menu-button" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full"
                        data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden sticky top-0 bg-white border-b dark:bg-zinc-800  dark:border-zinc-600">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts

    @persist('toast')
        <flux:toast />
    @endpersist
</body>

</html>
