<?php

namespace Aura\Base\Settings;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final readonly class SettingsPageAuthorizer
{
    public function canUpdate(SettingsPage $page, ?Authenticatable $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user !== null
            && $page->updateAbility !== null
            && Gate::forUser($user)->allows($page->updateAbility);
    }

    public function canView(SettingsPage $page, ?Authenticatable $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user !== null
            && $page->viewAbility !== null
            && Gate::forUser($user)->allows($page->viewAbility);
    }

    private function isSuperAdmin(?Authenticatable $user): bool
    {
        return $user !== null
            && method_exists($user, 'isSuperAdmin')
            && $user->isSuperAdmin();
    }
}
