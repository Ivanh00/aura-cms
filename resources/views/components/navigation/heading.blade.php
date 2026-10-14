@props([
  'toggled' => false,
  'compact' => false,
])

@php
    $settings = app('aura')::getOption('settings');

@endphp

@php
    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }
@endphp
<div class="px-3 mt-5 mb-1 aura-sidebar-heading">
    <x-aura::tippy text="{{ $slot }}" position="right">
        <div class="py-2 -mt-2 mb-2 h-0 show-collapsed">
            <div class="w-full border-b opacity-40 aura-sidebar-heading-border"></div>
        </div>
    </x-aura::tippy>

    <div class="hide-collapsed">
        <div class="flex justify-between items-center w-full">
            <h5 class="{{ $compact ? 'text-2xs' : 'text-xs' }} font-medium tracking-wider uppercase select-none opacity-80">{{ $slot }}</h5>

            @if ($toggled)
            @else
                <span class="text-sm leading-none opacity-60 select-none">+</span>
            @endif
        </div>
    </div>
</div>
