<?php

use Aura\Base\Ai\AiStatus;
use Aura\Base\Livewire\AiProviderStatus;
use Aura\Base\Livewire\Settings;
use Aura\Base\Providers\AppServiceProvider;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\AnonymousAgent;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'aura.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers' => [
            'openai' => [
                'driver' => 'openai',
                'key' => null,
                'url' => 'https://api.openai.com/v1',
            ],
            'anthropic' => [
                'driver' => 'anthropic',
                'key' => null,
                'url' => 'https://api.anthropic.com/v1',
            ],
            'openai-compatible' => [
                'driver' => 'openai-compatible',
                'key' => null,
                'url' => null,
            ],
            'ollama' => [
                'driver' => 'ollama',
                'key' => '',
                'url' => 'http://localhost:11434',
            ],
        ],
    ]);
});

test('no environment configuration leaves AI unavailable', function () {
    $status = app(AiStatus::class);

    expect($status->configured())->toBeFalse()
        ->and(collect($status->providers())->where('configured', true))->toBeEmpty();

    $this->actingAs(globalAdminWithTeam());

    Livewire::test(Settings::class, ['page' => 'ai'])
        ->assertSet('canUpdate', false)
        ->assertSee('None configured')
        ->assertDontSee('Save')
        ->call('save')
        ->assertForbidden();
});

test('configured providers are reported without exposing credentials', function () {
    config([
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'must-never-render',
    ]);

    $status = app(AiStatus::class);
    $openAi = collect($status->providers())->firstWhere('name', 'openai');

    expect($status->configured())->toBeTrue()
        ->and($openAi)->toMatchArray([
            'driver' => 'openai',
            'credentials' => true,
            'configured' => true,
            'default' => true,
        ])
        ->and($openAi)->not->toContain('must-never-render');

    $this->actingAs(globalAdminWithTeam());

    Livewire::test(Settings::class, ['page' => 'ai'])
        ->assertSee('OpenAI')
        ->assertSee('Credentials set: Yes')
        ->assertDontSee('must-never-render')
        ->assertDontSee('Save')
        ->assertDontSeeHtml('<input');
});

test('provider connection tests use the Laravel AI SDK', function () {
    config(['ai.providers.openai.key' => 'test-key']);
    AnonymousAgent::fake(['OK']);

    $this->actingAs(globalAdminWithTeam());

    Livewire::test(AiProviderStatus::class)
        ->call('testProvider', 'openai')
        ->assertSet('results.openai.successful', true)
        ->assertSet('results.openai.message', 'AI connection successful.')
        ->assertSet('results.openai.duration_ms', fn (int $duration): bool => $duration >= 1);

    AnonymousAgent::assertPrompted('Test the AI connection.');
});

test('provider connection failures redact configured credentials', function () {
    config(['ai.providers.openai.key' => 'must-never-leak']);
    AnonymousAgent::fake(function (): never {
        throw new RuntimeException('Rejected key must-never-leak');
    });

    $result = app(AiStatus::class)->test('openai');

    expect($result['successful'])->toBeFalse()
        ->and($result['message'])->toContain('[redacted]')
        ->and($result['message'])->not->toContain('must-never-leak');
});

test('the Aura AI kill switch disables the default provider', function () {
    config([
        'aura.ai.enabled' => false,
        'ai.providers.openai.key' => 'test-key',
    ]);

    expect(app(AiStatus::class)->configured())->toBeFalse()
        ->and(app(AiStatus::class)->test('openai'))->toMatchArray([
            'successful' => false,
            'message' => 'AI features are disabled.',
        ]);
});

test('only global admins can view or test AI provider status', function () {
    config(['ai.providers.openai.key' => 'test-key']);
    app()->register(AppServiceProvider::class);

    $teamAdmin = createSuperAdmin();
    $this->actingAs($teamAdmin);

    $this->get(route('aura.settings.page', 'ai'))->assertForbidden();
    Livewire::test(Settings::class, ['page' => 'ai'])->assertForbidden();
    Livewire::test(AiProviderStatus::class)->assertForbidden();

    expect(collect(Aura\Base\Facades\Aura::navigation()->get('settings'))->pluck('name'))
        ->not->toContain('AI');

    $globalAdmin = globalAdminWithTeam();
    $this->actingAs($globalAdmin);

    $this->get(route('aura.settings.page', 'ai'))->assertOk();
    Livewire::test(AiProviderStatus::class)->assertOk();
    expect(Gate::forUser($globalAdmin)->allows(User::GLOBAL_ADMIN_GATE))->toBeTrue();
});

function globalAdminWithTeam(): User
{
    $user = createSuperAdmin();
    $user->forceFill(['global_admin' => true])->saveQuietly();

    return $user->refresh();
}
