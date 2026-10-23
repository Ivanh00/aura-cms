<?php

use Aura\Base\ThemeTokens;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;

test('theme token defaults expose the stable light and dark contract', function () {
    expect(config('aura.theme.font'))->toBe([
        'family' => [
            'ui-sans-serif',
            'system-ui',
            'sans-serif',
            'Apple Color Emoji',
            'Segoe UI Emoji',
            'Segoe UI Symbol',
            'Noto Color Emoji',
        ],
        'stylesheet' => false,
    ])->and(config('aura.theme.colors.light'))->toBe([
        'primary' => 'var(--primary-600)',
        'background' => '255 255 255',
        'panel' => '250 250 250',
        'border' => '228 228 231',
        'text' => '24 24 27',
        'muted' => '82 82 91',
        'success' => '22 163 74',
        'warning' => '217 119 6',
        'danger' => '220 38 38',
        'white' => '255 255 255',
        'black' => '0 0 0',
    ])->and(config('aura.theme.colors.dark'))->toBe([
        'primary' => 'var(--primary-600)',
        'background' => '9 9 11',
        'panel' => '24 24 27',
        'border' => '63 63 70',
        'text' => '244 244 245',
        'muted' => '161 161 170',
        'success' => '22 163 74',
        'warning' => '217 119 6',
        'danger' => '220 38 38',
        'white' => '255 255 255',
        'black' => '0 0 0',
    ]);
});

test('expert tokens override presets only while expert mode is enabled', function () {
    $settings = [
        'appearance-expert-mode' => true,
        'interface-density' => 'comfortable',
        'corner-radius' => 'rounded',
        'sidebar-size' => 'standard',
        'expert-panel-radius' => 0,
        'expert-section-gap' => 7,
        'expert-light-color-background' => '#fefdfc',
        'expert-dark-color-text' => '#abcdef',
        'expert-light-sidebar-background' => '#112233',
        'expert-dark-sidebar-icon-hover' => '#445566',
    ];

    expect(ThemeTokens::design($settings))
        ->toMatchArray([
            '--aura-panel-radius' => '0px',
            '--aura-section-gap' => '7px',
        ])
        ->and(ThemeTokens::colors($settings, 'light')['background'])->toBe('254 253 252')
        ->and(ThemeTokens::colors($settings, 'dark')['text'])->toBe('171 205 239')
        ->and(ThemeTokens::sidebarColors($settings, 'light')['--sidebar-bg'])->toBe('17 34 51')
        ->and(ThemeTokens::sidebarColors($settings, 'dark')['--sidebar-icon-hover'])->toBe('68 85 102');

    $settings['appearance-expert-mode'] = false;

    expect(ThemeTokens::design($settings)['--aura-panel-radius'])->toBe('0.75rem')
        ->and(ThemeTokens::colors($settings, 'light')['background'])->toBe('255 255 255')
        ->and(ThemeTokens::sidebarColors($settings, 'light'))->toBe([]);
});

test('renderer emits the semantic variables for both color schemes', function () {
    $html = view('aura::components.layout.colors', [
        'settings' => ThemeTokens::resolve(),
    ])->render();

    expect($html)
        ->toContain('--aura-font-sans: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";')
        ->toContain('--aura-color-primary: var(--primary-600);')
        ->toContain('--aura-color-background: 255 255 255;')
        ->toContain('--aura-color-panel: 250 250 250;')
        ->toContain('--aura-color-border: 228 228 231;')
        ->toContain('--aura-color-text: 24 24 27;')
        ->toContain('--aura-color-muted: 82 82 91;')
        ->toContain('--aura-color-success: 22 163 74;')
        ->toContain('--aura-color-warning: 217 119 6;')
        ->toContain('--aura-color-danger: 220 38 38;')
        ->toContain('.dark {')
        ->toContain('--aura-color-background: 9 9 11;')
        ->toContain('--aura-color-panel: 24 24 27;')
        ->toContain('--aura-color-border: 63 63 70;')
        ->toContain('--aura-color-text: 244 244 245;')
        ->toContain('--aura-color-muted: 161 161 170;');
});

