<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

/**
 * Point the acting user's current team at $team without granting a Membership,
 * and make TeamScope observe it (its team id is cached).
 */
function actAsWithCurrentTeam(User $user, Team $team): void
{
    $user->forceFill(['current_team_id' => $team->id])->save();
    $user->refresh();
    test()->actingAs($user);
    Cache::forget("user_{$user->id}_current_team_id");
}

describe('Global Admin Users index sees every team', function () {
    beforeEach(function () {
        if (! config('aura.teams')) {
            $this->markTestSkipped('Cross-team visibility is a teams-on concern.');
        }

        // A member whose only Membership lives in a separate team.
        auth()->logout();
        $this->otherTeam = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $this->otherUser = soleMemberOf($this->otherTeam);

        // The current team the acting user is pointed at (a different tenant).
        $this->currentTeam = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
    });

    it('lists a user from another team for a Global Admin (indexQuery seam)', function () {
        $ga = createGlobalAdmin();
        actAsWithCurrentTeam($ga, $this->currentTeam);

        $ids = (new User)->indexQuery(User::query())->pluck('id');

        expect($ids)->toContain($this->otherUser->id);
    });

    it('lists a user from another team for a Global Admin (table component)', function () {
        $ga = createGlobalAdmin();
        actAsWithCurrentTeam($ga, $this->currentTeam);

        // The unpaginated query the table runs — robust to page size/ordering.
        $ids = livewire(Table::class, ['query' => null, 'model' => new User])
            ->instance()->rowsQuery()->pluck('id');

        expect($ids)->toContain($this->otherUser->id);
    });

    it('serves the Users index to a Global Admin over HTTP', function () {
        $ga = createGlobalAdmin();
        actAsWithCurrentTeam($ga, $this->currentTeam);

        $this->get(route('aura.user.index'))
            ->assertOk()
            ->assertSeeLivewire(Table::class);
    });

    it('hides other teams\' users from a non-Global-Admin (indexQuery seam)', function () {
        $admin = createSuperAdmin(); // super admin scoped to their own team

        $ids = (new User)->indexQuery(User::query())->pluck('id');

        expect($ids)->not->toContain($this->otherUser->id)
            ->and($ids)->toContain($admin->id);
    });

    it('hides other teams\' users from a non-Global-Admin (table component)', function () {
        createSuperAdmin();

        $ids = livewire(Table::class, ['query' => null, 'model' => new User])
            ->instance()->rowsQuery()->pluck('id');

        expect($ids)->not->toContain($this->otherUser->id);
    });
});

describe('Global Admin sees and can manage all teams', function () {
    beforeEach(function () {
        if (! config('aura.teams')) {
            $this->markTestSkipped('Team listing is a teams-on concern.');
        }

        auth()->logout();
        $this->teamB = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $this->teamC = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
    });

    it('offers every team to a Global Admin through getTeams (switcher source)', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);
        Cache::forget(User::GLOBAL_ADMIN_TEAMS_CACHE_KEY);

        $ids = $ga->getTeams()->pluck('id');

        expect($ids)->toContain($this->teamB->id)->toContain($this->teamC->id);
    });

    it('offers a non-Global-Admin only their own teams through getTeams', function () {
        $admin = createSuperAdmin();
        $this->actingAs($admin);

        $ids = $admin->getTeams()->pluck('id');

        expect($ids)->toContain($admin->current_team_id)
            ->and($ids)->not->toContain($this->teamB->id)
            ->and($ids)->not->toContain($this->teamC->id);
    });

    it('lists every team for a Global Admin in the Team index table', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        $ids = livewire(Table::class, ['query' => null, 'model' => new Team])
            ->instance()->rowsQuery()->pluck('id');

        expect($ids)->toContain($this->teamB->id)->toContain($this->teamC->id);
    });

    it('reflects a newly created team in the Global Admin switcher cache immediately', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        // Prime the shared cache.
        $ga->getTeams();
        expect(Cache::has(User::GLOBAL_ADMIN_TEAMS_CACHE_KEY))->toBeTrue();

        // A real create fires Team::created, which invalidates the shared cache.
        $newTeam = Team::create(['name' => 'Fresh Tenant']);
        expect(Cache::has(User::GLOBAL_ADMIN_TEAMS_CACHE_KEY))->toBeFalse();

        expect($ga->getTeams()->pluck('id'))->toContain($newTeam->id);
    });
});

describe('Teams-off mode', function () {
    beforeEach(function () {
        if (config('aura.teams')) {
            $this->markTestSkipped('Teams-off no-op assertions only.');
        }
    });

    it('keeps the flag readable while team surfaces no-op', function () {
        $ga = createGlobalAdmin();
        $this->actingAs($ga);

        // getTeams() returns an empty collection (not null) so call sites like
        // getTeams()->count() stay safe when teams are disabled.
        expect($ga->isAuraGlobalAdmin())->toBeTrue()
            ->and($ga->getTeams())->toBeEmpty()
            ->and($ga->currentTeam())->toBeNull();
    });
});
