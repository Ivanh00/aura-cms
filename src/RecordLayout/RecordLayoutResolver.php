<?php

namespace Aura\Base\RecordLayout;

use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class RecordLayoutResolver
{
    public function __construct(
        private PreferenceManager $preferences,
        private RecordLayoutRegistry $registry,
    ) {}

    public function resolve(Resource $resource): RecordLayout
    {
        $regions = [];
        $relationships = [];
        $preferenceValues = [];
        $user = auth()->user() instanceof User ? auth()->user() : null;
        $preferenceContext = null;

        foreach ($this->registry->panelsFor($resource) as $registered) {
            $panel = $registered->panel;

            if (! $panel->visible
                || ! $this->authorized($panel, $resource, $user)
                || ! $this->preferenceAllows($panel, $resource, $user, $preferenceContext, $preferenceValues)
                || ! $this->relationshipsExist($panel, $resource)) {
                continue;
            }

            $regions[$panel->region->value][] = $registered;
            array_push($relationships, ...$panel->eagerLoad);
        }

        if ($relationships !== []) {
            $resource->loadMissing(array_values(array_unique($relationships)));
        }

        return new RecordLayout($regions);
    }

    private function authorized(RecordLayoutPanel $panel, Resource $resource, ?User $user): bool
    {
        if ($panel->ability === null) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        try {
            return Gate::forUser($user)->allows($panel->ability, $resource);
        } catch (Throwable $e) {
            Log::warning('Record layout authorization failed.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function preferenceAllows(
        RecordLayoutPanel $panel,
        Resource $resource,
        ?User $user,
        ?PreferenceContext &$context,
        array &$resolved,
    ): bool {
        if ($panel->preferenceKey === null) {
            return true;
        }

        try {
            $context ??= $this->preferenceContext($resource, $user);
            if (! array_key_exists($panel->preferenceKey, $resolved)) {
                $resolved[$panel->preferenceKey] = $this->preferences->get($panel->preferenceKey, $context);
            }

            return $resolved[$panel->preferenceKey] === true;
        } catch (Throwable $e) {
            Log::warning('Record layout preference check failed.', [
                'message' => $e->getMessage(),
            ]);
            $resolved[$panel->preferenceKey] = false;

            return false;
        }
    }

    private function preferenceContext(Resource $resource, ?User $user): PreferenceContext
    {
        $team = null;

        if (config('aura.teams') && $user !== null) {
            // Prefer hardened identity helper when present; fall back to currentTeam.
            $team = method_exists($user, 'authorizedCurrentTeam')
                ? $user->authorizedCurrentTeam()
                : $user->currentTeam;
        }

        return new PreferenceContext(
            application: (string) config('app.name', 'aura'),
            user: $user,
            team: $team instanceof Team ? $team : null,
            resource: $resource->getType(),
        );
    }

    private function relationshipsExist(RecordLayoutPanel $panel, Resource $resource): bool
    {
        foreach ($panel->eagerLoad as $relationship) {
            if (! method_exists($resource, explode('.', $relationship, 2)[0])) {
                return false;
            }
        }

        return true;
    }
}
