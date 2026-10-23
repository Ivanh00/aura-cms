<x-aura::fields.wrapper :field="$field">
    @if (optional(optional($field)['options'])['native'])
        <div class="relative z-20"
            x-data="{
                @if (optional($field)['live'] === true)
                    color: $wire.entangle('form.fields.{{ optional($field)['slug'] }}').live,
                @else
                    color: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
                @endif
                init () {
                    // watch color for changes  and update the input
                    this.$watch('color', value => {
                        this.$nextTick(() => {
                            $dispatch('input', { value: this.color });
                        });
                    });
                },
            }"
        >
            <x-aura::input.text :disabled="$field['field']->isDisabled($this->form, $field)" x-model="color" error="form.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" id="resource-field-{{ optional($field)['slug'] }}" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input.text>

            <input type="color" x-model="color" class="absolute right-4 top-1/2 z-10 w-6 h-6 rounded-full border-none transform -translate-y-1/2 cursor-pointer" />
        </div>
    @else


        @assets
                {{-- <link rel="stylesheet" href="/js/pickr/nano.min.css"/> <!-- 'nano' theme --> --}}
                {{-- <script src="/js/pickr/pickr.min.js"></script> --}}
            @vite(['resources/js/pickr.js'], 'vendor/aura/libs')
        @endassets

    <div wire:ignore class="flex items-center"
        x-data="{
            @if (optional($field)['live'] === true)
                selectedColor: $wire.entangle('form.fields.{{ optional($field)['slug'] }}').live,
            @else
                selectedColor: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
            @endif
            isDisabled: {{ $field['field']->isDisabled($this->form, $field) ? 'true' : 'false' }},
            init() {
                // Simple example, see optional options for more configuration.
                const pickr = window.Pickr.create({
                    el: this.$refs.colorPicker,
                    theme: 'nano', // or 'classic', or 'monolith'

                    swatches: [],

                    default: this.selectedColor ?? '#000000',

                    position: 'bottom-start',

                    @if (optional($field)['format'] == 'hex')
                        defaultRepresentation: 'HEX',
                    @elseif (optional($field)['format'] == 'rgb')
                        defaultRepresentation: 'RGBA',
                    @elseif (optional($field)['format'] == 'hsl')
                        defaultRepresentation: 'HSLA',
                    @elseif (optional($field)['format'] == 'hsv')
                        defaultRepresentation: 'HSVA',
                    @elseif (optional($field)['format'] == 'cmyk')
                        defaultRepresentation: 'CMYK',
                    @else
                        defaultRepresentation: 'HEX',
                    @endif

                    components: {
                        // Main components
                        preview: true,
                        opacity: true,
                        hue: true,

                        // Input / output Options
                        interaction: {
                            hex: false,
                            rgba: false,
                            hsla: false,
                            hsva: false,
                            cmyk: false,
                            input: true,
                            clear: true,
                            save: true
                        }
                    }
                }).on('save', (color, source, instance) => {
                    if (!this.isDisabled) {
                        @if (optional($field)['format'] == 'hex')
                            this.selectedColor = color.toHEXA().toString();
                        @elseif (optional($field)['format'] == 'rgb')
                            this.selectedColor = color.toRGBA().toString(0);
                        @elseif (optional($field)['format'] == 'hsl')
                            this.selectedColor = color.toHSLA().toString(0);
                        @elseif (optional($field)['format'] == 'hsv')
                            this.selectedColor = color.toHSVA().toString(0);
                        @elseif (optional($field)['format'] == 'cmyk')
                            this.selectedColor = color.toCMYK().toString(0);
                        @else
                            this.selectedColor = color.toHEXA().toString();
                        @endif
                        $dispatch('input', { value: this.selectedColor });
                    }
                });

                // watch color for changes and update the input
                this.$watch('selectedColor', value => {
                    if (!this.isDisabled) {
                        this.$nextTick(() => {
                            // trim the value and all characters after the 9th
                            this.selectedColor = this.selectedColor.substring(0, 9);
                            $dispatch('input', value);

                            // debounce 400ms
                            setTimeout(() => {
                                @if (optional($field)['format'] == 'hex')
                                // only update if the color is 7 characters
                                if (this.selectedColor.length == 7 || this.selectedColor.length == 4 || this.selectedColor.length == 9) {
                                    pickr.setColor(this.selectedColor);
                                }
                                @else
                                    pickr.setColor(this.selectedColor);
                                @endif
                            }, 1000);
                        });
                    }
                });

                // Disable/Enable the Pickr instance based on the isDisabled state
                this.$watch('isDisabled', value => {
                    if (value) {
                        pickr.disable();
                    } else {
                        pickr.enable();
                    }
                });

                // Initial state
                if (this.isDisabled) {
                    pickr.disable();
                }
            },
        }"
    >
        <div>
            <input
                x-ref="colorPicker"
                type="text"
                class="hidden"
            />
        </div>

        <div class="ml-2">
            <x-aura::input.text :disabled="$field['field']->isDisabled($this->form, $field)" x-model="selectedColor" error="form.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" id="resource-field-{{ optional($field)['slug'] }}"></x-aura::input.text>
        </div>
    </div>

    @endif

    {{-- border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300  focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 rounded-md shadow-sm --}}

    <style >
        .pcr-app .pcr-interaction .pcr-result {
            border: 1px solid rgb(var(--gray-300));
            border-radius: 0.5rem;
            background: white;
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            line-height: 1rem;
        }
        .pcr-app .pcr-interaction .pcr-save {
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            line-height: 1rem;
            font-weight: 600;
            letter-spacing: 0px;

            background: rgb(var(--primary-600));
        }
        .pcr-app .pcr-interaction .pcr-clear, .pcr-app .pcr-interaction .pcr-cancel {
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            line-height: 1rem;
            font-weight: 600;
            letter-spacing: 0px;

            background: rgb(var(--gray-100));
            color: rgb(var(--gray-700));
        }
        .pcr-app[data-theme="nano"] {
            width: 16rem;
        }
    </style>
</x-aura::fields.wrapper>
