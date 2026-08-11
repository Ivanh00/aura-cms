<x-dynamic-component :component="config('aura.views.login-layout')">

    {{-- Bypass TeamScope: guests have no team, and fail-closed scoping returns no users. --}}
    @php($localAdmin = app(config('aura.resources.user'))::withoutGlobalScopes()->orderBy('id')->first())

    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Welcome back') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Login to your account to continue.') }}</p>
    </div>

    <!-- Session Status -->
    <x-aura::auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-aura::input-label class="!mt-0" for="email" :value="__('Email')" />
            <x-aura::input.text id="email" placeholder="{{ __('Enter your email') }}" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-aura::input-error :for="optional($errors)->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password" :value="__('Password')" />

            <x-aura::input.text id="password" class="block w-full"
                            type="password"
                            name="password"
                            placeholder="{{ __('Enter your password') }}"
                            required autocomplete="current-password" />

            <x-aura::input-error :for="optional($errors)->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-between items-center">
            <!-- Remember Me -->
            <label for="remember_me" class="inline-flex gap-2 items-center">
                <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 shadow-xs text-primary-600 focus:ring-primary-500 dark:border-white/20 dark:bg-gray-800 dark:checked:bg-primary-600 dark:focus:ring-offset-gray-900" name="remember">
                <span class="text-sm text-gray-600 select-none dark:text-gray-300">{{ __('Remember me') }}</span>
            </label>

            <!-- Forgot Password? -->
            @if (Route::has('aura.password.request'))
                <a class="text-sm font-medium rounded-md text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900" href="{{ route('aura.password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="pt-1">
            <x-aura::button type="submit" block>{{ __('Log in') }}</x-aura::button>
        </div>
    </form>

    @if(config('aura.auth.registration'))
        <p class="mt-6 text-sm text-center text-gray-500 dark:text-gray-400">
            {{ __("Don't have an account yet?") }} <a class="font-medium rounded-md text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900" href="/register">{{ __('Register') }}</a>
        </p>
    @endif

    @local
        @if($localAdmin)
            <div class="flex gap-3 items-center mt-8" aria-hidden="true">
                <div class="flex-1 h-px bg-gray-950/5 dark:bg-white/10"></div>
                <span class="text-xs font-medium tracking-wider text-gray-400 uppercase dark:text-gray-500">{{ __('Local') }}</span>
                <div class="flex-1 h-px bg-gray-950/5 dark:bg-white/10"></div>
            </div>

            <div class="mt-4">
                <x-aura::button.border href="{{ route('aura.login-as', ['id' => $localAdmin->id]) }}" size="xs" class="justify-center w-full login-as-admin">
                    <x-aura::icon.user class="mr-2 -ml-1 w-4 h-4" />
                    Admin
                </x-aura::button.border>
            </div>
        @endif
    @endlocal

</x-dynamic-component>
