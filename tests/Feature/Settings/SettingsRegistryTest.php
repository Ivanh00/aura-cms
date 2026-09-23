<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Settings;
use Aura\Base\Providers\AppServiceProvider;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\User;
use Aura\Base\Settings\SettingsPage;
use Aura\Base\Settings\SettingsRegistry;
use Aura\Base\Settings\SettingsStore;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('plugins register ordered settings pages through Aura', function () {
    // The package TestCase does not load the auto-discovered provider that owns the sidebar items.
    app()->register(AppServiceProvider::class);

    Aura::registerSettingsPages('acme/seo', [
        new SettingsPage(
            slug: 'seo',
            title: 'SEO',
            fields: [[
                'name' => 'Site Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'seo-site-name',
            ]],
            icon: 'search',
            order: 25,
        ),
    ]);

    $registry = app(SettingsRegistry::class);

    expect($registry->pages())
        ->sequence(
            fn ($page) => $page->slug->toBe('general'),
            fn ($page) => $page->slug->toBe('seo'),
            fn ($page) => $page->slug->toBe('ai'),
        );

    Livewire::test(Settings::class)
        ->assertDontSee('Site Name')
        ->set('form.fields.color-palette', 'blue')
        ->call('save');

    Livewire::test(Settings::class, ['page' => 'seo'])
        ->assertSee('SEO')
        ->assertSee('Site Name')
        ->set('form.fields.seo-site-name', 'Aura Site')
        ->call('save');

    expect(Option::first()->value)
        ->toMatchArray(['seo-site-name' => 'Aura Site', 'color-palette' => 'blue']);

    $this->get(route('aura.settings.page', 'seo'))->assertOk();
    $this->get(route('aura.settings.page', 'unknown'))->assertNotFound();

    expect(collect(Aura::navigation()->get('settings'))->pluck('route'))
        ->toContain(route('aura.settings.page', 'seo'));
});

test('plugin settings pages enforce separate view and update abilities', function () {
    Aura::registerSettingsPages('acme/seo', [
        new SettingsPage(
            slug: 'seo',
            title: 'SEO',
            fields: [[
                'name' => 'Site Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'seo-site-name',
            ]],
            viewAbility: 'settings.seo.view',
            updateAbility: 'settings.seo.update',
        ),
    ]);

    $viewer = createAdmin();
    $editor = User::factory()->create(['current_team_id' => $viewer->current_team_id]);
    $denied = User::factory()->create(['current_team_id' => $viewer->current_team_id]);

    Gate::define('settings.seo.view', fn ($user): bool => in_array($user->id, [$viewer->id, $editor->id], true));
    Gate::define('settings.seo.update', fn ($user): bool => $user->id === $editor->id);

    $this->actingAs($viewer);

    $this->get(route('aura.settings.page', 'seo'))->assertOk();

    Livewire::test(Settings::class, ['page' => 'seo'])
        ->assertSet('canUpdate', false)
        ->assertDontSee('Save')
        ->set('form.fields.seo-site-name', 'Not allowed')
        ->call('save')
        ->assertForbidden();

    expect(Aura::setting('seo-site-name'))->not->toBe('Not allowed');

    $this->actingAs($editor);

    Livewire::test(Settings::class, ['page' => 'seo'])
        ->assertSet('canUpdate', true)
        ->assertSee('Save')
        ->set('form.fields.seo-site-name', 'Aura Site')
        ->call('save')
        ->assertHasNoErrors();

    expect(Aura::setting('seo-site-name'))->toBe('Aura Site');

    $this->actingAs($denied);

    $this->get(route('aura.settings.page', 'seo'))->assertForbidden();
    Livewire::test(Settings::class, ['page' => 'seo'])->assertForbidden();
});

