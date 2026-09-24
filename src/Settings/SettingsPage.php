<?php

namespace Aura\Base\Settings;

final readonly class SettingsPage
{
    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $defaults
     */
    public function __construct(
        public string $slug,
        public string $title,
        public array $fields,
        public string $icon = 'cog',
        public ?string $description = null,
        public int $order = 100,
        public array $defaults = [],
        public ?string $viewAbility = null,
        public ?string $updateAbility = null,
    ) {}
}
