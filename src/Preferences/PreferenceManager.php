<?php

namespace Aura\Base\Preferences;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Lightweight preference store backed by existing User/Team Option helpers.
 *
 * Intentionally small: typed registry + explicit context + simple persistence.
 * Does not include the option-identity hardening stack from the
 * experimental mega-branch.
 */
final class PreferenceManager
{
    public function __construct(private PreferenceRegistry $registry) {}

    public function get(string $key, PreferenceContext $context): bool|int|float|string|array|null
    {
        return $this->resolve($key, $context)->value;
    }

    public function reset(
        string $key,
        PreferenceScope $scope,
        PreferenceContext $context,
        ?User $actor,
    ): void {
        $definition = $this->registry->get($key);

        if (! $definition->supports($scope)) {
            throw new InvalidArgumentException("Preference [{$key}] does not support scope [{$scope->value}].");
        }

        $this->assertCanWrite($scope, $context, $actor);

        $optionKey = $this->optionKey($key, $definition, $context);
        $this->writeScope($scope, $optionKey, null, $context, delete: true);
    }

    public function resolve(string $key, PreferenceContext $context): PreferenceResult
    {
        $definition = $this->registry->get($key);
        $optionKey = $this->optionKey($key, $definition, $context);

        foreach ($this->scopeOrder($definition) as $scope) {
            $value = $this->readScope($scope, $optionKey, $context);

            if ($value === null && ! $definition->nullable) {
                continue;
            }

            if ($value === null && $definition->nullable) {
                return new PreferenceResult(null, $scope, $definition->resourceAware && $context->resource !== null);
            }

            if ($value !== null) {
                try {
                    $definition->validate($value);
                } catch (InvalidArgumentException) {
                    continue;
                }

                return new PreferenceResult(
                    $value,
                    $scope,
                    $definition->resourceAware && $context->resource !== null,
                );
            }
        }

        foreach ($definition->legacyKeys as $legacyKey) {
            $legacy = $this->readScope(PreferenceScope::User, $legacyKey, $context);

            if ($legacy === null) {
                continue;
            }

            try {
                $definition->validate($legacy);
            } catch (InvalidArgumentException) {
                continue;
            }

            return new PreferenceResult(
                $legacy,
                PreferenceScope::User,
                false,
                isLegacy: true,
            );
        }

        return new PreferenceResult($definition->default, null, false, isDefault: true);
    }

    public function set(
        string $key,
        bool|int|float|string|array|null $value,
        PreferenceScope $scope,
        PreferenceContext $context,
        ?User $actor,
    ): void {
        $definition = $this->registry->get($key);

        if (! $definition->supports($scope)) {
            throw new InvalidArgumentException("Preference [{$key}] does not support scope [{$scope->value}].");
        }

        $this->assertCanWrite($scope, $context, $actor);
        $definition->validate($value);

        $optionKey = $this->optionKey($key, $definition, $context);
        $this->writeScope($scope, $optionKey, $value, $context);
    }

    private function assertCanWrite(PreferenceScope $scope, PreferenceContext $context, ?User $actor): void
    {
        if ($actor === null) {
            throw new InvalidArgumentException('An actor is required to change preferences.');
        }

        match ($scope) {
            PreferenceScope::User => $this->assertUserWrite($context, $actor),
            PreferenceScope::Team => $this->assertTeamWrite($context, $actor),
            PreferenceScope::Everyone => $this->assertEveryoneWrite($actor),
        };
    }

    private function assertEveryoneWrite(User $actor): void
    {
        if (! Gate::forUser($actor)->allows(User::GLOBAL_ADMIN_GATE)) {
            throw new InvalidArgumentException('Everyone preferences require a global admin actor.');
        }
    }

    private function assertTeamWrite(PreferenceContext $context, User $actor): void
    {
        if ($context->team === null) {
            throw new InvalidArgumentException('Team preferences require a team context.');
        }

        if (Gate::forUser($actor)->allows(User::GLOBAL_ADMIN_GATE)) {
            return;
        }

        if (Gate::forUser($actor)->allows('update', $context->team)) {
            return;
        }

        throw new InvalidArgumentException('Team preferences can only be changed by the team owner.');
    }

    private function assertUserWrite(PreferenceContext $context, User $actor): void
    {
        if ($context->user === null || (string) $context->user->getKey() !== (string) $actor->getKey()) {
            throw new InvalidArgumentException('User preferences can only be changed by that user.');
        }
    }

    private function optionKey(string $key, PreferenceDefinition $definition, PreferenceContext $context): string
    {
        if ($definition->resourceAware && $context->resource !== null && $context->resource !== '') {
            return 'preference.'.$key.'.'.$context->resource;
        }

        return 'preference.'.$key;
    }

    private function readScope(PreferenceScope $scope, string $optionKey, PreferenceContext $context): bool|int|float|string|array|null
    {
        $value = match ($scope) {
            PreferenceScope::User => $context->user?->getOption($optionKey),
            PreferenceScope::Team => $context->team instanceof Team
                ? $context->team->getOption($optionKey)
                : null,
            PreferenceScope::Everyone => Aura::getOption('preference.everyone.'.$optionKey),
        };

        if (! is_bool($value) && ! is_int($value) && ! is_float($value) && ! is_string($value) && ! is_array($value) && $value !== null) {
            return null;
        }

        return $value;
    }

    /**
     * @return list<PreferenceScope>
     */
    private function scopeOrder(PreferenceDefinition $definition): array
    {
        $order = [PreferenceScope::User, PreferenceScope::Team, PreferenceScope::Everyone];

        return array_values(array_filter(
            $order,
            fn (PreferenceScope $scope): bool => $definition->supports($scope),
        ));
    }

    private function writeScope(
        PreferenceScope $scope,
        string $optionKey,
        bool|int|float|string|array|null $value,
        PreferenceContext $context,
        bool $delete = false,
    ): void {
        if ($delete) {
            // Option helpers only update; store null-equivalent empty for reset simplicity.
            $value = null;
        }

        match ($scope) {
            PreferenceScope::User => $context->user?->updateOption($optionKey, $value),
            PreferenceScope::Team => $context->team instanceof Team
                ? $context->team->updateOption($optionKey, $value)
                : null,
            PreferenceScope::Everyone => Aura::updateOption('preference.everyone.'.$optionKey, $value),
        };
    }
}
