@props([
    'header' => null,
    'sidebar' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $settings = app('aura')::getOption('settings');
        $appSettings = app('aura')::options();
    @endphp

    @php($pageTitle = trim(strip_tags($__env->yieldContent('title'))))

    <title>{{ $pageTitle !== '' ? $pageTitle.' • ' : '' }}{{ $appSettings['app_name'] ?? 'Aura CMS' }}</title>

    @include('aura::components.layout.favicon')

    @livewireStyles

    @auraStyles
</head>
<body class="overflow-hidden antialiased text-gray-900 bg-white dark:bg-gray-900 dark:text-gray-100">

<div
    x-data="aura"
    @keydown.window.slash="$dispatch('search')"
    @keydown.window.prevent.cmd.k="$dispatch('search')"
    @keydown.window.escape="closeSearch()"
    @inset-sidebar.window="insetSidebar(event)"
    class="flex overflow-hidden flex-col items-stretch h-screen md:flex-row"
>


    @if(auth()->check())
        <livewire:aura::navigation/>
    @else
        <x-aura::navigation.guest/>
    @endif


    <div class="flex flex-col flex-grow w-screen h-screen aura-content">

        @if (! app('aura')::assetsAreCurrent() && env('APP_ENV') === 'local')
            <div class="p-4 text-xs text-red-800 bg-red-100">
                The published assets are not up-to-date with the installed version. To update, run:<br/><code
                        class="font-bold">php artisan aura:publish</code>
            </div>
        @endif


        <div class="overflow-y-auto flex-1" style="scrollbar-gutter: stable;">
            <div class="p-5 md:p-8">
                {{ $slot }}
            </div>
            <div class="flex justify-end px-8 pb-8">
                @include('aura::components.layout.aura-version')
            </div>
        </div>
    </div>

    @if($sidebar)
        <x-aura::sidebar>
            {{ $sidebar  }}
        </x-aura::sidebar>
    @endif

    @yield('sidebar')

    @if(config('aura.features.global_search'))
        <livewire:aura::global-search/>
    @endif
</div>

@stack('modals')



<x-aura::notification/>

@if(config('aura.features.notifications'))
<livewire:aura::notifications/>
@endif

@livewire('aura::modals')


@auraScripts
@livewireScripts

</body>
</html>
