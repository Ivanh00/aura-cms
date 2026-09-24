<?php

namespace Aura\Base\Settings;

use Aura\Base\Resources\Option;
use Aura\Base\Support\TeamExecutionContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use LogicException;

final class SettingsStore
{
    private const RETIRED_AI_KEYS = [
        '_secret_contexts',
        'ai-api-key',
        'ai-endpoint',
        'ai-model',
        'ai-provider',
    ];

    /**
     * Settings of every team, for lookups that run without a team context
     * (e.g. resolving which team owns a public hostname).
     *
     * @return list<array{team_id: ?int, values: array<string, mixed>}>
     */
    public function all(): array
    {
        return Option::withoutGlobalScopes()
            ->where('name', 'like', config('aura.teams') ? 'team.%.settings' : 'settings')
            ->get()
            ->map(fn (Option $option): array => [
                'team_id' => config('aura.teams') ? (int) $option->getAttribute('team_id') : null,
                'values' => $this->values($option),
            ])
            ->all();
    }

    /** @param  array<string, mixed>  $defaults */
    public function findOrCreate(array $defaults = []): Option
    {
        return Option::firstOrCreate(
            ['name' => $this->optionName()],
            ['value' => $defaults],
        );
    }

    /** Pass $teamId to read a team's settings without an authenticated user (public requests, jobs). */
    public function get(string $key, mixed $default = null, ?int $teamId = null): mixed
    {
        $values = $this->values(teamId: $teamId);

        if (! array_key_exists($key, $values)) {
            return $default;
        }

        return $values[$key];
    }

    public function put(string $key, mixed $value): Option
    {
        return $this->store($this->findOrCreate(), [$key => $value]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function store(Option $option, array $values): Option
    {
        $option->refresh();
        $storedValue = $option->getAttribute('value');
        $stored = is_array($storedValue) ? Arr::except($storedValue, self::RETIRED_AI_KEYS) : [];

        // Each settings page submits only its own fields.
        $option->update(['value' => array_replace($stored, $values)]);
        $this->forgetCache();

        return $option;
    }

    /** @return array<string, mixed> */
    public function values(?Option $option = null, ?int $teamId = null): array
    {
        $teamId ??= $this->currentTeamId();

        if (! $option && config('aura.teams') && $teamId === null) {
            return [];
        }

        // The option name carries the team id, so the lookup must not depend on TeamScope.
        $option ??= Option::withoutGlobalScopes()->where('name', $this->optionName($teamId))->first();

        if (! $option) {
            return [];
        }

        $value = $option->getAttribute('value');

        if (is_array($value)) {
            return Arr::except($value, self::RETIRED_AI_KEYS);
        }

        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? Arr::except($decoded, self::RETIRED_AI_KEYS) : [];
    }

    private function currentTeamId(): mixed
    {
        if (TeamExecutionContext::active()) {
            return TeamExecutionContext::currentTeamId();
        }

        $user = auth()->user();

        if (! $user instanceof Model) {
            return null;
        }

        $team = $user->getRelationValue('currentTeam');

        return $team instanceof Model
            ? $team->getKey()
            : $user->getAttribute('current_team_id');
    }

    private function forgetCache(): void
    {
        Cache::forget('aura.settings');

        $teamId = $this->currentTeamId();

        if (config('aura.teams') && $teamId !== null) {
            Cache::forget($teamId.'.aura.settings');
            Cache::forget('team.'.$teamId.'.settings');
        }
    }

    private function optionName(mixed $teamId = null): string
    {
        if (! config('aura.teams')) {
            return 'settings';
        }

        $teamId ??= $this->currentTeamId();

        if ($teamId === null) {
            throw new LogicException('Team settings require an authenticated current team.');
        }

        return 'team.'.$teamId.'.settings';
    }
}
