<?php

use Aura\Base\Livewire\Table\Traits\QueryFilters;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

it('scopes tenant models according to the teams feature flag', function () {
    $attributes = [
        'title' => 'Tenant Post',
        'type' => 'Post',
        'status' => 'publish',
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $this->user->current_team_id;

        Post::withoutGlobalScope(TeamScope::class)->create($attributes);

        // Clear team context without logging out.
        DB::table('users')->where('id', $this->user->id)->update(['current_team_id' => null]);
        Cache::forget(User::currentTeamCacheKey($this->user->id));
        $this->user->refresh();

        // Teams on + no current team: fail closed — never return unscoped rows.
        expect(Post::query()->count())->toBe(0)
            ->and(Post::query()->toSql())->toContain('1 = 0');

        return;
    }

    // Teams off: TeamScope is a no-op (no team columns / no tenant filter).
    Post::withoutGlobalScope(TeamScope::class)->create($attributes);

    expect(Post::query()->count())->toBe(1)
        ->and(Post::query()->toSql())->not->toContain('1 = 0');
});

it('scopes user queries according to the teams feature flag when team context is absent', function () {
    $other = User::factory()->create();

    if (config('aura.teams')) {
        DB::table('users')->where('id', $this->user->id)->update(['current_team_id' => null]);
        Cache::forget(User::currentTeamCacheKey($this->user->id));
        $this->user->refresh();

        // Ordinary authenticated user with no team: only their own row.
        $ids = User::query()->pluck('id')->all();

        expect($ids)->toBe([$this->user->id])
            ->and($ids)->not->toContain($other->id);

        return;
    }

    // Teams off: no self-only restriction from TeamScope.
    $ids = User::query()->pluck('id')->all();

    expect($ids)->toContain($this->user->id)
        ->and($ids)->toContain($other->id)
        ->and(User::query()->toSql())->not->toContain('1 = 0');
});

it('scopes Role queries according to the teams feature flag when team context is absent', function () {
    // Catalog always has at least one role from bootstrap/seeding (or attach-don't-mint).
    expect(Role::withoutGlobalScope(TeamScope::class)->count())->toBeGreaterThan(0);

    if (config('aura.teams')) {
        DB::table('users')->where('id', $this->user->id)->update(['current_team_id' => null]);
        Cache::forget(User::currentTeamCacheKey($this->user->id));
        $this->user->refresh();

        // Teams on + no current team: refuse the role catalog rather than leak it.
        expect(Role::query()->count())->toBe(0)
            ->and(Role::query()->toSql())->toContain('1 = 0');

        return;
    }

    // Teams off: roles remain queryable without a tenant fail-closed clause.
    expect(Role::query()->count())->toBeGreaterThan(0)
        ->and(Role::query()->toSql())->not->toContain('1 = 0');
});
it('rejects table filters with an unknown operator via fail-closed default', function () {
    $harness = new class
    {
        use QueryFilters;

        public $filters = ['custom' => []];

        public $model;

        public function runOperator(Builder $query, array $filter): void
        {
            $this->applyOperatorCondition($query, $filter);
        }

        public function runTable(Builder $query, array $filter): Builder
        {
            return $this->applyTableFieldFilter($query, $filter);
        }

        public function valid(array $filter): bool
        {
            return $this->isValidFilter($filter);
        }
    };

    $query = Post::query();
    $harness->runOperator($query, [
        'name' => 'title',
        'operator' => 'drop_table',
        'value' => 'x',
    ]);

    expect($query->toSql())->toContain('1 = 0');

    $tableQuery = Post::query();
    $harness->runTable($tableQuery, [
        'name' => 'title',
        'operator' => 'not_a_real_operator',
        'value' => 'x',
    ]);

    expect($tableQuery->toSql())->toContain('1 = 0');

    expect($harness->valid(['name' => 'title', 'value' => 'x']))->toBeFalse()
        ->and($harness->valid(['name' => 'title', 'operator' => '', 'value' => 'x']))->toBeFalse()
        ->and($harness->valid(['name' => 'title', 'operator' => 'contains', 'value' => 'x']))->toBeTrue();
});

it('fails closed for malformed custom filter payloads', function () {
    $harness = new class
    {
        use QueryFilters;

        public $filters = ['custom' => ['not-a-group' => true]];

        public $model;

        public function run(Builder $query): Builder
        {
            return $this->applyCustomFilter($query);
        }
    };

    $query = Post::query();
    $harness->run($query);

    expect($query->toSql())->toContain('1 = 0');
});

