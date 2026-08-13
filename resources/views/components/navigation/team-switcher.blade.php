@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false,
  'title' => ''
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


<div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }"
     class="w-full">

    <div x-data="{
      open: false,
      init() {
        // Tippy is attached on window by the package app.js entry. Alpine may
        // boot before that script finishes evaluating under slow/parallel
        // asset loads — wait for window.tippy instead of throwing ReferenceError.
        this.$nextTick(() => {
          const mount = (attempts = 0) => {
            const tippyFn = window.tippy;

            if (typeof tippyFn !== 'function') {
              if (attempts < 50) {
                setTimeout(() => mount(attempts + 1), 20);
              }

              return;
            }

            tippyFn(this.$refs.this, {
              arrow: false,
              theme: 'aura-small',
              trigger: 'click',
              offset: [0, 8],
              placement: 'top-start',
              content: @js((string)$slot),
              allowHTML: true,
              interactive: true,
              onShow: (instance) => {
                this.open = true;
              },
              onHide: (instance) => {
                this.open = false;
              },
            });

            this.$refs.this.addEventListener('keydown', (event) => {
              if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                this.$refs.this.click();
              }
            });
          };

          mount();
        });
      }
    }">
        <div x-ref="this" role="button" tabindex="0" class="flex justify-between items-center px-2 py-1.5 w-full text-sm font-medium rounded-lg cursor-pointer aura-sidebar-team-switcher">
            <span class="min-w-0 flex-1">{{ $title }}</span>

            <div class="ml-2 opacity-50 hide-collapsed">
                <!-- svg chevron up down -->
                <x-aura::icon.chevron-up class="w-4 h-4"/>
            </div>
        </div>

    </div>

</div>
