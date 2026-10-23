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
}
