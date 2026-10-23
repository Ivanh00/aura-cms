<div class="flex flex-col aura-sidebar-groups">
@php
   
@endphp

    @foreach (app('aura')::navigation() as $group => $resources)
        @if ($group !== '')
            <div wire:key="toggle-{{ $group }}" wire:click="toggleGroup('{{ $group }}')"
                class="cursor-pointer">
                <x-aura::navigation.heading :toggled="$this->isToggled($group)" :compact="$this->compact">
                    {{ __($group) }}
                </x-aura::navigation.heading>
            </div>
        @endif

        @if ($this->isToggled($group))
            <div class="flex flex-col {{ $this->compact ? 'space-y-0.5' : 'space-y-1' }}">
                @foreach ($resources as $resource)
                    @if (isset($resource['dropdown']) && $resource['dropdown'] !== false)
                        <x-aura::navigation.dropdown :compact="$this->compact">
                            <x-slot:title>
                                <div class="aura-sidebar-icon">

                                    @php
                                        $iconView = 'aura::aura.navigation.icons.' . Str::slug($resource['dropdown']);
                                    @endphp

                                    @includeIf($iconView, ['class' => 'w-6 h-6'])

                                    @if (!View::exists($iconView) && isset($resource['items'][0]['icon']))
                                        {!! $resource['items'][0]['icon'] !!}
                                    @endif
                                </div>
                                <div class="hide-collapsed">{{ __($resource['dropdown']) }}</div>
                            </x-slot:title>

                            <x-slot:mobile>

                                @foreach($resource['items'] as $r)
                                    <x-aura::navigation.item-dropdown :route="'aura.' . (isset($r['slug']) ? $r['slug'] : '') . '.index'" :id="isset($r['slug']) ? $r['slug'] : ''" :strict="false" :compact="$this->compact">
                                        <div class="aura-sidebar-icon">
                                            @if(isset($r['icon']))
                                                {!! $r['icon'] !!}
                                            @endif
                                        </div>
                                        <div>{{ __(isset($r['name']) ? $r['name'] : '') }}</div>
                                    </x-aura::navigation.item-dropdown>
                                @endforeach

                            </x-slot:mobile>

                            @foreach ($resource['items'] as $r)
                                <x-aura::navigation.item :route="isset($r['route']) ? $r['route'] : '#'" :strict="false" :compact="$this->compact">
                                    <div class="aura-sidebar-icon">
                                        @if(isset($r['icon']))
                                            {!! $r['icon'] !!}
                                        @endif
                                    </div>
                                    <div class="hide-collapsed">{{ __(isset($r['name']) ? $r['name'] : '') }}</div>
                                </x-aura::navigation.item>
                            @endforeach
                        </x-aura::navigation.dropdown>
                    @else
                        @if(isset($resource['resource']))
                            @can('viewAny', app($resource['resource']))
                                <x-aura::navigation.item :route="isset($resource['route']) ? $resource['route'] : '#'" :strict="false" :tooltip="__(isset($resource['name']) ? $resource['name'] : '')" :compact="$this->compact" :badge="$resource['badge'] ?? null" :badgeColor="$resource['badgeColor'] ?? null">
                                    <div class="aura-sidebar-icon">
                                        @if(isset($resource['icon']))
                                            {!! $resource['icon'] !!}
                                        @endif
                                    </div>
                                    <div class="hide-collapsed">{{ __(isset($resource['name']) ? $resource['name'] : '') }}</div>
                                </x-aura::navigation.item>
                            @endcan
                        @else

                            <x-aura::navigation.item :route="isset($resource['route']) ? $resource['route'] : '#'" :strict="false" :tooltip="__(isset($resource['name']) ? $resource['name'] : '')" :compact="$this->compact" :badge="$resource['badge'] ?? null" :badgeColor="$resource['badgeColor'] ?? null" :onclick="$resource['onclick'] ?? ''"

                            >
                                @if(isset($resource['icon']) && $resource['icon'] !== '')
                                    <div class="aura-sidebar-icon">
                                        {!! Blade::render($resource['icon']) !!}
                                    </div>
                                    <div class="hide-collapsed">{{ __(isset($resource['name']) ? $resource['name'] : '') }}</div>
                                @else
                                    <div>
                                        <div class="hidden w-6 text-xl text-center show-collapsed aura-sidebar-icon">
                                            {{ isset($resource['name']) ? strtoupper(substr($resource['name'], 0, 1)) : '' }}
                                        </div>
                                        <div class="hide-collapsed">{{ __(isset($resource['name']) ? $resource['name'] : '') }}</div>
                                    </div>
                                @endif

                            </x-aura::navigation.item>
                        @endif
                    @endif

                @endforeach
            </div>
        @endif
    @endforeach

</div>