test('design presets emit stable density radius and sidebar variables', function () {
    expect(ThemeTokens::design([
        'interface-density' => 'compact',
        'corner-radius' => 'square',
        'sidebar-size' => 'dense',
    ]))->toMatchArray([
        '--aura-panel-padding' => '1rem',
        '--aura-layout-gap' => '1rem',
        '--aura-panel-radius' => '0rem',
        '--aura-control-radius' => '0rem',
        '--aura-sidebar-item-height' => '1.75rem',
        '--aura-sidebar-icon-size' => '1rem',
    ]);
});

test('scoped renderer isolates temporary preview variables and scripts', function () {
    $html = view('aura::components.layout.colors', [
        'settings' => [
            'color-palette' => 'red',
            'interface-density' => 'compact',
            'corner-radius' => 'subtle',
        ],
        'colorScope' => '#settings-preview',
        'darkColorScope' => '#settings-preview.dark',
        'includeColorHelpers' => false,
    ])->render();

    expect($html)
        ->toContain('#settings-preview {')
        ->toContain('#settings-preview.dark {')
        ->toContain('--primary-600: 220 38 38;')
        ->toContain('--aura-panel-padding: 1rem;')
        ->toContain('--aura-control-radius: 0.25rem;')
        ->not->toContain('document.documentElement.classList');
});

test('font family serializer removes only matching outer quote pairs', function (array|string $families, string $expected) {
    expect(ThemeTokens::fontFamily([
        'font' => ['family' => $families],
    ]))->toBe($expected);
})->with([
    'matching double quote pair and surrounding whitespace' => [
        ['  "Acme Sans"  ', ' system-ui '],
        '"Acme Sans", system-ui',
    ],
    'matching single quote pair' => [
        ["'Source Serif 4'", 'serif'],
        '"Source Serif 4", serif',
    ],
    'embedded quotes' => [
        ['Brand "Quoted"', 'sans-serif'],
        '"Brand \"Quoted\"", sans-serif',
    ],
    'backslashes' => [
        ['C:\\Fonts\\Acme', 'monospace'],
        '"C:\\\\Fonts\\\\Acme", monospace',
    ],
    'unmatched opening quote' => [
        ['"Brand Quoted', 'sans-serif'],
        '"\"Brand Quoted", sans-serif',
    ],
    'unmatched closing quote' => [
        ['Brand Quoted"', 'sans-serif'],
        '"Brand Quoted\"", sans-serif',
    ],
    'mixed outer quotes' => [
        ['"Brand Quoted\'', 'sans-serif'],
        '"\"Brand Quoted\'", sans-serif',
    ],
    'exactly one matching outer pair' => [
        ['""Acme Sans""', 'sans-serif'],
        '"\"Acme Sans\"", sans-serif',
    ],
    'comma separated fallback list' => [
        '  "Acme Sans"  ,  system-ui , sans-serif  ',
        '"Acme Sans", system-ui, sans-serif',
    ],
]);

test('renderer preserves quoted custom font names in the font CSS variable', function () {
    $html = view('aura::components.layout.colors', [
        'settings' => [
            'font' => [
                'family' => ['Brand "Quoted"', 'C:\\Fonts\\Acme', 'sans-serif'],
            ],
        ],
    ])->render();

    expect($html)->toContain(
        '--aura-font-sans: "Brand \"Quoted\"", "C:\\\\Fonts\\\\Acme", sans-serif;',
    );
});

test('host and stored settings override tokens without losing package defaults', function () {
    config()->set('aura.theme', [
        'color-palette' => 'emerald',
        'font' => [
            'family' => ['Atkinson Hyperlegible', 'sans-serif'],
        ],
        'colors' => [
            'light' => [
                'primary' => '1 2 3',
            ],
        ],
    ]);

    $theme = ThemeTokens::resolve([
        'colors' => [
            'dark' => [
                'panel' => '4 5 6',
            ],
        ],
    ]);

    expect($theme['color-palette'])->toBe('emerald')
        ->and(ThemeTokens::fontFamily($theme))->toBe('"Atkinson Hyperlegible", sans-serif')
        ->and(ThemeTokens::colors($theme, 'light')['primary'])->toBe('1 2 3')
        ->and(ThemeTokens::colors($theme, 'light')['background'])->toBe('255 255 255')
        ->and(ThemeTokens::colors($theme, 'dark')['panel'])->toBe('4 5 6')
        ->and(ThemeTokens::colors($theme, 'dark')['danger'])->toBe('220 38 38');
});

