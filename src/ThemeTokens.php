<?php

namespace Aura\Base;

final class ThemeTokens
{
    /** @var list<string> */
    private const COLOR_NAMES = [
        'primary',
        'background',
        'panel',
        'border',
        'text',
        'muted',
        'success',
        'warning',
        'danger',
        'white',
        'black',
    ];

    /** @var array<string, string> */
    private const DESIGN_OVERRIDES = [
        'expert-panel-radius' => '--aura-panel-radius',
        'expert-control-radius' => '--aura-control-radius',
        'expert-panel-padding' => '--aura-panel-padding',
        'expert-panel-small-padding' => '--aura-panel-small-padding',
        'expert-layout-gap' => '--aura-layout-gap',
        'expert-section-gap' => '--aura-section-gap',
        'expert-sidebar-item-height' => '--aura-sidebar-item-height',
        'expert-sidebar-item-padding-x' => '--aura-sidebar-item-padding-x',
        'expert-sidebar-group-gap' => '--aura-sidebar-group-gap',
        'expert-sidebar-heading-gap' => '--aura-sidebar-heading-gap',
        'expert-sidebar-icon-size' => '--aura-sidebar-icon-size',
    ];

    /** @var list<string> */
    private const GENERIC_FONT_FAMILIES = [
        'serif',
        'sans-serif',
        'monospace',
        'cursive',
        'fantasy',
        'system-ui',
        'ui-serif',
        'ui-sans-serif',
        'ui-monospace',
        'ui-rounded',
        'math',
        'emoji',
        'fangsong',
    ];

