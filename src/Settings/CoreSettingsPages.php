<?php

namespace Aura\Base\Settings;

use Aura\Base\Livewire\Settings;
use Aura\Base\Resources\User;

final class CoreSettingsPages
{
    /** @return list<SettingsPage> */
    public static function all(): array
    {
        return [
            new SettingsPage(
                slug: 'general',
                title: 'General',
                fields: Settings::generalFields(),
                icon: 'cog',
                order: 0,
            ),
            new SettingsPage(
                slug: 'ai',
                title: 'AI',
                fields: self::aiFields(),
                icon: 'config',
                description: 'Review AI providers configured for this application.',
                order: 50,
                viewAbility: User::GLOBAL_ADMIN_GATE,
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function aiFields(): array
    {
        return [
            [
                'name' => 'AI provider status',
                'type' => 'Aura\\Base\\Fields\\View',
                'slug' => 'ai-provider-status',
                'view' => 'aura::settings.ai-provider-status',
            ],
        ];
    }
}
