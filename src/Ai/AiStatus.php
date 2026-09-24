<?php

namespace Aura\Base\Ai;

use BackedEnum;
use Illuminate\Support\Str;
use Stringable;
use Throwable;

use function Laravel\Ai\agent;

final class AiStatus
{
    public function configured(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $default = $this->defaultProvider();

        foreach ($this->providers() as $provider) {
            if ($provider['name'] === $default) {
                return $provider['configured'];
            }
        }

        return false;
    }

    public function enabled(): bool
    {
        return (bool) config('aura.ai.enabled', true);
    }

    /**
     * @return list<array{
     *     name: string,
     *     label: string,
     *     driver: string,
     *     endpoint: ?string,
     *     credentials: bool,
     *     configured: bool,
     *     default: bool
     * }>
     */
    public function providers(): array
    {
        $providers = config('ai.providers', []);

        if (! is_array($providers)) {
            return [];
        }

        $default = $this->defaultProvider();
        $result = [];

        foreach ($providers as $name => $provider) {
            if (! is_string($name) || ! is_array($provider)) {
                continue;
            }

            $driver = $this->stringValue($provider['driver'] ?? $name);
            $endpoint = $this->endpoint($provider);
            $credentials = $this->hasCredentials($provider);

            $result[] = [
                'name' => $name,
                'label' => $this->providerLabel($name),
                'driver' => $driver,
                'endpoint' => $this->safeEndpoint($endpoint),
                'credentials' => $credentials,
                'configured' => $this->providerIsConfigured($name, $driver, $endpoint, $credentials, $provider),
                'default' => $name === $default,
            ];
        }

        return $result;
    }

    /** @return array{successful: bool, message: string, duration_ms: ?int} */
    public function test(string $provider): array
    {
        if (! $this->enabled()) {
            return ['successful' => false, 'message' => 'AI features are disabled.', 'duration_ms' => null];
        }

        $status = collect($this->providers())->firstWhere('name', $provider);

        if (! is_array($status) || ! $status['configured']) {
            return ['successful' => false, 'message' => 'This AI provider is not configured.', 'duration_ms' => null];
        }

        $startedAt = microtime(true);

        try {
            agent(instructions: 'Reply with OK only.')
                ->prompt('Test the AI connection.', provider: $provider, timeout: 15);

            return [
                'successful' => true,
                'message' => 'AI connection successful.',
                'duration_ms' => $this->durationSince($startedAt),
            ];
        } catch (Throwable $exception) {
            return [
                'successful' => false,
                'message' => 'AI connection failed: '.$this->redact($exception->getMessage()),
                'duration_ms' => $this->durationSince($startedAt),
            ];
        }
    }

    private function defaultProvider(): ?string
    {
        $default = config('ai.default');

        return is_string($default) || $default instanceof Stringable || $default instanceof BackedEnum
            ? $this->stringValue($default)
            : null;
    }

    private function durationSince(float $startedAt): int
    {
        return max(1, (int) round((microtime(true) - $startedAt) * 1000));
    }

    /** @param  array<string, mixed>  $provider */
    private function endpoint(array $provider): ?string
    {
        foreach (['url', 'endpoint'] as $key) {
            if (is_string($provider[$key] ?? null) && filled($provider[$key])) {
                return $provider[$key];
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $provider */
    private function hasCredentials(array $provider): bool
    {
        foreach (['key', 'api_key', 'token', 'access_key_id', 'secret_access_key', 'session_token'] as $key) {
            if (is_string($provider[$key] ?? null) && filled($provider[$key])) {
                return true;
            }
        }

        return false;
    }

    /** @param  array<string, mixed>  $provider */
    private function providerIsConfigured(
        string $name,
        string $driver,
        ?string $endpoint,
        bool $credentials,
        array $provider,
    ): bool {
        if ($driver === 'bedrock') {
            return filled($provider['key'] ?? null)
                || (filled($provider['access_key_id'] ?? null)
                    && filled($provider['secret_access_key'] ?? null));
        }

        if ($credentials) {
            return true;
        }

        if ($driver === 'openai-compatible') {
            return filled($endpoint);
        }

        if ($driver === 'ollama') {
            return $name !== 'ollama'
                ? filled($endpoint)
                : filled($endpoint) && $endpoint !== 'http://localhost:11434';
        }

        return false;
    }

    private function providerLabel(string $name): string
    {
        return match ($name) {
            'openai' => 'OpenAI',
            'openai-compatible' => 'OpenAI-compatible',
            'openrouter' => 'OpenRouter',
            'voyageai' => 'Voyage AI',
            'xai' => 'xAI',
            default => Str::headline($name),
        };
    }

    private function redact(string $message): string
    {
        $secrets = [];

        $providers = config('ai.providers', []);
        $providers = is_array($providers) ? $providers : [];

        array_walk_recursive($providers, function (mixed $value, string|int $key) use (&$secrets): void {
            if (is_string($key)
                && preg_match('/(?:key|secret|token|password)/i', $key) === 1
                && is_string($value)
                && $value !== '') {
                $secrets[] = $value;
            }
        });

        $message = str_replace(array_unique($secrets), '[redacted]', $message);
        $message = preg_replace('/(Bearer\s+)[^\s,;]+/i', '$1[redacted]', $message) ?? $message;

        return preg_replace('/([?&](?:api[_-]?key|key|token)=)[^&\s]+/i', '$1[redacted]', $message) ?? $message;
    }

    private function safeEndpoint(?string $endpoint): ?string
    {
        if ($endpoint === null) {
            return null;
        }

        $parts = parse_url($endpoint);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return Str::before(Str::before($endpoint, '?'), '#');
        }

        return $parts['scheme'].'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '');
    }

    private function stringValue(mixed $value): string
    {
        return match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof Stringable => (string) $value,
            is_string($value) => $value,
            default => '',
        };
    }
}