    /**
     * Resolved colors used when an existing primary palette is promoted into
     * Expert mode. Keeping these values alongside the token resolver prevents
     * Expert mode from silently falling back to Aura blue.
     *
     * @var array<string, array<string, string>>
     */
    private const PALETTE_EXPERT_COLORS = [
        'aura' => ['primary' => '31 85 233', 'background' => '22 67 215', 'hover' => '31 85 233', 'dropdown' => '18 54 185', 'text' => '104 148 246', 'icon' => '149 181 249', 'icon-hover' => '194 213 251'],
        'red' => ['primary' => '220 38 38', 'background' => '220 38 38', 'hover' => '239 68 68', 'dropdown' => '185 28 28', 'text' => '248 113 113', 'icon' => '252 165 165', 'icon-hover' => '252 187 187'],
        'orange' => ['primary' => '194 65 12', 'background' => '234 88 12', 'hover' => '194 65 12', 'dropdown' => '194 65 12', 'text' => '253 186 116', 'icon' => '253 186 116', 'icon-hover' => '254 215 170'],
        'amber' => ['primary' => '180 83 9', 'background' => '217 119 6', 'hover' => '180 83 9', 'dropdown' => '180 83 9', 'text' => '252 205 77', 'icon' => '252 205 77', 'icon-hover' => '253 230 138'],
        'yellow' => ['primary' => '161 98 7', 'background' => '202 138 4', 'hover' => '161 98 7', 'dropdown' => '161 98 7', 'text' => '253 208 71', 'icon' => '253 208 71', 'icon-hover' => '254 240 138'],
        'lime' => ['primary' => '77 124 15', 'background' => '77 124 15', 'hover' => '63 98 18', 'dropdown' => '63 98 18', 'text' => '132 204 22', 'icon' => '163 230 53', 'icon-hover' => '190 242 100'],
        'forest-green' => ['primary' => '38 120 46', 'background' => '38 120 46', 'hover' => '54 148 63', 'dropdown' => '29 95 36', 'text' => '80 178 91', 'icon' => '115 204 127', 'icon-hover' => '157 227 167'],
        'green' => ['primary' => '22 163 74', 'background' => '22 163 74', 'hover' => '21 128 61', 'dropdown' => '21 128 61', 'text' => '74 222 128', 'icon' => '134 239 172', 'icon-hover' => '187 247 208'],
        'emerald' => ['primary' => '5 150 105', 'background' => '5 150 105', 'hover' => '4 120 87', 'dropdown' => '4 120 87', 'text' => '52 211 153', 'icon' => '110 231 183', 'icon-hover' => '167 243 208'],
        'mountain-meadow' => ['primary' => '13 162 139', 'background' => '13 162 139', 'hover' => '18 186 159', 'dropdown' => '15 135 116', 'text' => '94 228 204', 'icon' => '204 248 240', 'icon-hover' => '224 251 246'],
        'teal' => ['primary' => '15 118 110', 'background' => '15 118 110', 'hover' => '17 94 89', 'dropdown' => '17 94 89', 'text' => '20 184 166', 'icon' => '45 212 191', 'icon-hover' => '94 234 212'],
        'ocean-breeze' => ['primary' => '34 131 156', 'background' => '22 107 133', 'hover' => '34 131 156', 'dropdown' => '15 85 110', 'text' => '165 224 233', 'icon' => '198 237 242', 'icon-hover' => '224 247 250'],
        'cyan' => ['primary' => '14 116 144', 'background' => '14 116 144', 'hover' => '8 145 178', 'dropdown' => '21 94 117', 'text' => '34 211 238', 'icon' => '103 232 249', 'icon-hover' => '165 243 252'],
        'sky' => ['primary' => '3 105 161', 'background' => '3 105 161', 'hover' => '2 132 199', 'dropdown' => '7 89 133', 'text' => '56 189 248', 'icon' => '125 211 252', 'icon-hover' => '186 230 253'],
        'blue' => ['primary' => '37 99 235', 'background' => '37 99 235', 'hover' => '59 130 246', 'dropdown' => '29 78 216', 'text' => '96 165 250', 'icon' => '147 197 253', 'icon-hover' => '191 219 254'],
        'indigo' => ['primary' => '67 56 202', 'background' => '67 56 202', 'hover' => '79 70 229', 'dropdown' => '55 48 163', 'text' => '99 102 241', 'icon' => '129 140 248', 'icon-hover' => '165 180 252'],
        'violet' => ['primary' => '124 62 237', 'background' => '124 62 237', 'hover' => '139 92 246', 'dropdown' => '109 40 217', 'text' => '196 180 253', 'icon' => '221 214 254', 'icon-hover' => '237 233 254'],
        'purple' => ['primary' => '126 34 206', 'background' => '126 34 206', 'hover' => '147 51 234', 'dropdown' => '107 33 168', 'text' => '192 132 252', 'icon' => '216 180 254', 'icon-hover' => '233 213 255'],
        'fuchsia' => ['primary' => '162 28 175', 'background' => '162 28 175', 'hover' => '192 38 211', 'dropdown' => '134 25 143', 'text' => '232 121 249', 'icon' => '240 171 252', 'icon-hover' => '245 208 254'],
        'pink' => ['primary' => '190 24 93', 'background' => '190 24 93', 'hover' => '219 39 119', 'dropdown' => '157 24 77', 'text' => '244 114 182', 'icon' => '249 168 212', 'icon-hover' => '252 207 232'],
        'rose' => ['primary' => '212 23 59', 'background' => '212 23 59', 'hover' => '233 41 73', 'dropdown' => '180 18 52', 'text' => '246 123 141', 'icon' => '249 164 175', 'icon-hover' => '252 205 211'],
        'sandal' => ['primary' => '157 139 102', 'background' => '157 139 102', 'hover' => '181 166 131', 'dropdown' => '131 113 79', 'text' => '202 191 163', 'icon' => '220 213 193', 'icon-hover' => '235 231 218'],
        'desert-sand' => ['primary' => '174 124 82', 'background' => '174 124 82', 'hover' => '198 151 106', 'dropdown' => '147 98 63', 'text' => '217 179 136', 'icon' => '231 203 167', 'icon-hover' => '241 223 199'],
        'salmon' => ['primary' => '206 92 76', 'background' => '206 92 76', 'hover' => '224 123 109', 'dropdown' => '179 69 52', 'text' => '236 157 146', 'icon' => '245 190 182', 'icon-hover' => '250 215 210'],
        'autumn-rust' => ['primary' => '186 68 28', 'background' => '186 68 28', 'hover' => '217 91 46', 'dropdown' => '154 52 22', 'text' => '232 128 82', 'icon' => '241 166 127', 'icon-hover' => '247 199 173'],
        'slate' => ['primary' => '75 85 99', 'background' => '75 85 99', 'hover' => '107 114 128', 'dropdown' => '51 65 85', 'text' => '156 163 175', 'icon' => '209 213 219', 'icon-hover' => '229 231 235'],
        'dark-slate' => ['primary' => '61 71 85', 'background' => '61 71 85', 'hover' => '93 100 114', 'dropdown' => '40 53 72', 'text' => '148 155 167', 'icon' => '201 205 211', 'icon-hover' => '226 228 232'],
        'blackout' => ['primary' => '55 65 81', 'background' => '55 65 81', 'hover' => '75 85 99', 'dropdown' => '17 24 39', 'text' => '156 163 175', 'icon' => '209 213 219', 'icon-hover' => '229 231 235'],
        'obsidian' => ['primary' => '61 60 67', 'background' => '61 60 67', 'hover' => '91 90 97', 'dropdown' => '41 40 47', 'text' => '131 130 136', 'icon' => '171 170 175', 'icon-hover' => '201 200 204'],
        'amethyst' => ['primary' => '75 71 99', 'background' => '75 71 99', 'hover' => '107 100 128', 'dropdown' => '51 51 85', 'text' => '156 148 175', 'icon' => '209 203 219', 'icon-hover' => '229 226 235'],
        'opal' => ['primary' => '71 85 105', 'background' => '71 85 105', 'hover' => '100 116 139', 'dropdown' => '51 65 85', 'text' => '148 163 184', 'icon' => '203 213 225', 'icon-hover' => '226 232 240'],
        'gray' => ['primary' => '75 85 99', 'background' => '75 85 99', 'hover' => '107 114 128', 'dropdown' => '55 65 81', 'text' => '156 163 175', 'icon' => '209 213 219', 'icon-hover' => '229 231 235'],
        'zinc' => ['primary' => '82 82 91', 'background' => '82 82 91', 'hover' => '113 113 122', 'dropdown' => '63 63 70', 'text' => '161 161 170', 'icon' => '212 212 216', 'icon-hover' => '228 228 231'],
        'neutral' => ['primary' => '82 82 82', 'background' => '82 82 82', 'hover' => '115 115 115', 'dropdown' => '64 64 64', 'text' => '163 163 163', 'icon' => '212 212 212', 'icon-hover' => '229 229 229'],
        'stone' => ['primary' => '87 83 78', 'background' => '87 83 78', 'hover' => '120 113 108', 'dropdown' => '68 64 60', 'text' => '168 162 158', 'icon' => '214 211 209', 'icon-hover' => '231 229 228'],
        'sandstone' => ['primary' => '75 62 52', 'background' => '75 62 52', 'hover' => '107 91 79', 'dropdown' => '51 40 32', 'text' => '156 140 128', 'icon' => '209 197 188', 'icon-hover' => '229 221 215'],
        'rose-quartz' => ['primary' => '75 66 70', 'background' => '75 66 70', 'hover' => '107 95 99', 'dropdown' => '51 45 49', 'text' => '156 140 144', 'icon' => '209 196 199', 'icon-hover' => '229 219 221'],
        'olive' => ['primary' => '94 103 82', 'background' => '94 103 82', 'hover' => '143 153 124', 'dropdown' => '65 75 55', 'text' => '177 186 160', 'icon' => '208 215 195', 'icon-hover' => '226 231 217'],
        'smaragd' => ['primary' => '66 87 74', 'background' => '66 87 74', 'hover' => '86 113 95', 'dropdown' => '50 63 55', 'text' => '134 156 141', 'icon' => '158 183 166', 'icon-hover' => '194 214 198'],
    ];

