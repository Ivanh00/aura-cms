<?php

namespace Aura\Base\Livewire;

use Aura\Base\Contracts\AiConnector;
use Aura\Base\Settings\SettingsPage;
use Aura\Base\Settings\SettingsPageAuthorizer;
use Aura\Base\Settings\SettingsRegistry;
use Aura\Base\Settings\SettingsStore;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\MediaFields;
use Illuminate\Support\Arr;
use Livewire\Component;

class Settings extends Component
{
    use InputFields;
    use MediaFields;

    public ?array $aiConnectionStatus = null;

    public bool $canUpdate = false;

    public $form = [
        'fields' => [],
    ];

    public $model;

    public string $page = 'general';

    public array $secretConfigured = [];

    public function boot(): void
    {
        // The field caches are keyed by class, but this component renders a different page per URL.
        static::flushFieldCache();
    }

    public function fieldsCollection()
    {
        return collect($this->settingsPage()->fields)
            ->map(fn (array $field): array => $this->canUpdate
                ? $field
                : [...$field, 'disabled' => true]);
    }

    public static function generalFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Appearance',
                'slug' => 'panel-DZzV',
            ],
            [
                'name' => 'Logo',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'logo',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Logo Darkmode',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'logo-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],
            // [
            //     'name' => 'Timezone',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'timezone',
            // ],

            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Sidebar',
                'slug' => 'panel-theme-sidebar',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Size',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'standard',
                        'value' => 'Standard',
                    ],
                    [
                        'key' => 'compact',
                        'value' => 'Compact',
                    ],
                ],
                'slug' => 'sidebar-size',
                'style' => [
                    'width' => '25',
                ],
            ],
            [
                'name' => 'Sidebar',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'primary',
                        'value' => 'Primary',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'sidebar-type',
                'style' => [
                    'width' => '25',
                ],
            ],
            [
                'name' => 'Darkmode',
                'live' => true,
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'auto',
                        'value' => 'Auto',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'darkmode-type',
                'style' => [
                    'width' => '25',
                ],
            ],

            [
                'name' => 'Sidebar Darkmode',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'primary',
                        'value' => 'Primary',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'sidebar-darkmode-type',
                'style' => [
                    'width' => '25',
                ],
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['darkmode-type']) {
                        return $form['fields']['darkmode-type'] == 'auto';
                    }
                },
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Theme',
                'slug' => 'panel-theme-primary',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Primary Color Palette',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    'aura' => 'Aura',
                    'red' => 'Red',
                    'orange' => 'Orange',
                    'amber' => 'Amber',
                    'yellow' => 'Yellow',
                    'lime' => 'Lime',
                    'forest-green' => 'Forest Green',
                    'green' => 'Green',
                    'emerald' => 'Emerald',
                    'mountain-meadow' => 'Mountain Meadow',
                    'teal' => 'Teal',
                    'ocean-breeze' => 'Ocean Breeze',
                    'cyan' => 'Cyan',
                    'sky' => 'Sky',
                    'blue' => 'Blue',
                    'indigo' => 'Indigo',
                    'violet' => 'Violet',
                    'purple' => 'Purple',
                    'fuchsia' => 'Fuchsia',
                    'pink' => 'Pink',
                    'rose' => 'Rose',
                    'sandal' => 'Sandal',
                    'desert-sand' => 'Desert Sand',
                    'salmon' => 'Salmon',
                    'autumn-rust' => 'Autumn Rust',

                    'slate' => 'Slate',
                    'dark-slate' => 'Dark Slate',
                    'blackout' => 'Blackout',
                    'obsidian' => 'Obsidian',
                    'amethyst' => 'Amethyst',
                    'opal' => 'Opal',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'sandstone' => 'Sandstone',
                    'rose-quartz' => 'Rose Quartz',
                    'olive' => 'Olive',
                    'smaragd' => 'Smaragd',

                    'custom' => 'Custom',
                ],
                'slug' => 'color-palette',
                'live' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Group',
                'name' => 'Custom Colors',
                'slug' => 'tab-primary-colors-lightmode',
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['color-palette']) {
                        return $form['fields']['color-palette'] == 'custom';
                    }
                },
            ],
            [
                'name' => 'Primary 25',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-25',
            ],
            [
                'name' => 'Primary 50',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-50',
            ],
            [
                'name' => 'Primary 100',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-100',
            ],
            [
                'name' => 'Primary 200',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-200',
            ],
            [
                'name' => 'Primary 300',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-300',
            ],
            [
                'name' => 'Primary 400',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-400',
            ],
            [
                'name' => 'Primary 500',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-500',
            ],
            [
                'name' => 'Primary 600',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-600',
            ],
            [
                'name' => 'Primary 700',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-700',
            ],
            [
                'name' => 'Primary 800',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-800',
            ],
            [
                'name' => 'Primary 900',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-900',
            ],
            [
                'name' => 'Primary 950',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-950',
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Theme',
                'slug' => 'panel-theme-gray',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Gray Color Palette',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    'slate' => 'Slate',
                    'dark-slate' => 'Dark Slate',
                    'blackout' => 'Blackout',
                    'obsidian' => 'Obsidian',
                    'amethyst' => 'Amethyst',
                    'opal' => 'Opal',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'sandstone' => 'Sandstone',
                    'rose-quartz' => 'Rose Quartz',
                    'olive' => 'Olive',
                    'smaragd' => 'Smaragd',
                    'custom' => 'Custom',
                ],
                'slug' => 'gray-color-palette',
                'live' => true,
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Group',
                'name' => 'Custom Colors',
                'slug' => 'tab-gray-colors-custom-tab',
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['gray-color-palette']) {
                        return $form['fields']['gray-color-palette'] == 'custom';
                    }
                },
            ],

            [
                'name' => 'Gray 25',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-25',
            ],
            [
                'name' => 'Gray 50',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-50',
            ],
            [
                'name' => 'Gray 100',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-100',
            ],
            [
                'name' => 'Gray 200',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-200',
            ],
            [
                'name' => 'Gray 300',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-300',
            ],
            [
                'name' => 'Gray 400',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-400',
            ],
            [
                'name' => 'Gray 500',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-500',
            ],
            [
                'name' => 'Gray 600',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-600',
            ],
            [
                'name' => 'Gray 700',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-700',
            ],
            [
                'name' => 'Gray 800',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-800',
            ],
            [
                'name' => 'Gray 900',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-900',
            ],
            [
                'name' => 'Gray 950',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-950',
            ],

        ];
    }

    public static function getFields()
    {
        return static::generalFields();
    }

    public function getFieldsForViewProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function getFieldsProperty()
    {
        return $this->inputFields()->mapWithKeys(function ($field) {
            return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
        });
    }

    public function mount(
        SettingsPageAuthorizer $authorizer,
        SettingsRegistry $registry,
        SettingsStore $store,
        string $page = 'general',
    ) {
        abort_unless(config('aura.features.settings') && $registry->has($page), 404);

        $this->page = $page;

        abort_unless($authorizer->canView($this->settingsPage(), auth()->user()), 403);

        $this->canUpdate = $authorizer->canUpdate($this->settingsPage(), auth()->user());

        $valueString = [
            'darkmode-type' => config('aura.theme.darkmode-type'),
            'sidebar-type' => config('aura.theme.sidebar-type'),
            'color-palette' => config('aura.theme.color-palette'),
            'gray-color-palette' => config('aura.theme.gray-color-palette'),
            'sidebar-size' => config('aura.theme.sidebar-size'),
            'sidebar-darkmode-type' => config('aura.theme.sidebar-darkmode-type'),
        ];

        $this->model = $store->findOrCreate($valueString);

        $stored = $store->values($this->model);
        $defaults = $registry->defaults();
        $secretFields = $this->settingsPage()->secretFields;

        $this->secretConfigured = array_fill_keys(
            array_values(array_filter(
                $secretFields,
                static fn (string $slug): bool => $store->secret($slug, $stored) !== null,
            )),
            true,
        );

        $this->form['fields'] = $this->inputFields()->mapWithKeys(function ($field) use ($defaults, $secretFields, $stored) {
            $slug = $field['slug'];

            if (in_array($slug, $secretFields, true)) {
                return [$slug => ''];
            }

            return [$slug => $stored[$slug] ?? $defaults[$slug] ?? ''];
        })->toArray();

        $this->model->setAttribute('value', Arr::except($stored, [...$registry->secretFields(), '_secret_contexts']));
    }

    public function render()
    {
        return view('aura::livewire.settings')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }

    public function save(
        SettingsPageAuthorizer $authorizer,
        SettingsRegistry $registry,
        SettingsStore $store,
    ): void {
        abort_unless($authorizer->canUpdate($this->settingsPage(), auth()->user()), 403);

        $this->validate();

        $secretFields = $this->settingsPage()->secretFields;

        $this->model = $store->store($this->model, $this->form['fields'], $secretFields);
        $stored = $store->values($this->model);

        foreach ($secretFields as $slug) {
            $this->secretConfigured[$slug] = $store->secret($slug, $stored) !== null;

            $this->form['fields'][$slug] = '';
        }

        $this->model->setAttribute('value', Arr::except($stored, [...$registry->secretFields(), '_secret_contexts']));

        $this->dispatch('notify', message: __('Successfully updated'), type: 'success');
    }

    public function settingsPage(): SettingsPage
    {
        return app(SettingsRegistry::class)->page($this->page) ?? abort(404);
    }

    public function testAiConnection(AiConnector $connector, SettingsPageAuthorizer $authorizer): void
    {
        abort_unless($authorizer->canUpdate($this->settingsPage(), auth()->user()), 403);

        $result = $connector->testConnection();
        $this->aiConnectionStatus = $result->toArray();

        $this->dispatch('notify', message: $result->message, type: $result->successful ? 'success' : 'error');
    }
}
