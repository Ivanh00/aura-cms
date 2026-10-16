<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Creates an isolated config/.env pair and points the application at it for the
 * duration of the callback.
 */
function withTemporaryAuraConfig(string $configContents, Closure $callback, string $envContents = "APP_ENV=testing\n"): void
{
    $temporaryPath = storage_path('framework/testing/aura-install-'.Str::uuid());
    $originalConfigPath = app()->configPath();
    $originalEnvironmentPath = app()->environmentPath();
    $originalBootstrapPath = app()->bootstrapPath();
    $originalAuraConfig = config('aura');

    File::ensureDirectoryExists($temporaryPath);
    File::put($temporaryPath.'/aura.php', $configContents);
    File::put($temporaryPath.'/.env', $envContents);

    app()->useConfigPath($temporaryPath);
    app()->useEnvironmentPath($temporaryPath);
    app()->useBootstrapPath($temporaryPath);

    try {
        $callback($temporaryPath);
    } finally {
        config(['aura' => $originalAuraConfig]);
        app()->useConfigPath($originalConfigPath);
        app()->useEnvironmentPath($originalEnvironmentPath);
        app()->useBootstrapPath($originalBootstrapPath);
        File::deleteDirectory($temporaryPath);
    }
}

function publishedAuraConfig(): string
{
    return File::get(__DIR__.'/../../../config/aura.php');
}