    /** @var array<string, string> */
    private const SIDEBAR_COLOR_NAMES = [
        'background' => '--sidebar-bg',
        'hover' => '--sidebar-bg-hover',
        'dropdown' => '--sidebar-bg-dropdown',
        'text' => '--sidebar-text',
        'icon' => '--sidebar-icon',
        'icon-hover' => '--sidebar-icon-hover',
    ];

    /** @var array<string, mixed>|null */
    private static ?array $packageTheme = null;

    /**
     * @param  array<string, mixed>  $theme
     * @return array<string, string>
     */
    public static function colors(array $theme, string $mode): array
    {
        $mode = $mode === 'dark' ? 'dark' : 'light';
        $packageColors = self::packageTheme()['colors'][$mode];
        $configuredColors = $theme['colors'][$mode] ?? [];

        if (! is_array($configuredColors)) {
            $configuredColors = [];
        }

        $colors = [];

        foreach (self::COLOR_NAMES as $name) {
            $expertValue = self::expertEnabled($theme)
                ? ($theme["expert-{$mode}-color-{$name}"] ?? null)
                : null;
            $colors[$name] = self::normalizeColor($expertValue)
                ?? self::normalizeColor($configuredColors[$name] ?? null)
                ?? $packageColors[$name];
        }

        return $colors;
    }

