@props([
  'permission' => false,
  'id' => null,
  'route'=> null,
  'href' => null,
  'compact' => false,
  'block' => false,
  'size' => 'base',
  'class' => 'text-white bg-primary-600 hover:bg-primary-500 active:bg-primary-700 focus-visible:ring-primary-500',
  'type' => 'button',
  'navigate' => true,
  'disabled' => false,
])

@php

if ($block) {
  $class = 'flex w-full' . ' ' . $class;
} else {
  $class = 'inline-flex' . ' ' . $class;
}

$sizes = [
  'icon-xs' => 'gap-1 p-1 text-xs font-medium rounded-md',
  'xs' => 'gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md',
  'sm' => 'gap-1.5 px-3 py-1.5 text-sm leading-5 font-medium rounded-lg',
  'base' => 'gap-2 px-4 py-2 text-sm font-medium rounded-lg',
  'lg' => 'gap-2 px-4 py-2.5 text-base font-medium rounded-lg',
  'xl' => 'gap-2.5 px-5 py-3 text-lg font-medium rounded-xl',
];
$iconSizes = [
  'icon-xs' => 'w-4',
  'xs' => 'w-4',
  'sm' => 'w-4',
  'base' => 'w-5',
  'lg' => 'w-5',
  'xl' => 'w-6',
];

if (!isset($sizes[$size])) {
  $size = 'base';
}

$disabledClass = $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
$class .= ' ' . $disabledClass;

$sharedClass = ' aura-button relative items-center justify-center whitespace-nowrap select-none transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 ';

@endphp


@if(isset($href))
<a
  @if(isset($id)) id="{{ $id }}" @endif
  tabindex="{{ $disabled ? '-1' : '0' }}"
  href="{{ $disabled ? '#' : $href }}"
  @if($navigate && !$disabled) wire:navigate @endif
  {{$attributes->merge([
    'class' => $class . $sharedClass . $sizes[$size],
  ])}}
  @if($disabled) aria-disabled="true" @endif
>
  @if ($icon ?? false)
    <span class="inline-flex items-center justify-center shrink-0 -ml-0.5 {{ $iconSizes[$size] }}">
      {{ $icon }}
    </span>
  @endif
  {{ $slot }}
</a>
@else
<button
  @if(isset($id)) id="{{ $id }}" @endif
  tabindex="{{ $disabled ? '-1' : ($attributes->has('tabindex') ? $attributes->get('tabindex') : '0') }}"
  type="{{ $type }}"
  {{$attributes->merge([
    'class' => $class . $sharedClass . $sizes[$size],
  ])}}
  @if($disabled) disabled aria-disabled="true" @endif
>
  @if ($icon ?? false)
    <span class="inline-flex items-center justify-center shrink-0 -ml-0.5 {{ $iconSizes[$size] }}">
      {{ $icon }}
    </span>
  @endif
  {{ $slot }}
</button>
@endif
