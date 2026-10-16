<div>
    @section('title', 'Edit Settings')

    <div class="mb-6">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
            @if($page === 'general')
                <x-aura::breadcrumbs.li title="Settings" />
            @else
                <x-aura::breadcrumbs.li :href="route('aura.settings')" title="Settings" />
                <x-aura::breadcrumbs.li :title="$this->settingsPage()->title" />
            @endif
        </x-aura::breadcrumbs>
    </div>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="mb-2 text-2xl font-semibold">{{ $page === 'general' ? __('Settings') : __($this->settingsPage()->title) }}</h1>
            @if(filled($this->settingsPage()->description))
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($this->settingsPage()->description) }}</p>
            @endif
        </div>

        <div class="flex gap-2 items-center">
            @if($page === 'general' && $this->hasAppearanceChanges)
                <span class="text-xs font-medium text-amber-700 dark:text-amber-300">
                    {{ __('Unsaved changes') }}
                </span>
                <x-aura::button.border size="sm" wire:click="discardAppearanceChanges">
                    {{ __('Discard changes') }}
                </x-aura::button.border>
            @endif
            <x-aura::button wire:click="save">
                <div wire:loading wire:target="save">
                    <x-aura::icon.loading  />
                </div>
                {{ __('Save') }}
            </x-aura::button>
        </div>
    </div>

    @if($page === 'general')
        @php
            $previewSettings = $form['fields'];
            $appearanceState = [
                'darkmode' => $previewSettings['darkmode-type'] ?? 'auto',
                'sidebarType' => $previewSettings['sidebar-type'] ?? 'dark',
                'sidebarDarkmodeType' => $previewSettings['sidebar-darkmode-type'] ?? 'dark',
                'sidebarSize' => $previewSettings['sidebar-size'] ?? 'standard',
            ];
            $savedAppearanceState = [
                'darkmode' => $savedAppearance['darkmode-type'] ?? 'auto',
                'sidebarType' => $savedAppearance['sidebar-type'] ?? 'dark',
                'sidebarDarkmodeType' => $savedAppearance['sidebar-darkmode-type'] ?? 'dark',
                'sidebarSize' => $savedAppearance['sidebar-size'] ?? 'standard',
            ];
            $appearanceStateKey = sha1(json_encode([$appearanceState, $savedAppearanceState]));
        @endphp

        @include('aura::components.layout.colors', [
            'settings' => $previewSettings,
            'colorScope' => ':root',
            'darkColorScope' => '.dark',
            'includeColorHelpers' => false,
        ])

        <div
            wire:key="appearance-page-preview-{{ $appearanceStateKey }}"
            data-testid="appearance-page-preview"
            data-preview-sidebar-size="{{ $appearanceState['sidebarSize'] }}"
            data-preview-sidebar-darkmode-type="{{ $appearanceState['sidebarDarkmodeType'] }}"
            data-preview-interface-density="{{ $previewSettings['interface-density'] ?? 'comfortable' }}"
            data-preview-corner-radius="{{ $previewSettings['corner-radius'] ?? 'rounded' }}"
            class="hidden"
            x-data="{
                preview: @js($appearanceState),
                saved: @js($savedAppearanceState),
                init() { this.apply(this.preview) },
                destroy() { this.apply(this.saved) },
                apply(settings) {
                    const root = document.documentElement
                    const sidebar = document.querySelector('.aura-sidebar')
                    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false
                    const dark = settings.darkmode === 'dark' || (settings.darkmode === 'auto' && prefersDark)

                    root.classList.toggle('dark', dark)
                    root.classList.toggle('light', !dark)

                    if (! sidebar) return

                    Array.from(sidebar.classList)
                        .filter(name => name.startsWith('aura-sidebar-size-')
                            || name.startsWith('aura-sidebar-type-')
                            || name.startsWith('aura-darkmode-type-')
                            || name.startsWith('aura-sidebar-darkmode-type-'))
                        .forEach(name => sidebar.classList.remove(name))

                    sidebar.classList.remove('light')
                    sidebar.classList.add(`aura-sidebar-size-${settings.sidebarSize}`)
                    sidebar.classList.add(`aura-sidebar-type-${settings.sidebarType}`)
                    sidebar.classList.add(`aura-darkmode-type-${settings.darkmode}`)
                    sidebar.classList.add(`aura-sidebar-darkmode-type-${settings.sidebarDarkmodeType}`)
                    sidebar.classList.toggle('light', settings.sidebarType === 'light')
                }
            }"
        ></div>
    @endif


    @foreach($this->fieldsForView as $key => $field)
        <style>
            #resource-field-{{ optional($field)['slug'] }}-wrapper {
                width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
                @if(optional($field)['type'] === 'Aura\\Base\\Fields\\Panel')
                    margin-bottom: var(--aura-layout-gap, 1rem);
                @endif
            }

            @media screen and (max-width: 768px) {
                #resource-field-{{ optional($field)['slug'] }}-wrapper {
                    width: 100%;
                }
            }
        </style>

        @checkCondition($model, $field, $form)
            <div wire:key="resource-field-{{ $key }}"
            id="resource-field-{{ optional($field)['slug'] }}-wrapper">
            <x-dynamic-component :component="$field['field']->edit()" mode="edit" :field="$field" :form="$form" />
            </div>
        @endcheckCondition
    @endforeach

</div>
