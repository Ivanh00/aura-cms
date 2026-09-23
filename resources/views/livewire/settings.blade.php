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

        @if($canUpdate)
            <div>
                <x-aura::button wire:click="save">
                    <div wire:loading wire:target="save">
                        <x-aura::icon.loading  />
                    </div>
                    {{ __('Save') }}
                </x-aura::button>
            </div>
        @endif
    </div>


    @foreach($this->fieldsForView as $key => $field)
        <style>
            #resource-field-{{ optional($field)['slug'] }}-wrapper {
                width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
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
