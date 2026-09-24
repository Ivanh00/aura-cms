<?php

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

describe('installation commands', function () {
    it('stops without reporting success when a setup command fails', function (string $failedCommand) {
        $calls = [];
        $this->app->usePublicPath(sys_get_temp_dir().'/aura-install-'.bin2hex(random_bytes(8)));

        foreach (['vendor:publish', 'aura:extend-user-model', 'aura:install-config', 'migrate', 'aura:user', 'storage:link'] as $name) {
            $command = new class($name, $failedCommand, $calls) extends Command
            {
                public function __construct(string $name, private string $failedCommand, private array &$calls)
                {
                    parent::__construct();
                    $this->setName($name);
                    $this->ignoreValidationErrors();
                }

                public function handle(): int
                {
                    $this->calls[] = $this->getName();

                    return $this->getName() === $this->failedCommand ? self::FAILURE : self::SUCCESS;
                }
            };

            $this->app->make(Kernel::class)->registerCommand($command);
        }

        $this->artisan('aura:install', [
            '--no-interaction' => true,
            '--admin-name' => 'Admin',
            '--admin-email' => 'admin@example.com',
            '--admin-password' => 'installation-password',
        ])
            ->expectsConfirmation('Would you like to star our repo on GitHub?', false)
            ->expectsOutputToContain("Aura installation stopped: {$failedCommand} failed.")
            ->doesntExpectOutputToContain('Next steps:')
            ->doesntExpectOutputToContain('aura has been installed!')
            ->assertFailed();

        expect(end($calls))->toBe($failedCommand);
    })->with(['aura:extend-user-model', 'aura:install-config', 'migrate', 'aura:user', 'storage:link']);

    it('is visible in the artisan command list with a description of what it does', function () {
        $command = Artisan::all()['aura:install'];

        expect($command->isHidden())->toBeFalse()
            ->and($command->getDescription())->toContain('Install Aura CMS');
    });

    it('documents environment-backed AI configuration after installation', function () {
        $this->app->usePublicPath(sys_get_temp_dir().'/aura-install-'.bin2hex(random_bytes(8)));

        foreach (['vendor:publish', 'aura:extend-user-model', 'aura:install-config', 'migrate', 'storage:link'] as $name) {
            $command = new class($name) extends Command
            {
                public function __construct(string $name)
                {
                    parent::__construct();
                    $this->setName($name);
                    $this->ignoreValidationErrors();
                }

                public function handle(): int
                {
                    return self::SUCCESS;
                }
            };

            $this->app->make(Kernel::class)->registerCommand($command);
        }

        $this->artisan('aura:install', [
            '--no-interaction' => true,
            '--no-admin' => true,
        ])
            ->expectsConfirmation('Would you like to star our repo on GitHub?', false)
            ->expectsOutputToContain('Configure AI providers in config/ai.php and your environment')
            ->assertSuccessful();
    });

    it('fails before any side effect when a non-interactive install is missing admin options', function () {
        $this->artisan('aura:install', [
            '--no-interaction' => true,
            '--teams' => 'true',
        ])
            ->expectsOutputToContain('A non-interactive install requires --admin-name, --admin-email, --admin-password, or --no-admin.')
            ->assertExitCode(1);
    });

    it('rejects a non-boolean --teams value before any side effect', function () {
        $this->artisan('aura:install', [
            '--no-interaction' => true,
            '--no-admin' => true,
            '--teams' => 'maybe',
        ])
            ->expectsOutputToContain('The --teams option must be true or false.')
            ->assertExitCode(1);
    });

    it('rejects an invalid --admin-email before any side effect', function () {
        $this->artisan('aura:install', [
            '--no-interaction' => true,
            '--admin-name' => 'Admin',
            '--admin-email' => 'not-an-email',
            '--admin-password' => 'password',
        ])
            ->expectsOutputToContain('The --admin-email option must be a valid email address.')
            ->assertExitCode(1);
    });

    it('rejects a short --admin-password before any side effect', function () {
        $this->artisan('aura:install', [
            '--no-interaction' => true,
            '--admin-name' => 'Admin',
            '--admin-email' => 'admin@example.com',
            '--admin-password' => 'x',
        ])
            ->expectsOutputToContain('The --admin-password option must be at least 8 characters.')
            ->assertExitCode(1);
    });

    it('exposes the unified installer options required by automation', function () {
        $command = Artisan::all()['aura:install'];
        $definition = $command->getDefinition();

        expect($definition->hasOption('teams'))->toBeTrue()
            ->and($definition->hasOption('registration'))->toBeTrue()
            ->and($definition->hasOption('admin-name'))->toBeTrue()
            ->and($definition->hasOption('admin-email'))->toBeTrue()
            ->and($definition->hasOption('admin-password'))->toBeTrue()
            ->and($definition->hasOption('no-admin'))->toBeTrue()
            ->and($definition->hasOption('no-global-admin'))->toBeTrue()
            ->and($definition->hasOption('team-name'))->toBeTrue();
    });

    it('aura:install-config command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:install-config', $commands))->toBeTrue();
    });

    it('aura:publish command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:publish', $commands))->toBeTrue();
    });

    it('aura:user command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:user', $commands))->toBeTrue();
    });
});

// Note: Full installation workflow tests require:
// 1. Isolated test environment
// 2. Fresh config file creation
// 3. Database seeding
// These are typically tested manually or in a dedicated CI job
