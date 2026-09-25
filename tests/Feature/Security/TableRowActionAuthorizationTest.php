<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

/**
 * Test resource that declares a single legitimate row action.
 */
class SecurityRowActionModel extends Resource
{
    public array $actions = [
        'delete' => [
            'label' => 'Delete',
            'ability' => 'delete',
        ],
    ];

    public static $singularName = 'SecurityRow';

    public static ?string $slug = 'securityrow';

    public static string $type = 'SecurityRow';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'searchable' => true,
                'slug' => 'title',
            ],
        ];
    }
}

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([SecurityRowActionModel::class]);
    Aura::setModel(new SecurityRowActionModel);
    Cache::clear();
});

test('table row action rejects a forged undeclared action name', function () {
    $this->actingAs(createSuperAdmin());

    $record = SecurityRowActionModel::create(['title' => 'Stay put']);

    livewire(Table::class, ['query' => null, 'model' => $record])
        ->call('action', ['action' => 'forceDelete', 'id' => $record->id])
        ->assertStatus(403);

    expect(SecurityRowActionModel::whereKey($record->id)->exists())->toBeTrue();
});

test('table row action blocks delete when the user is not authorized', function () {
    // Limited admin (Editor role) has no delete permission for this resource.
    $this->actingAs(createAdmin());

    $record = SecurityRowActionModel::create(['title' => 'Protected']);

    livewire(Table::class, ['query' => null, 'model' => $record])
        ->call('action', ['action' => 'delete', 'id' => $record->id])
        ->assertStatus(403);

    expect(SecurityRowActionModel::whereKey($record->id)->exists())->toBeTrue();
});

test('table row action resolves the record only inside the current table scope', function () {
    // Cross-team record must not be reachable via bare model find — the table
    // scope (TeamScope + type) has to own the lookup.
    $this->actingAs(createSuperAdmin());

    $own = SecurityRowActionModel::create(['title' => 'Own team row']);

    $otherTeam = foreignTeam();

    $foreign = SecurityRowActionModel::withoutGlobalScopes()->create([
        'title' => 'Foreign team row',
        'team_id' => $otherTeam->id,
        'type' => SecurityRowActionModel::$type,
    ]);

    expect(SecurityRowActionModel::whereKey($own->id)->exists())->toBeTrue();
    // Foreign row is invisible under the acting team's TeamScope.
    expect(SecurityRowActionModel::whereKey($foreign->id)->exists())->toBeFalse();

    // Custom row action: exact selection inside scope fails closed.
    livewire(Table::class, ['query' => null, 'model' => $own])
        ->call('action', ['action' => 'delete', 'id' => $foreign->id])
        ->assertHasErrors(['selected']);

    expect(SecurityRowActionModel::withoutGlobalScopes()->whereKey($foreign->id)->exists())->toBeTrue();
    expect(SecurityRowActionModel::whereKey($own->id)->exists())->toBeTrue();
})->skip(fn () => ! config('aura.teams'), 'Cross-team scoped find requires teams.');

test('table view action uses scoped find and never bare model find', function () {
    $this->actingAs(createSuperAdmin());

    $own = SecurityRowActionModel::create(['title' => 'Visible']);

    $otherTeam = foreignTeam();

    $foreign = SecurityRowActionModel::withoutGlobalScopes()->create([
        'title' => 'Hidden',
        'team_id' => $otherTeam->id,
        'type' => SecurityRowActionModel::$type,
    ]);

    // Out-of-scope id must 404 via firstOrFail on the scoped query.
    livewire(Table::class, ['query' => null, 'model' => $own])
        ->call('action', ['action' => 'view', 'id' => $foreign->id])
        ->assertStatus(404);

    // In-scope id is authorized and redirects.
    livewire(Table::class, ['query' => null, 'model' => $own])
        ->call('action', ['action' => 'view', 'id' => $own->id])
        ->assertRedirect(route('aura.securityrow.view', ['id' => $own->id]));
})->skip(fn () => ! config('aura.teams'), 'Cross-team scoped find requires teams.');

test('table row action runs a declared delete for an authorized user', function () {
    $this->actingAs(createSuperAdmin());

    $record = SecurityRowActionModel::create(['title' => 'Delete me']);

    livewire(Table::class, ['query' => null, 'model' => $record])
        ->call('action', ['action' => 'delete', 'id' => $record->id])
        ->assertHasNoErrors();

    expect(SecurityRowActionModel::whereKey($record->id)->exists())->toBeFalse();
});