    /**
     * Shared spacing and radius variables used by the admin interface.
     *
     * @param  array<string, mixed>  $theme
     * @return array<string, string>
     */
    public static function design(array $theme): array
    {
        $density = $theme['interface-density'] ?? 'comfortable';
        $radius = $theme['corner-radius'] ?? 'rounded';
        $sidebarSize = $theme['sidebar-size'] ?? 'standard';

        $densityTokens = match ($density) {
            'compact' => [
                '--aura-panel-padding' => '1rem',
                '--aura-panel-small-padding' => '0.375rem',
                '--aura-layout-gap' => '1rem',
                '--aura-section-gap' => '0.75rem',
            ],
            default => [
                '--aura-panel-padding' => '1.25rem',
                '--aura-panel-small-padding' => '0.5rem',
                '--aura-layout-gap' => '1.5rem',
                '--aura-section-gap' => '1rem',
            ],
        };

        $radiusTokens = match ($radius) {
            'square' => [
                '--aura-panel-radius' => '0rem',
                '--aura-control-radius' => '0rem',
            ],
            'subtle' => [
                '--aura-panel-radius' => '0.375rem',
                '--aura-control-radius' => '0.25rem',
            ],
            default => [
                '--aura-panel-radius' => '0.75rem',
                '--aura-control-radius' => '0.5rem',
            ],
        };

        $sidebarTokens = match ($sidebarSize) {
            'dense' => [
                '--aura-sidebar-item-height' => '1.75rem',
                '--aura-sidebar-item-padding-x' => '0.375rem',
                '--aura-sidebar-icon-size' => '1rem',
                '--aura-sidebar-group-gap' => '0.125rem',
                '--aura-sidebar-heading-gap' => '0.625rem',
            ],
            'compact' => [
                '--aura-sidebar-item-height' => '2rem',
                '--aura-sidebar-item-padding-x' => '0.5rem',
                '--aura-sidebar-icon-size' => '1.125rem',
                '--aura-sidebar-group-gap' => '0.25rem',
                '--aura-sidebar-heading-gap' => '0.875rem',
            ],
            default => [
                '--aura-sidebar-item-height' => '2.25rem',
                '--aura-sidebar-item-padding-x' => '0.75rem',
                '--aura-sidebar-icon-size' => '1.25rem',
                '--aura-sidebar-group-gap' => '0.25rem',
                '--aura-sidebar-heading-gap' => '1.25rem',
            ],
        };

        $tokens = [...$densityTokens, ...$radiusTokens, ...$sidebarTokens];

        if (! self::expertEnabled($theme)) {
            return $tokens;
        }

        foreach (self::DESIGN_OVERRIDES as $setting => $variable) {
            if ($value = self::pixelValue($theme[$setting] ?? null)) {
                $tokens[$variable] = $value;
            }
        }

        return $tokens;
    }

    /**
     * Return the active primary and sidebar palette as editable hex values.
     *
     * @param  array<string, mixed>  $theme
     * @return array<string, string>
     */
    public static function expertPaletteColors(array $theme): array
    {
        $palette = $theme['color-palette'] ?? 'aura';

        if ($palette === 'custom') {
            $fallback = self::PALETTE_EXPERT_COLORS['aura'];
            $colors = [
                'primary' => self::normalizeColor($theme['primary-600'] ?? null) ?? $fallback['primary'],
                'background' => self::normalizeColor($theme['primary-600'] ?? null) ?? $fallback['background'],
                'hover' => self::normalizeColor($theme['primary-500'] ?? null) ?? $fallback['hover'],
                'dropdown' => self::normalizeColor($theme['primary-700'] ?? null) ?? $fallback['dropdown'],
                'text' => self::normalizeColor($theme['primary-400'] ?? null) ?? $fallback['text'],
                'icon' => self::normalizeColor($theme['primary-300'] ?? null) ?? $fallback['icon'],
                'icon-hover' => self::normalizeColor($theme['primary-200'] ?? null) ?? $fallback['icon-hover'],
            ];
        } else {
            $colors = self::PALETTE_EXPERT_COLORS[$palette] ?? self::PALETTE_EXPERT_COLORS['aura'];
        }

        return array_map(
            fn (string $color): string => self::rgbToHex($color) ?? '#1f55e9',
            $colors,
        );
    }

    /**
     * @param  array<string, mixed>  $theme
     */
    public static function fontFamily(array $theme): string
    {
        $families = $theme['font']['family'] ?? [];

        if (is_string($families)) {
            $families = preg_split('/\s*,\s*/', $families) ?: [];
        }

        if (! is_array($families)) {
            $families = [];
        }

        $families = array_values(array_filter(array_map(
            self::formatFontFamily(...),
            $families,
        )));

        if ($families === []) {
            return self::fontFamily(self::packageTheme());
        }

        return implode(', ', $families);
    }