describe('config installation command', function () {
    it('clears cached configuration after saving settings', function () {
        withTemporaryAuraConfig(publishedAuraConfig(), function (string $path) {
            File::ensureDirectoryExists(dirname(app()->getCachedConfigPath()));
            File::put(app()->getCachedConfigPath(), '<?php return [];');

            $this->artisan('aura:install-config', [
                '--no-interaction' => true,
                '--teams' => 'false',
                '--registration' => 'false',
            ])->assertSuccessful();

            expect(File::exists(app()->getCachedConfigPath()))->toBeFalse()
                ->and(config('aura.teams'))->toBeFalse();
        });
    });

    it('command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:install-config', $commands))->toBeTrue();
    });

    it('configures teams and registration without interaction', function () {
        withTemporaryAuraConfig(<<<'PHP'
<?php

return [
    'teams' => true,
    'features' => [],
    'auth' => [
        'registration' => true,
    ],
    'theme' => [],
];
PHP, function (string $path) {
            $this->artisan('aura:install-config', [
                '--no-interaction' => true,
                '--teams' => 'false',
                '--registration' => 'false',
            ])->assertSuccessful();

            $config = include $path.'/aura.php';

            expect($config['teams'])->toBeFalse()
                ->and($config['auth']['registration'])->toBeFalse()
                ->and(config('aura.teams'))->toBeFalse()
                ->and(config('aura.auth.registration'))->toBeFalse();
        });
    });

    it('keeps env calls, comments and parentheses in the published config intact', function () {
        withTemporaryAuraConfig(publishedAuraConfig(), function (string $path) {
            $before = File::get($path.'/aura.php');

            $this->artisan('aura:install-config', [
                '--no-interaction' => true,
                '--teams' => 'false',
                '--registration' => 'false',
            ])->assertSuccessful();

            $after = File::get($path.'/aura.php');

            // Env-backed settings never touch the config file.
            expect($after)->toBe($before)
                ->and(substr_count($after, 'env('))->toBe(substr_count($before, 'env('))
                ->and($after)->toContain("env('AURA_TEAMS', true)")
                ->and($after)->toContain("env('AURA_REGISTRATION', true)")
                ->and($after)->toContain("env('AURA_PATH', 'admin')")
                ->and($after)->toContain("env('AURA_DOMAIN')")
                ->and($after)->toContain("env('AURA_CREATE_TEAMS', true)")
                ->and($after)->toContain('| You can customise the Aura theme')
                ->and($after)->toContain("'primary' => 'var(--primary-600)'")
                ->and(config('aura.teams'))->toBeFalse()
                ->and(config('aura.auth.registration'))->toBeFalse();

            $env = File::get($path.'/.env');

            expect($env)->toContain('AURA_TEAMS=false')
                ->and($env)->toContain('AURA_REGISTRATION=false')
                ->and($env)->toContain('APP_ENV=testing');
        });
    });

    it('replaces an existing env key without duplicating it', function () {
        withTemporaryAuraConfig(publishedAuraConfig(), function (string $path) {
            $this->artisan('aura:install-config', [
                '--no-interaction' => true,
                '--teams' => 'false',
                '--registration' => 'true',
            ])->assertSuccessful();

            $env = File::get($path.'/.env');

            expect(substr_count($env, 'AURA_TEAMS='))->toBe(1)
                ->and(substr_count($env, 'AURA_REGISTRATION='))->toBe(1)
                ->and($env)->toContain('AURA_TEAMS=false')
                ->and($env)->toContain('AURA_REGISTRATION=true')
                ->and($env)->toContain('APP_KEY=base64:existing')
                ->and($env)->toContain('APP_DEBUG=true');
        }, "APP_ENV=testing\nAPP_KEY=base64:existing\nAURA_TEAMS=true\nAPP_DEBUG=true\n");
    });

    it('rewrites only the targeted theme line', function () {
        withTemporaryAuraConfig(publishedAuraConfig(), function (string $path) {
            $before = File::get($path.'/aura.php');

            $this->artisan('aura:install-config')
                ->expectsConfirmation('Do you want to use teams?', 'yes')
                ->expectsConfirmation('Do you want to modify the default features?', 'no')
                ->expectsConfirmation('Do you want to allow registration?', 'yes')
                ->expectsConfirmation('Do you want to modify the default theme?', 'yes')
                ->expectsQuestion("Select value for 'color-palette':", 'red')
                ->expectsQuestion("Select value for 'gray-color-palette':", 'slate')
                ->expectsQuestion("Select value for 'darkmode-type':", 'auto')
                ->expectsQuestion("Select value for 'sidebar-size':", 'standard')
                ->expectsQuestion("Select value for 'sidebar-type':", 'dark')
                ->expectsQuestion("Select value for 'interface-density':", 'comfortable')
                ->expectsQuestion("Select value for 'corner-radius':", 'rounded')
                ->assertSuccessful();

            $after = File::get($path.'/aura.php');

            $beforeLines = explode("\n", $before);
            $afterLines = explode("\n", $after);

            expect($afterLines)->toHaveCount(count($beforeLines));

            $changed = [];

            foreach ($beforeLines as $index => $line) {
                if ($line !== $afterLines[$index]) {
                    $changed[$index] = [$line, $afterLines[$index]];
                }
            }

            expect($changed)->toHaveCount(1)
                ->and(trim(reset($changed)[0]))->toBe("'color-palette' => 'aura',")
                ->and(trim(reset($changed)[1]))->toBe("'color-palette' => 'red',")
                ->and($after)->toContain("'primary' => 'var(--primary-600)'")
                ->and($after)->toContain("env('AURA_TEAMS', true)")
                ->and(config('aura.theme.color-palette'))->toBe('red');
        });
    });

    it('warns and leaves the file untouched when a key is missing', function () {
        withTemporaryAuraConfig(<<<'PHP'
<?php

return [
    // The user removed the teams setting entirely.
    'auth' => [
        'registration' => env('AURA_REGISTRATION', true),
    ],
];
PHP, function (string $path) {
            $before = File::get($path.'/aura.php');

            $this->artisan('aura:install-config', [
                '--no-interaction' => true,
                '--teams' => 'false',
                '--registration' => 'false',
            ])
                ->expectsOutputToContain('Could not find [teams] in config/aura.php')
                ->assertSuccessful();

            expect(File::get($path.'/aura.php'))->toBe($before)
                ->and(File::get($path.'/.env'))->toContain('AURA_REGISTRATION=false');
        });
    });
});