test('settings pages without declared abilities remain restricted to super admins', function () {
    Aura::registerSettingsPages('acme/private', [
        new SettingsPage('private', 'Private', [[
            'name' => 'Private value',
            'type' => 'Aura\\Base\\Fields\\Text',
            'slug' => 'private-value',
        ]]),
    ]);

    $this->actingAs(createAdmin());

    $this->get(route('aura.settings.page', 'private'))->assertForbidden();
    Livewire::test(Settings::class, ['page' => 'private'])->assertForbidden();
});

test('settings are readable by team id without an authenticated user', function () {
    Aura::registerSettingsPages('acme/seo', [
        new SettingsPage('seo', 'SEO', [[
            'name' => 'Site Name',
            'type' => 'Aura\\Base\\Fields\\Text',
            'slug' => 'seo-site-name',
        ]]),
    ]);

    $store = app(SettingsStore::class);
    $store->put('seo-site-name', 'Aura Site');
    $store->put('ai-api-key', 'super-secret-key');
    $teamId = $this->user->current_team_id;

    auth()->logout();

    // Without teams there is one global settings row, which needs no team to be read.
    expect(Aura::setting('seo-site-name'))->toBe(config('aura.teams') ? null : 'Aura Site')
        ->and(Aura::setting('seo-site-name', teamId: $teamId))->toBe('Aura Site')
        ->and($store->all())->toHaveCount(1)
        ->and($store->all()[0]['team_id'])->toBe(config('aura.teams') ? $teamId : null)
        ->and($store->all()[0]['values'])->toHaveKey('seo-site-name')->not->toHaveKey('ai-api-key');
});

test('the registry rejects page and field collisions', function () {
    $registry = new SettingsRegistry;
    $page = new SettingsPage('one', 'One', [[
        'name' => 'Shared',
        'type' => 'Aura\\Base\\Fields\\Text',
        'slug' => 'shared-setting',
    ]]);
    $registry->register('acme/one', [$page]);
    $registry->register('acme/one', [$page]);

    expect($registry->pages())->toHaveCount(1);

    expect(fn () => $registry->register('acme/two', [
        new SettingsPage('two', 'Two', [[
            'name' => 'Shared again',
            'type' => 'Aura\\Base\\Fields\\Text',
            'slug' => 'shared-setting',
        ]]),
    ]))->toThrow(InvalidArgumentException::class, 'already registered by page [one]')
        ->and(fn () => $registry->register('acme/two', [
            new SettingsPage('one', 'Duplicate page', [[
                'name' => 'Different field',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'different-setting',
            ]]),
        ]))->toThrow(InvalidArgumentException::class, 'already registered');
});

test('secret settings are encrypted write only and preserved by blank submissions', function () {
    Livewire::test(Settings::class, ['page' => 'ai'])
        ->assertSet('form.fields.ai-api-key', '')
        ->set('form.fields.ai-api-key', 'super-secret-key')
        ->call('save')
        ->assertSet('form.fields.ai-api-key', '')
        ->assertSet('secretConfigured.ai-api-key', true);

    $option = Option::first();
    $encrypted = $option->value['ai-api-key'];

    expect($encrypted)->toStartWith('encrypted:')
        ->not->toContain('super-secret-key')
        ->and(Crypt::decryptString(str($encrypted)->after('encrypted:')->toString()))->toBe('super-secret-key')
        ->and(Aura::setting('ai-api-key'))->toBe('super-secret-key');

    Livewire::test(Settings::class)
        ->set('form.fields.color-palette', 'blue')
        ->call('save');

    Livewire::test(Settings::class, ['page' => 'ai'])
        ->set('form.fields.ai-model', 'some-model')
        ->call('save');

    $option->refresh();

    expect($option->value['ai-api-key'])->toBe($encrypted)
        ->and($option->value['color-palette'])->toBe('blue');

    Livewire::test(Settings::class, ['page' => 'ai'])
        ->assertSet('form.fields.ai-api-key', '')
        ->assertSet('secretConfigured.ai-api-key', true);
});