    /**
     * Return a host-local public asset path, rejecting external and executable URLs.
     *
     * @param  array<string, mixed>  $theme
     */
    public static function fontStylesheet(array $theme): ?string
    {
        $stylesheet = $theme['font']['stylesheet'] ?? null;

        if (! is_string($stylesheet)) {
            return null;
        }

        $stylesheet = trim($stylesheet);

        if (
            $stylesheet === ''
            || str_starts_with($stylesheet, '//')
            || str_contains($stylesheet, '\\')
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $stylesheet) === 1
            || preg_match('/(?:^|\/)\.\.(?:\/|$)/', $stylesheet) === 1
            || preg_match('/[\x00-\x1F\x7F]/', $stylesheet) === 1
        ) {
            return null;
        }

        return ltrim($stylesheet, '/');
    }

    /**
     * Resolve package defaults, host config, and persisted settings in that order.
     *
     * @param  array<string, mixed>  $storedSettings
     * @return array<string, mixed>
     */
    public static function resolve(array $storedSettings = []): array
    {
        $packageTheme = self::packageTheme();
        $configuredTheme = config('aura.theme', []);

        if (! is_array($configuredTheme)) {
            $configuredTheme = [];
        }

        return self::merge(
            self::merge($packageTheme, $configuredTheme),
            $storedSettings,
        );
    }

    /**
     * @param  array<string, mixed>  $theme
     * @return array<string, string>
     */
    public static function sidebarColors(array $theme, string $mode): array
    {
        if (! self::expertEnabled($theme)) {
            return [];
        }

        $mode = $mode === 'dark' ? 'dark' : 'light';
        $colors = [];

        foreach (self::SIDEBAR_COLOR_NAMES as $name => $variable) {
            $value = self::normalizeColor($theme["expert-{$mode}-sidebar-{$name}"] ?? null);

            if ($value !== null) {
                $colors[$variable] = $value;
            }
        }

        return $colors;
    }

    /** @param array<string, mixed> $theme */
    private static function expertEnabled(array $theme): bool
    {
        return filter_var($theme['appearance-expert-mode'] ?? false, FILTER_VALIDATE_BOOL);
    }

    private static function formatFontFamily(mixed $family): ?string
    {
        if (! is_string($family)) {
            return null;
        }

        $family = trim($family);
        $openingQuote = $family[0] ?? '';

        if (
            strlen($family) >= 2
            && in_array($openingQuote, ['"', "'"], true)
            && str_ends_with($family, $openingQuote)
        ) {
            $family = substr($family, 1, -1);
        }

        if (
            $family === ''
            || preg_match('/[\x00-\x1F\x7F<>]/', $family) === 1
        ) {
            return null;
        }

        if (in_array(strtolower($family), self::GENERIC_FONT_FAMILIES, true)) {
            return strtolower($family);
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $family).'"';
    }

    private static function isColorValue(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $value = trim($value);

        if (preg_match('/^var\(--[a-zA-Z0-9_-]+\)$/', $value) === 1) {
            return true;
        }

        if (preg_match('/^(\d{1,3})\s+(\d{1,3})\s+(\d{1,3})$/', $value, $matches) !== 1) {
            return false;
        }

        foreach (array_slice($matches, 1) as $channel) {
            if ((int) $channel > 255) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursively merge associative maps while replacing ordered lists.
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                isset($base[$key])
                && is_array($base[$key])
                && is_array($value)
                && ! array_is_list($base[$key])
                && ! array_is_list($value)
            ) {
                $base[$key] = self::merge($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    private static function normalizeColor(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^#[0-9a-f]{6}$/i', $value) === 1) {
            return TransformColor::hexToRgb($value);
        }

        return self::isColorValue($value) ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function packageTheme(): array
    {
        if (self::$packageTheme !== null) {
            return self::$packageTheme;
        }

        /** @var array<string, mixed> $configuration */
        $configuration = require dirname(__DIR__).'/config/aura.php';

        /** @var array<string, mixed> $theme */
        $theme = $configuration['theme'];

        return self::$packageTheme = $theme;
    }

    private static function pixelValue(mixed $value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value >= 0 && $value <= 96 ? $value.'px' : null;
    }

    private static function rgbToHex(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/^(\d{1,3})\s+(\d{1,3})\s+(\d{1,3})$/', $value, $matches) !== 1) {
            return null;
        }

        $channels = array_map('intval', array_slice($matches, 1));

        if (max($channels) > 255) {
            return null;
        }

        return sprintf('#%02x%02x%02x', ...$channels);
    }
}
