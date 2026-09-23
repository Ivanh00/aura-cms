<?php

namespace Aura\Base\Settings;

final readonly class SettingsPage
{
    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $defaults
     * @param  list<string>  $secretFields
     * @param  array<string, string>  $secretContexts
     */
    public function __construct(
        public string $slug,
        public string $title,
        public array $fields,
        public string $icon = 'cog',
        public ?string $description = null,
        public int $order = 100,
        public array $defaults = [],
        public array $secretFields = [],
        public array $secretContexts = [],
        public ?string $viewAbility = null,
        public ?string $updateAbility = null,
    ) {}
}
