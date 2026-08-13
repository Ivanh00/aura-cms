<?php

namespace Aura\Base\Preferences;

use InvalidArgumentException;

final readonly class PreferenceDefinition
{
    /**
     * @param  array<int, PreferenceScope>  $scopes
     * @param  array<int, bool|int|float|string|array|null>  $allowedValues
     * @param  array<int, string>  $legacyKeys
     */
    public function __construct(
        public string $key,
        public PreferenceValueType $type,
        public bool|int|float|string|array|null $default,
        public array $scopes = [PreferenceScope::User, PreferenceScope::Team],
        public bool $nullable = false,
        public bool $resourceAware = false,
        public array $allowedValues = [],
        public ?PreferenceValueType $itemType = null,
        public bool $list = false,
        public array $legacyKeys = [],
    ) {
        if (! preg_match('/\A[a-z][a-z0-9_.-]*\z/', $key)) {
            throw new InvalidArgumentException("Invalid preference key [{$key}].");
        }

        if ($scopes === []) {
            throw new InvalidArgumentException("Preference [{$key}] must declare valid scopes.");
        }

        foreach ($scopes as $scope) {
            if (! $scope instanceof PreferenceScope) {
                throw new InvalidArgumentException("Preference [{$key}] must declare valid scopes.");
            }
        }

        if (($list || $itemType !== null) && $type !== PreferenceValueType::Array) {
            throw new InvalidArgumentException(
                "Preference [{$key}] must use the array type when declaring a list or item type."
            );
        }

        $this->validate($default);
    }

    public function supports(PreferenceScope $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function validate(bool|int|float|string|array|null $value): void
    {
        if ($value === null) {
            if ($this->nullable) {
                return;
            }

            throw new InvalidArgumentException("Preference [{$this->key}] does not accept null.");
        }

        if (! $this->type->accepts($value)) {
            throw new InvalidArgumentException("Preference [{$this->key}] must be {$this->type->value}.");
        }

        if ($this->list && is_array($value) && ! array_is_list($value)) {
            throw new InvalidArgumentException("Preference [{$this->key}] must be a list.");
        }

        if ($this->itemType !== null && is_array($value)) {
            foreach ($value as $item) {
                if (! $this->itemType->accepts($item)) {
                    throw new InvalidArgumentException("Preference [{$this->key}] contains an invalid item.");
                }
            }
        }

        if ($this->allowedValues !== [] && ! in_array($value, $this->allowedValues, true)) {
            throw new InvalidArgumentException("Preference [{$this->key}] contains a value outside its schema.");
        }
    }
}
