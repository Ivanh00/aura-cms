<?php

namespace Aura\Base\Settings;

use Aura\Base\Livewire\Settings;
use InvalidArgumentException;

final class SettingsRegistry
{
    /** @var array<string, array{source: string, page: SettingsPage}> */
    private array $baselinePages = [];

    /** @var array<string, array{source: string, page: SettingsPage}> */
    private array $pages = [];

    public function captureBaselineState(): void
    {
        $this->baselinePages = $this->pages;
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->pages() as $page) {
            foreach ($page->fields as $field) {
                if (isset($field['slug']) && array_key_exists('default', $field)) {
                    $defaults[$field['slug']] = $field['default'];
                }
            }

            $defaults = array_replace($defaults, $page->defaults);
        }

        return $defaults;
    }

    public function flushState(): void
    {
        $this->pages = $this->baselinePages;
        Settings::flushFieldCache();
    }

    public function has(string $slug): bool
    {
        return isset($this->pages[$slug]);
    }

    public function page(string $slug): ?SettingsPage
    {
        return $this->pages[$slug]['page'] ?? null;
    }

    /** @return list<SettingsPage> */
    public function pages(): array
    {
        $pages = array_column($this->pages, 'page');

        usort($pages, static fn (SettingsPage $left, SettingsPage $right): int => [
            $left->order,
            $left->title,
            $left->slug,
        ] <=> [
            $right->order,
            $right->title,
            $right->slug,
        ]);

        return $pages;
    }

    /** @param  list<SettingsPage>  $pages */
    public function register(string $source, array $pages): void
    {
        $this->validateSource($source);

        $pending = $this->pages;

        foreach ($pages as $page) {
            if (! $page instanceof SettingsPage) {
                throw new InvalidArgumentException("Settings source [{$source}] must register immutable settings page definitions.");
            }

            if (isset($pending[$page->slug])) {
                if ($pending[$page->slug]['source'] === $source && $pending[$page->slug]['page'] == $page) {
                    continue;
                }

                throw new InvalidArgumentException("Settings page [{$page->slug}] is already registered.");
            }

            $this->validatePage($source, $page, $pending);
            $pending[$page->slug] = ['source' => $source, 'page' => $page];
        }

        $this->pages = $pending;
        Settings::flushFieldCache();
    }

    /**
     * @param  array<string, array{source: string, page: SettingsPage}>  $pending
     */
    private function validatePage(string $source, SettingsPage $page, array $pending): void
    {
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $page->slug) !== 1) {
            throw new InvalidArgumentException("Settings page slug [{$page->slug}] from [{$source}] must use lowercase kebab-case.");
        }

        if (trim($page->title) === '') {
            throw new InvalidArgumentException("Settings page [{$page->slug}] from [{$source}] must have a title.");
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $page->icon) !== 1) {
            throw new InvalidArgumentException("Settings page [{$page->slug}] from [{$source}] must use a kebab-case icon name.");
        }

        $registeredSlugs = [];

        foreach ($pending as $registered) {
            foreach ($registered['page']->fields as $field) {
                if (isset($field['slug'])) {
                    $registeredSlugs[$field['slug']] = $registered['page']->slug;
                }
            }
        }

        $pageSlugs = [];

        foreach ($page->fields as $field) {
            if (! isset($field['type'], $field['slug']) || ! is_string($field['type']) || ! is_string($field['slug'])) {
                throw new InvalidArgumentException("Every field on settings page [{$page->slug}] must define string type and slug values.");
            }

            if (isset($pageSlugs[$field['slug']])) {
                throw new InvalidArgumentException("Settings field [{$field['slug']}] is duplicated on page [{$page->slug}].");
            }

            if (isset($registeredSlugs[$field['slug']])) {
                throw new InvalidArgumentException("Settings field [{$field['slug']}] on page [{$page->slug}] is already registered by page [{$registeredSlugs[$field['slug']]}].");
            }

            $pageSlugs[$field['slug']] = true;
        }

        foreach (array_keys($page->defaults) as $slug) {
            if (! isset($pageSlugs[$slug])) {
                throw new InvalidArgumentException("Default [{$slug}] is not a field on settings page [{$page->slug}].");
            }
        }
    }

    private function validateSource(string $source): void
    {
        if (preg_match('/\A[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\z/D', $source) !== 1) {
            throw new InvalidArgumentException("Settings source [{$source}] must be a lowercase Composer package name.");
        }
    }
}
