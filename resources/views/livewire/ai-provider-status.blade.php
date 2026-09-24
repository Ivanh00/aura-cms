<div>
    @if(! app(\Aura\Base\Ai\AiStatus::class)->enabled())
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('AI features are disabled by AURA_AI_ENABLED.') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Configured providers') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Provider settings come from config/ai.php and your environment. Run php artisan config:cache after changing them when configuration is cached.') }}
            </p>
        </div>

        @if($this->providers() === [])
            <div class="px-5 py-8 text-center">
                <p class="font-medium text-gray-900 dark:text-white">{{ __('None configured') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Add provider credentials to the application environment to enable AI features.') }}</p>
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->providers() as $provider)
                    <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between" wire:key="ai-provider-{{ $provider['name'] }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $provider['label'] }}</p>
                                @if($provider['default'])
                                    <span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-300">{{ __('Default') }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Driver: :driver', ['driver' => $provider['driver']]) }}
                                @if($provider['endpoint'])
                                    <span aria-hidden="true"> · </span>{{ $provider['endpoint'] }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Credentials set: :value', ['value' => $provider['credentials'] ? __('Yes') : __('No')]) }}
                            </p>
                            @if(isset($results[$provider['name']]))
                                <p @class([
                                    'mt-2 text-sm',
                                    'text-green-700 dark:text-green-300' => $results[$provider['name']]['successful'],
                                    'text-red-700 dark:text-red-300' => ! $results[$provider['name']]['successful'],
                                ])>
                                    {{ $results[$provider['name']]['message'] }}
                                    @if($results[$provider['name']]['duration_ms'] !== null)
                                        {{ __('(:duration ms)', ['duration' => $results[$provider['name']]['duration_ms']]) }}
                                    @endif
                                </p>
                            @endif
                        </div>

                        <x-aura::button.border
                            type="button"
                            wire:click="testProvider('{{ $provider['name'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="testProvider('{{ $provider['name'] }}')"
                        >
                            <span wire:loading wire:target="testProvider('{{ $provider['name'] }}')" class="mr-2">
                                <x-aura::icon.loading />
                            </span>
                            {{ __('Test connection') }}
                        </x-aura::button.border>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
