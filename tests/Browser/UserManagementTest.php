<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

require_once __DIR__.'/Support/helpers.php';

/*
|--------------------------------------------------------------------------
| User management browser journeys (issue #56)
|--------------------------------------------------------------------------
|
| Real Chromium against the Testbench app: an admin creates a user with a role
| through the Roles field (AdvancedSelect) and that user logs in; a Global Admin
| grants the flag through the user form and then sees users and teams across the
| whole instance where a plain team Super Admin does not.
|
*/

test('a team Super Admin creates a user with a role through the form, and that user logs in', function () {
    $admin = browserSuperAdmin('admin-password');
    $team = $admin->currentTeam;

    // A distinctively named Team Role so the picker search resolves to one row.
    $role = browserTeamRole($team->id, 'Content Team', 'content-team');

    $page = visit('/admin/user/create');

    $page->assertSee('Name');

    $page->fill('#resource-field-name', 'Created Member')
        ->fill('#aura_field_email', 'created-member@example.com')
        ->fill('#resource-field-password', 'Str0ng!Pass#2024');

    // Pick the role in the AdvancedSelect (open → search → click the option).
    browserPickSingleRole($page, 'Content Team');

    $page->press('Save')->wait(3);

    // The user row exists with a Membership carrying the picked role + team.
    $user = User::withoutGlobalScopes()->where('email', 'created-member@example.com')->first();

    expect($user)->not->toBeNull()
        ->and(browserMembershipExists($user->id, $role->id, $team->id))->toBeTrue();

    // The created account really works: log out the admin and sign in as the new
    // user through the real login form → the admin panel.
    Auth::logout();

    $login = visit('/login');

    $login->fill('#email', 'created-member@example.com')
        ->fill('#password', 'Str0ng!Pass#2024')
        ->press('Log in')
        ->wait(2);

    $login->assertPathIs('/admin');
});

test('a Global Admin grants the flag, and the newly-flagged user then sees across all teams', function () {
    $ga = browserGlobalAdmin('ga-password');
    $team = $ga->currentTeam;

    // A user whose only Membership is in a foreign team the target is not part of —
    // invisible to a plain team Super Admin, visible to a Global Admin.
    $foreign = foreignTeam();
    $foreignMember = soleMemberOf($foreign);
    $foreignMember->forceFill(['email' => 'foreign-seen@example.com'])->save();

    // The target starts as a team Super Admin in the GA's team (via the global
    // admin role) but is NOT yet a Global Admin.
    $target = User::factory()->create(['email' => 'promote-me@example.com', 'current_team_id' => $team->id]);
    browserAttachMembership($target, $team->id, globalAdminRole()->id);
    $target->refresh();

    expect($target->fresh()->global_admin)->toBeFalse();

    $page = visit('/admin/user/'.$target->id.'/edit');

    $page->assertSee('Global Admin');

    // The Global Admin toggle is the only Boolean on the user form; flip it on.
    $page->click('#resource-field-global-admin-wrapper [x-ref="toggle"]')->wait(1);

    $page->press('Save')->wait(3);

    expect($target->fresh()->global_admin)->toBeTrue();

    // Causal chain, one continuous journey: log in AS the newly-flagged user and
    // confirm the grant took effect — their Users index is now unscoped and lists
    // the foreign-team member a plain team Super Admin could never see.
    Auth::logout();
    $this->actingAs($target->fresh());

    $promotedIndex = visit('/admin/user');

    $promotedIndex->assertSee('foreign-seen@example.com');
});

test('a Global Admin sees users across all teams where a plain Super Admin does not', function () {
    // A user whose ONLY Membership is in a foreign team the actor is not part of.
    $foreign = foreignTeam();
    $foreignMember = soleMemberOf($foreign);
    $foreignMember->forceFill(['name' => 'Foreign Only', 'email' => 'foreign-only@example.com'])->save();

    // A plain team Super Admin: their Users index is tenant-scoped.
    $superAdmin = browserSuperAdmin('sa-password');
    $this->actingAs($superAdmin);

    $saIndex = visit('/admin/user');

    $saIndex->assertSee($superAdmin->email)
        ->assertDontSee('foreign-only@example.com');

    // A Global Admin: the Users index is unscoped and lists the foreign-team user.
    $ga = browserGlobalAdmin('ga-password');
    $this->actingAs($ga);

    $gaIndex = visit('/admin/user');

    $gaIndex->assertSee('foreign-only@example.com');
});

test('a Global Admin sees a team they are not a member of in the team switcher', function () {
    $ga = browserGlobalAdmin('ga-password');
    $this->actingAs($ga);

    // createQuietly skips Team model events (and the shared switcher cache
    // invalidation). Clear the cache so getTeams() rebuilds with this row.
    Team::factory()->createQuietly([
        'name' => 'Distant Tenant',
        'user_id' => User::factory()->create()->id,
    ]);
    Cache::forget(User::GLOBAL_ADMIN_TEAMS_CACHE_KEY);

    expect($ga->fresh()->isAuraGlobalAdmin())->toBeTrue()
        ->and($ga->fresh()->getTeams()->pluck('name')->all())->toContain('Distant Tenant');

    $page = visit('/admin');

    // Wait for package JS (window.tippy) before opening the switcher popover.
    // Playwright evaluate resolves returned Promises.
    $page->script(<<<'JS'
        new Promise((resolve, reject) => {
            let n = 0;
            const tick = () => {
                if (typeof window.tippy === 'function') {
                    resolve(true);
                    return;
                }
                if (n++ > 100) {
                    reject(new Error('window.tippy never became available'));
                    return;
                }
                setTimeout(tick, 20);
            };
            tick();
        })
    JS);

    $page->click('.aura-sidebar-team-switcher')->wait(1);

    // Visitation: a Global Admin can enter any team, so the switcher lists a team
    // they hold no Membership in.
    $page->assertSee('Switch Teams')
        ->assertSee('Distant Tenant');
});
