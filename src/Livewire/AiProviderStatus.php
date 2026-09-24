<?php

namespace Aura\Base\Livewire;

use Aura\Base\Ai\AiStatus;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AiProviderStatus extends Component
{
    /** @var array<string, array{successful: bool, message: string, duration_ms: ?int}> */
    public array $results = [];

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    /** @return list<array<string, bool|string|null>> */
    public function providers(): array
    {
        return array_values(array_filter(
            app(AiStatus::class)->providers(),
            static fn (array $provider): bool => $provider['configured'],
        ));
    }

    public function render()
    {
        $this->authorizeAccess();

        return view('aura::livewire.ai-provider-status');
    }

    public function testProvider(AiStatus $status, string $provider): void
    {
        $this->authorizeAccess();

        $result = $status->test($provider);
        $this->results[$provider] = $result;

        $this->dispatch(
            'notify',
            message: $result['message'],
            type: $result['successful'] ? 'success' : 'error',
        );
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->check() && Gate::allows(User::GLOBAL_ADMIN_GATE), 403);
    }
}