test('font stylesheet is absent by default and accepts only a local asset path', function () {
    $defaultHtml = view('aura::components.layout.styles', ['settings' => []])->render();
    $localHtml = view('aura::components.layout.styles', [
        'settings' => [
            'font' => [
                'family' => ['Acme Sans', 'sans-serif'],
                'stylesheet' => 'fonts/acme-sans.css',
            ],
        ],
    ])->render();

    expect($defaultHtml)
        ->not->toContain('inter.css')
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.gstatic.com')
        ->and($localHtml)
        ->toContain('href="http://localhost/fonts/acme-sans.css"')
        ->toContain('--aura-font-sans: "Acme Sans", sans-serif;');

    foreach (['https://fonts.example.com/theme.css', '//fonts.example.com/theme.css', 'data:text/css,body{}'] as $remoteStylesheet) {
        $html = view('aura::components.layout.styles', [
            'settings' => [
                'font' => [
                    'stylesheet' => $remoteStylesheet,
                ],
            ],
        ])->render();

        expect($html)->not->toContain($remoteStylesheet);
    }
});

test('active views and host scaffold have no fixed Inter dependency', function () {
    $packagePath = dirname(__DIR__, 2);
    $files = [
        $packagePath.'/resources/views/components/layout/styles.blade.php',
        $packagePath.'/resources/views/components/widgets/bar.blade.php',
        $packagePath.'/resources/views/livewire/styleguide.blade.php',
        $packagePath.'/stubs/scaffold/views/components/layout/app.blade.php',
    ];

    foreach ($files as $file) {
        expect(file_get_contents($file))
            ->not->toContain("'Inter'")
            ->not->toContain('/inter.css')
            ->not->toContain('Font family: Inter');
    }
});

test('renderer rejects malformed token values', function () {
    $theme = ThemeTokens::resolve([
        'font' => [
            'family' => ['</style><script>alert(1)</script>'],
        ],
        'colors' => [
            'light' => [
                'text' => '999 0 0',
                'danger' => 'red; background: url(https://example.com)',
            ],
        ],
    ]);

    expect(ThemeTokens::fontFamily($theme))->toBe(
        'ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
    )->and(ThemeTokens::colors($theme, 'light')['text'])->toBe('24 24 27')
        ->and(ThemeTokens::colors($theme, 'light')['danger'])->toBe('220 38 38');
});

test('theme token configuration can be cached', function () {
    $filesystem = new Filesystem;
    $basePath = sys_get_temp_dir().'/aura-theme-config-cache-'.bin2hex(random_bytes(8));
    $filesystem->makeDirectory($basePath.'/bootstrap/cache', 0755, true);
    $filesystem->makeDirectory($basePath.'/storage/framework/cache/data', 0755, true);
    $filesystem->makeDirectory($basePath.'/storage/framework/sessions', 0755, true);
    $filesystem->makeDirectory($basePath.'/storage/framework/views', 0755, true);
    $filesystem->makeDirectory($basePath.'/storage/logs', 0755, true);
    $filesystem->put($basePath.'/bootstrap/app.php', <<<'PHP'
<?php

use Aura\Base\AuraServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        LivewireServiceProvider::class,
        AuraServiceProvider::class,
    ])
    ->create();
PHP);

    try {
        $application = require $basePath.'/bootstrap/app.php';
        $exitCode = $application->make(Kernel::class)->call('config:cache', [
            '--no-interaction' => true,
        ]);
        $cachedConfiguration = require $application->getCachedConfigPath();

        expect($exitCode)->toBe(0)
            ->and($cachedConfiguration['aura']['theme']['colors']['light']['primary'])
            ->toBe('var(--primary-600)')
            ->and($cachedConfiguration['aura']['theme']['font']['stylesheet'])
            ->toBeFalse();
    } finally {
        $filesystem->deleteDirectory($basePath);
    }
});
