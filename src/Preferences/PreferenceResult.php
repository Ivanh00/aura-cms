<?php

namespace Aura\Base\Preferences;

final readonly class PreferenceResult
{
    public function __construct(
        public bool|int|float|string|array|null $value,
        public ?PreferenceScope $scope,
        public bool $resourceSpecific,
        public bool $isDefault = false,
        public bool $isLegacy = false,
    ) {}
}
