<div class="aura-sidebar aura-sidebar-type-{{ $this->sidebarType }} aura-darkmode-type-{{ $this->darkmodeType }}
        @if($this->darkmodeType == 'auto')
            aura-sidebar-darkmode-type-{{ $this->sidebarDarkmodeType }}
        @endif
        @if($this->sidebarType == 'light')
            light
        @endif
        ">
    <style>
        @media screen and (max-width: 767px) {
            .mobile-load-hidden {
                display: none !important;
            }
        }
    </style>

    <div
        x-data="{
        desktopSidebarOpen: {{ $this->sidebarToggled ? 'true' : 'false' }},
        mobileSidebarOpen: false,
        isDesktop: window.innerWidth >= 768,
        get sidebarToggled() {
            return this.isDesktop ? this.desktopSidebarOpen : this.mobileSidebarOpen;
        },
        init() {
            const storedValue = this.readSidebarPreference();

            if (storedValue === null) {
                this.persistSidebarPreference();
            } else {
                this.desktopSidebarOpen = storedValue === 'true';
            }

            this.syncViewport();

            this.$nextTick(() => {
                document.querySelectorAll('.mobile-load-hidden').forEach((el) => {
                    el.classList.remove('mobile-load-hidden');
                });
            });
        },
        persistSidebarPreference() {
            try {
                localStorage.setItem('leftSidebar', String(this.desktopSidebarOpen));
            } catch (error) {
                return false;
            }

            return true;
        },
        readSidebarPreference() {
            try {
                return localStorage.getItem('leftSidebar');
            } catch (error) {
                return null;
            }
        },
        syncViewport() {
            this.isDesktop = window.innerWidth >= 768;

            if (!this.isDesktop) {
                this.mobileSidebarOpen = false;
            }
        },
        toggleSidebar() {
            if (this.isDesktop) {
                this.desktopSidebarOpen = !this.desktopSidebarOpen;
                this.persistSidebarPreference();
            } else {
                this.mobileSidebarOpen = !this.mobileSidebarOpen;
            }
        }
    }"
        x-on:resize.window.debounce.150ms="syncViewport()"
        x-bind:data-sidebar-open="sidebarToggled ? 'true' : 'false'"
        data-sidebar-state
    >

        <div class="flex justify-between px-5 py-3 md:hidden">
            <div>
                @include('aura::navigation.logo')
            </div>

            <!-- Button to toggle the sidebar -->
            <button
                x-on:click="toggleSidebar()"
                x-bind:aria-expanded="sidebarToggled"
                type="button"
                data-sidebar-toggle-mobile
            >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 12H15M3 6H21M3 18H21" stroke="currentcolor" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
                <span class="sr-only">Toggle sidebar</span>
            </button>

        </div>

        <div
            class="relative mobile-load-hidden overflow-x-visible flex-shrink-0 aura-navigation transition-[width] duration-150 ease-out motion-reduce:transition-none"
            x-bind:class="{
                'open-sidebar {{ $this->compact ? 'md:w-56' : 'md:w-72' }}': sidebarToggled,
                'closed-sidebar w-20': !sidebarToggled,
            }"
            x-bind:data-sidebar-open="sidebarToggled ? 'true' : 'false'"
            data-sidebar-layout
        >
        {{-- <div class="z-[2000] absolute top-0 transform translate-y-[0%] translate-x-[0%] right-0 flex">
            <button
                @click="toggleSidebar()"
                type="button"
                class="inline-flex relative justify-center items-center w-6 h-6 text-sm font-semibold text-white bg-transparent rounded-full border-none shadow-none opacity-30 select-none hover:opacity-100 focus:outline-none focus:ring-2"
            >
                <div class="hide-collapsed">
                    <x-aura::icon icon="minus" size="xs"/>
                </div>

                <div class="show-collapsed">
                    <x-aura::icon icon="plus" size="xs"/>
                </div>

            </button>
        </div> --}}
            <div
                class="aura-sidebar-bg fixed top-0 left-0 z-10 flex flex-col flex-shrink-0 h-screen border-r shadow-xl transition-[width] duration-150 ease-out motion-reduce:transition-none"
                x-bind:class="{
                '{{ $this->compact ? 'w-56' : 'w-72' }}': sidebarToggled,
                'w-20': !sidebarToggled,
            }"
                x-bind:data-sidebar-open="sidebarToggled ? 'true' : 'false'"
                data-sidebar-panel
            >

                <div class="flex overflow-y-auto overflow-x-visible flex-col flex-1 px-0 pt-0 pb-5 space-y-1 aura-sidebar-scrollbar">

                    <div class="flex flex-col {{ $this->compact ? 'px-2' : 'px-4' }} space-y-1">
                        <div class="flex-shrink-0 h-[4.5rem] flex items-center justify-between">

                            <div class="hide-collapsed">
                                @include('aura::navigation.logo')
                            </div>
                            <div class="hide-collapsed">
                                <button
                                    @click="toggleSidebar()"
                                    x-bind:aria-expanded="sidebarToggled"
                                    type="button"
                                    class="inline-flex relative justify-center items-center w-6 h-6 text-sm font-semibold rounded-lg shadow-none select-none focus:outline-none focus:ring-2 aura-sidebar-toggle"
                                    data-sidebar-toggle-collapse
                                >
                                    <div>
                                        <x-aura::icon icon="minus" size="sm"/>
                                    </div>
                                </button>
                            </div>

                            <div class="w-full show-collapsed">
                                <div class="flex justify-center items-center w-full">
                                    <button
                                        @click="toggleSidebar()"
                                        x-bind:aria-expanded="sidebarToggled"
                                        type="button"
                                        class="inline-flex relative justify-center items-center w-6 h-6 text-sm font-semibold rounded-lg shadow-none select-none focus:outline-none focus:ring-2 aura-sidebar-toggle"
                                        data-sidebar-toggle-expand
                                    >
                                        <div>
                                            <x-aura::icon icon="plus" size="sm"/>
                                        </div>
                                    </button>
                                </div>
                            </div>

                        </div>

                        @if(config('aura.features.global_search'))
                            <button type="button" @click="$dispatch('search')"
                                    class="hidden items-center py-1.5 pr-3 pl-2.5 w-full text-sm leading-6 rounded-lg ring-1 shadow-sm aura-sidebar-search lg:flex">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true" class="flex-none mr-2.5 w-5 h-5">
                                    <path d="m19 19-3.5-3.5" stroke="currentColor" stroke-width="2"
                                          stroke-linecap="round" stroke-linejoin="round"></path>
                                    <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"></circle>
                                </svg>
                                {{ __('Search') }}
                                <span class="flex-none pl-3 ml-auto font-medium text-2xs opacity-80">⌘K</span>
                            </button>
                        @endif

                    </div>

                    <div class="flex flex-col {{ $this->compact ? 'px-2' : 'px-4' }} space-y-1">

                        @includeIf('navigation.before')

                        @include('aura::navigation.index')

                        @includeIf('navigation.after')

                    </div>
                </div>

                <div class=" flex-shrink-0 {{ $this->compact ? 'px-2' : 'px-4' }} min-h-[4.5rem] py-2 flex flex-wrap items-center border-t aura-sidebar-footer">
                    @impersonating($guard = null)
                    <div class="w-full">
                        <x-aura::button.primary :href="route('impersonate.leave')" class="my-2 w-full" size="xs">
                        <x-slot:icon>
                            <x-aura::icon icon="user-impersonate" size="xs"/>
                        </x-slot:icon>
                        <span>{{ __('Leave Impersonation') }}</span>
                    </x-aura::button.primary>
                    </div>
                    @endImpersonating

                    @if(config('aura.teams') && Auth::user()->currentTeam)
                        <div class="flex justify-between items-center pb-20 w-full md:pb-0">
                            <x-aura::navigation.team-switcher>
                                <x-slot:title>
                                    <div class="block min-w-0 flex-1 group">
                                        <div class="flex min-w-0 items-center">
                                            <div class="shrink-0">
                                                <img class="inline-block object-cover w-9 h-9 rounded-full"
                                                     src="{{ auth()->user()->avatarUrl }}" alt="">
                                            </div>
                                            <div class="ml-3 min-w-0 hide-collapsed">
                                                <p class="truncate text-sm font-medium aura-sidebar-user-name">{{ Auth::user()->name }}</p>
                                                <p class="truncate text-xs font-medium aura-sidebar-team-name">{{ Auth::user()->currentTeam->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </x-slot:title>

                                @include('aura::navigation.footer-popup')

                            </x-aura::navigation.team-switcher>

                            {{-- @if(config('aura.features.notifications'))
                                <div class="ml-2">
                                    <x-aura::tippy text="{{ __('Notifications') }}">
                                        <x-aura::button.primary @click="$wire.dispatch('openSlideOver', { target: 'notifications', parameters: {} })"
                                                                class="my-2 w-full" size="xs">
                                            <x-aura::icon icon="notifications" size="xs"/>
                                        </x-aura::button.primary>
                                    </x-aura::tippy>
                                </div>
                            @endif --}}
                        </div>
                    @else
                        <div class="flex justify-between items-center w-full">

                            <x-aura::navigation.team-switcher>
                                <x-slot:title>
                                    <div class="block min-w-0 flex-1 group">
                                        <div class="flex min-w-0 items-center">
                                            <div class="shrink-0">
                                                <img class="inline-block object-cover w-9 h-9 rounded-full"
                                                    src="{{ auth()->user()->avatarUrl }}" alt="">
                                            </div>
                                            <div class="ml-3 min-w-0 hide-collapsed">
                                                <p class="truncate text-sm font-medium aura-sidebar-user-name">{{ Auth::user()->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </x-slot:title>

                                @include('aura::navigation.footer-popup')

                            </x-aura::navigation.team-switcher>

                            @if(config('aura.features.notifications'))
                                <div class="ml-2">
                                    <x-aura::tippy text="{{ __('Notifications') }}">
                                        <x-aura::button.primary @click="$wire.dispatch('openSlideOver', { target: 'notifications', parameters: {} })"
                                                                class="my-2 w-full" size="xs">
                                            <x-aura::icon icon="notifications" size="xs"/>
                                        </x-aura::button.primary>
                                    </x-aura::tippy>
                                </div>
                            @endif
                        </div>
                    @endif

                </div>
            </div>
        </div>


    </div>
</div>