it('denies policy abilities when actor and resource use different connections', function () {
    $policy = new ResourcePolicy;

    // Same connection should still authorize for a super admin.
    expect($policy->view($this->user, new Post))->toBeTrue();

    // Pin the resource to a connection name that cannot match the actor.
    $resource = new Post;
    $resource->setConnection('foreign_security_connection');

    expect($this->user->getConnectionName())->not->toBe($resource->getConnectionName())
        ->and($policy->view($this->user, $resource))->toBeFalse()
        ->and($policy->update($this->user, $resource))->toBeFalse()
        ->and($policy->delete($this->user, $resource))->toBeFalse()
        ->and($policy->create($this->user, $resource))->toBeFalse()
        ->and($policy->viewAny($this->user, $resource))->toBeFalse()
        ->and($policy->restore($this->user, $resource))->toBeFalse()
        ->and($policy->forceDelete($this->user, $resource))->toBeFalse();
});

it('fails closed when a custom filter group contains a tampered filter', function () {
    createPost(['title' => 'Visible Post']);

    $harness = new class
    {
        use QueryFilters;

        public $filters = [
            'custom' => [
                [
                    'filters' => [
                        [
                            'name' => 'title',
                            'operator' => '',
                            'value' => 'Visible',
                        ],
                    ],
                ],
            ],
        ];

        public $model;

        public function run(Builder $query): Builder
        {
            return $this->applyCustomFilter($query);
        }
    };

    $query = Post::query();
    $harness->run($query);

    expect($query->toSql())->toContain('1 = 0')
        ->and($query->count())->toBe(0);
});

it('skips cleared filter values without failing closed', function () {
    createPost(['title' => 'Cleared Filter Post']);

    $harness = new class
    {
        use QueryFilters;

        public $filters = [
            'custom' => [
                [
                    'filters' => [
                        [
                            'name' => 'title',
                            'operator' => 'contains',
                            'value' => null,
                        ],
                    ],
                ],
            ],
        ];

        public $model;

        public function run(Builder $query): Builder
        {
            return $this->applyCustomFilter($query);
        }
    };

    $query = Post::query();
    $harness->run($query);

    expect($query->toSql())->not->toContain('1 = 0')
        ->and($query->count())->toBeGreaterThan(0);
});

it('skips completely empty placeholder filters without failing closed', function () {
    createPost(['title' => 'Placeholder Post']);

    $harness = new class
    {
        use QueryFilters;

        public $filters = [
            'custom' => [
                [
                    'filters' => [
                        [
                            'name' => '',
                            'operator' => '',
                            'value' => '',
                        ],
                    ],
                ],
            ],
        ];

        public $model;

        public function run(Builder $query): Builder
        {
            return $this->applyCustomFilter($query);
        }
    };

    $query = Post::query();
    $harness->run($query);

    expect($query->toSql())->not->toContain('1 = 0')
        ->and($query->count())->toBeGreaterThan(0);
});

it('fails closed for operator-without-name and name-without-operator filters', function () {
    createPost(['title' => 'Partial Filter Post']);

    $operatorWithoutName = new class
    {
        use QueryFilters;

        public $filters = [
            'custom' => [
                [
                    'filters' => [
                        [
                            'name' => '',
                            'operator' => 'contains',
                            'value' => 'Partial',
                        ],
                    ],
                ],
            ],
        ];

        public $model;

        public function run(Builder $query): Builder
        {
            return $this->applyCustomFilter($query);
        }
    };

    $nameWithoutOperator = new class
    {
        use QueryFilters;

        public $filters = [
            'custom' => [
                [
                    'filters' => [
                        [
                            'name' => 'title',
                            'operator' => '',
                            'value' => '',
                        ],
                    ],
                ],
            ],
        ];

        public $model;

        public function run(Builder $query): Builder
        {
            return $this->applyCustomFilter($query);
        }
    };

    $operatorQuery = Post::query();
    $operatorWithoutName->run($operatorQuery);

    $nameQuery = Post::query();
    $nameWithoutOperator->run($nameQuery);

    expect($operatorQuery->toSql())->toContain('1 = 0')
        ->and($operatorQuery->count())->toBe(0)
        ->and($nameQuery->toSql())->toContain('1 = 0')
        ->and($nameQuery->count())->toBe(0);
});
