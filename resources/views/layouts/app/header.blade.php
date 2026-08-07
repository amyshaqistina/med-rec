<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="building-office-2" :href="route('dashboard')" :current="request()->routeIs('dashboard') || request()->routeIs('wards.*')" wire:navigate>
                    {{ __('Wards') }}
                </flux:navbar.item>
                <flux:navbar.item icon="users" :href="route('patients.index')" :current="request()->routeIs('patients.*')" wire:navigate>
                    {{ __('Patients') }}
                </flux:navbar.item>
                <flux:navbar.item icon="clipboard-document-check" :href="route('reconciliations.index')" :current="request()->routeIs('reconciliations.*')" wire:navigate>
                    {{ __('Reconciliation Worklist') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="building-office-2" :href="route('dashboard')" :current="request()->routeIs('dashboard') || request()->routeIs('wards.*')" wire:navigate>
                        {{ __('Wards') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('patients.index')" :current="request()->routeIs('patients.*')" wire:navigate>
                        {{ __('Patients') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('reconciliations.index')" :current="request()->routeIs('reconciliations.*')" wire:navigate>
                        {{ __('Reconciliation Worklist') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
