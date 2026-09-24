<?php

namespace Aura\Base\Settings;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final readonly class SettingsPageAuthorizer
{
    public function canUpdate(SettingsPage $page, ?Authenticatable $user): bool
    {
        if ($page->updateAbility !== null) {
            return $user !== null && Gate::forUser($user)->allows($page->updateAbility);
        }

        return $this->isSuperAdmin($user);
    }

    public function canView(SettingsPage $page, ?Authenticatable $user): bool
    {
        if ($page->viewAbility !== null) {
            return $user !== null && Gate::forUser($user)->allows($page->viewAbility);
        }

        return $this->isSuperAdmin($user);
    }

    private function isSuperAdmin(?Authenticatable $user): bool
    {
        return $user !== null
            && method_exists($user, 'isSuperAdmin')
            && $user->isSuperAdmin();
    }
}
