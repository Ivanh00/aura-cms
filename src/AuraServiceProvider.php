<?php

namespace Aura\Base;

use Aura\Base\Ai\AiStatus;
use Aura\Base\Commands\AuraLayoutCommand;
use Aura\Base\Commands\CreateAuraPlugin;
use Aura\Base\Commands\CreateResourceFactory;
use Aura\Base\Commands\CreateResourceMigration;
use Aura\Base\Commands\CreateResourcePermissions;
use Aura\Base\Commands\CustomizeCommand;
use Aura\Base\Commands\DatabaseToResources;
use Aura\Base\Commands\ExtendUserModel;
use Aura\Base\Commands\InstallConfigCommand;
use Aura\Base\Commands\MakeField;
use Aura\Base\Commands\MakeResource;
use Aura\Base\Commands\MakeUser;
use Aura\Base\Commands\MigrateFromPostsToCustomTable;
use Aura\Base\Commands\MigratePostMetaToMeta;
use Aura\Base\Commands\PublishCommand;
use Aura\Base\Commands\TransferFromPostsToCustomTable;
use Aura\Base\Commands\TransformTableToResource;
use Aura\Base\Commands\UpdateSchemaFromMigration;
use Aura\Base\Contracts\ResourceActionRegistry as ResourceActionRegistryContract;
use Aura\Base\Database\Seeders\RoleCatalogSeeder;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\AiProviderStatus;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;
use Aura\Base\Livewire\AttachmentDetails;
use Aura\Base\Livewire\BookmarkPage;
use Aura\Base\Livewire\ChooseTemplate;
use Aura\Base\Livewire\ComponentSlots\ArrayLivewireCollisionInspector;
use Aura\Base\Livewire\ComponentSlots\LivewireCollisionInspector;
use Aura\Base\Livewire\CreateResource;
use Aura\Base\Livewire\EditResourceField;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\InviteUser;
use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Modals;
use Aura\Base\Livewire\Navigation;
use Aura\Base\Livewire\Notifications;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\CreateModal;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\EditModal;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Livewire\Resource\ViewModal;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Livewire\Styleguide;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Livewire\TwoFactorAuthenticationForm;
use Aura\Base\Livewire\UserTeams;
use Aura\Base\Navigation\Navigation as AuraNavigation;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Policies\UserPolicy;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Preferences\PreferenceRegistry;
use Aura\Base\RecordLayout\RecordLayoutRegistry;
use Aura\Base\RecordLayout\RecordLayoutResolver;
use Aura\Base\Reporting\AggregateEngine;
use Aura\Base\Reporting\ResourceAggregateEngine;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Services\ResourceActionRegistry;
use Aura\Base\Settings\CoreSettingsPages;
use Aura\Base\Settings\SettingsRegistry;
use Aura\Base\Settings\SettingsStore;
use Aura\Base\Widgets\Bar;
use Aura\Base\Widgets\Donut;
use Aura\Base\Widgets\Pie;
use Aura\Base\Widgets\SparklineArea;
use Aura\Base\Widgets\SparklineBar;
use Aura\Base\Widgets\ValueWidget;
use Aura\Base\Widgets\Widgets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Laravel\Octane\Events\RequestReceived;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Symfony\Component\Console\Input\InputOption;

class AuraServiceProvider extends PackageServiceProvider
{
    protected $commands = [
        AuraLayoutCommand::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->app->booted(function (): void {
            Aura::captureBaselineState();
            app(ResourceActionRegistryContract::class)->captureBaselineState();
        });
    }

    public function bootGate()
    {
        if (config('aura.teams')) {
            Gate::policy(Team::class, TeamPolicy::class);
        }

        Gate::policy(Resource::class, ResourcePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Global Admin: an instance-level operator that transcends the tenant
        // boundary. The package resolves it from the users.global_admin flag so
        // the policy bypasses keyed on isAuraGlobalAdmin() become live out of the
        // box. Host apps may redefine this gate in their own service provider —
        // app providers boot after package providers, so a later Gate::define
        // wins. A required $user param means guests are denied automatically.
        Gate::define(User::GLOBAL_ADMIN_GATE, function ($user) {
            return (bool) ($user->global_admin ?? false);
        });

        return $this;
    }

    public function bootLivewireComponents()
    {
        // Component name to class mapping for Livewire 4.x compatibility
        $componentMap = [
            // Resource components (aura:: prefixed names)
            'aura::resource-index' => Index::class,
            'aura::resource-create' => Create::class,
            'aura::resource-create-modal' => CreateModal::class,
            'aura::resource-edit' => Edit::class,
            'aura::resource-edit-modal' => EditModal::class,
            'aura::resource-view-modal' => ViewModal::class,
            'aura::resource-view' => View::class,

            // Resource components (dot-notation names for full-page components)
            'aura.base.livewire.resource' => Index::class,
            'aura.base.livewire.resource.index' => Index::class,
            'aura.base.livewire.resource.create' => Create::class,
            'aura.base.livewire.resource.create-modal' => CreateModal::class,
            'aura.base.livewire.resource.edit' => Edit::class,
            'aura.base.livewire.resource.edit-modal' => EditModal::class,
            'aura.base.livewire.resource.view-modal' => ViewModal::class,
            'aura.base.livewire.resource.view' => View::class,

            // Table component
            'aura::table' => Table::class,
            'aura.base.livewire.table.table' => Table::class,

            // Attachment component
            'aura::attachment-index' => AttachmentIndex::class,
            'aura.base.livewire.attachment' => AttachmentIndex::class,
            'aura.base.livewire.attachment.index' => AttachmentIndex::class,

            // Top-level Livewire components
            'aura::navigation' => Navigation::class,
            'aura::global-search' => GlobalSearch::class,
            'aura::bookmark-page' => BookmarkPage::class,
            'aura::notifications' => Notifications::class,
            'aura::edit-resource-field' => EditResourceField::class,
            'edit-field' => EditResourceField::class,
            'aura::media-manager' => config('aura.components.media-manager', MediaManager::class),
            'aura::media-uploader' => MediaUploader::class,
            'aura::attachment-details' => AttachmentDetails::class,
            'aura::create-resource' => CreateResource::class,
            'aura::resource-editor' => ResourceEditor::class,
            'aura::invite-user' => InviteUser::class,
            'aura::user-teams' => UserTeams::class,
            'aura::modals' => Modals::class,
            'aura::plugins-page' => PluginsPage::class,
            'aura::ai-provider-status' => AiProviderStatus::class,
            'aura::styleguide' => Styleguide::class,
            'aura::choose-template' => ChooseTemplate::class,
            'aura::two-factor-authentication-form' => TwoFactorAuthenticationForm::class,
            'aura::user-two-factor-authentication-form' => TwoFactorAuthenticationForm::class,

            // Top-level components (dot-notation for full-page)
            'aura.base.livewire.dashboard' => config('aura.components.dashboard'),
            'aura.base.livewire.navigation' => Navigation::class,
            'aura.base.livewire.global-search' => GlobalSearch::class,
            'aura.base.livewire.bookmark-page' => BookmarkPage::class,
            'aura.base.livewire.notifications' => Notifications::class,
            'aura.base.livewire.edit-resource-field' => EditResourceField::class,
            'aura.base.livewire.media-manager' => config('aura.components.media-manager', MediaManager::class),
            'aura.base.livewire.media-uploader' => MediaUploader::class,
            'aura.base.livewire.create-resource' => CreateResource::class,
            'aura.base.livewire.resource-editor' => ResourceEditor::class,
            'aura.base.livewire.invite-user' => InviteUser::class,
            'aura.base.livewire.user-teams' => UserTeams::class,
            'aura.base.livewire.modals' => Modals::class,
            'aura.base.livewire.plugins-page' => PluginsPage::class,
            'aura.base.livewire.ai-provider-status' => AiProviderStatus::class,
            'aura.base.livewire.styleguide' => Styleguide::class,
            'aura.base.livewire.choose-template' => ChooseTemplate::class,
            'aura.base.livewire.two-factor-authentication-form' => TwoFactorAuthenticationForm::class,
            'aura.base.livewire.settings' => config('aura.components.settings'),
            'aura.base.livewire.profile' => config('aura.components.profile'),

            // Configurable components (from config)
            'aura::settings' => config('aura.components.settings'),
            'aura::profile' => config('aura.components.profile'),
            'aura::dashboard' => config('aura.components.dashboard'),

            // Widgets
            'aura::widgets' => Widgets::class,
            'aura::widgets.value-widget' => ValueWidget::class,
            'aura::widgets.sparkline-area' => SparklineArea::class,
            'aura::widgets.sparkline-bar' => SparklineBar::class,
            'aura::widgets.donut' => Donut::class,
            'aura::widgets.pie' => Pie::class,
            'aura::widgets.bar' => Bar::class,

            // Widgets (dot-notation)
            'aura.base.widgets.widgets' => Widgets::class,
            'aura.base.widgets.value-widget' => ValueWidget::class,
            'aura.base.widgets.sparkline-area' => SparklineArea::class,
            'aura.base.widgets.sparkline-bar' => SparklineBar::class,
            'aura.base.widgets.donut' => Donut::class,
            'aura.base.widgets.pie' => Pie::class,
            'aura.base.widgets.bar' => Bar::class,
        ];

        // Register component resolver for Livewire 4.x compatibility
        Livewire::resolveMissingComponent(function ($name) use ($componentMap) {
            return $componentMap[$name] ?? null;
        });

        return $this;
    }

    /*
    * This class is a Package Service Provider
    *
    * More info: https://github.com/spatie/laravel-package-tools
    */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('aura')
            ->hasConfigFile(['aura', 'aura-settings'])
            ->hasViews('aura')
            ->hasAssets()
            ->hasRoutes('web')
            // Migrations are published, then run (ADR 0002: ownership-aware
            // rollback). runsMigrations() cannot load .php.stub files anyway.
            ->hasMigrations(['create_aura_tables', 'consolidate_per_team_admin_roles', 'add_global_admin_to_users'])
            ->hasCommands([
                InstallConfigCommand::class,
                MakeResource::class,
                MakeUser::class,
                CustomizeCommand::class,
                CreateAuraPlugin::class,
                MakeField::class,
                PublishCommand::class,
                CreateResourceMigration::class,
                DatabaseToResources::class,
                TransformTableToResource::class,
                CreateResourcePermissions::class,
                ExtendUserModel::class,
                UpdateSchemaFromMigration::class,
                CreateResourceFactory::class,
                AuraLayoutCommand::class,
                MigratePostMetaToMeta::class,
                MigrateFromPostsToCustomTable::class,
                TransferFromPostsToCustomTable::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                // Spatie's InstallCommand hides itself and describes itself as
                // "Install aura". This is Aura's documented entry point, so it has to
                // show up in `php artisan list` with a description that says what it does.
                $command->setHidden(false);
                $command->setDescription('Install Aura CMS: publish config, assets and migrations, run migrations, create the first admin');

                $command
                    ->addOption('teams', null, InputOption::VALUE_REQUIRED, 'Enable teams: true or false')
                    ->addOption('registration', null, InputOption::VALUE_REQUIRED, 'Allow public registration: true or false')
                    ->addOption('admin-name', null, InputOption::VALUE_REQUIRED, 'Name for the first administrator')
                    ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'Email for the first administrator')
                    ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'Password for the first administrator (at least 8 characters)')
                    ->addOption('team-name', null, InputOption::VALUE_REQUIRED, 'Name for the first team (defaults to the administrator name)')
                    ->addOption('no-admin', null, InputOption::VALUE_NONE, 'Skip creating the first administrator')
                    ->addOption('no-global-admin', null, InputOption::VALUE_NONE, 'Do not grant Global Admin status to the first administrator')
                    ->startWith(function (InstallCommand $command) {
                        // Everything is validated here, before the first side effect:
                        // startWith runs ahead of publishing, migrating and seeding, so a
                        // bad flag can no longer leave a half-installed application behind.
                        $errors = [];

                        foreach (['teams', 'registration'] as $booleanOption) {
                            $value = $command->option($booleanOption);
                            $accepted = ['1', 'true', 'yes', 'on', '0', 'false', 'no', 'off'];

                            if (filled($value) && ! in_array(strtolower((string) $value), $accepted, true)) {
                                $errors[] = "The --{$booleanOption} option must be true or false.";
                            }
                        }

                        if ($command->option('no-interaction') && ! $command->option('no-admin')) {
                            $missingOptions = array_keys(array_filter([
                                '--admin-name' => $command->option('admin-name'),
                                '--admin-email' => $command->option('admin-email'),
                                '--admin-password' => $command->option('admin-password'),
                            ], fn ($value) => blank($value)));

                            if ($missingOptions !== []) {
                                $errors[] = 'A non-interactive install requires '.implode(', ', $missingOptions).', or --no-admin.';
                            }
                        }

                        $adminEmail = $command->option('admin-email');

                        if (filled($adminEmail) && ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = 'The --admin-email option must be a valid email address.';
                        }

                        $adminPassword = $command->option('admin-password');

                        if (filled($adminPassword) && strlen((string) $adminPassword) < 8) {
                            $errors[] = 'The --admin-password option must be at least 8 characters.';
                        }

                        if ($errors === []) {
                            $command->info('Hello, thank you for installing Aura!');

                            return;
                        }

                        foreach ($errors as $error) {
                            $command->error($error);
                        }

                        // fail() surfaces a clean error and a non-zero exit code instead of
                        // an uncaught exception with a stack trace.
                        $command->fail('Aura was not installed. Fix the options above and run the command again.');
                    })
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->askToStarRepoOnGitHub('eminiarts/aura-cms')
                    ->endWith(function (InstallCommand $command) {
                        $run = function (string $step, array $options = []) use ($command): void {
                            if ($command->call($step, $options) !== InstallCommand::SUCCESS) {
                                $command->fail("Aura installation stopped: {$step} failed.");
                            }
                        };

                        if (! file_exists(config_path('ai.php'))) {
                            $run('vendor:publish', ['--tag' => 'ai-config']);
                        }

                        $run('aura:extend-user-model');

                        // The media library stores on the `public` disk and Attachment::url()
                        // builds /storage/... URLs, so the symlink has to exist. Guarded so
                        // re-running the installer stays idempotent.
                        $finish = function (InstallCommand $command, bool $createdAdmin) use ($run) {
                            $link = public_path('storage');

                            if (! file_exists($link) && ! is_link($link)) {
                                $run('storage:link');
                            }

                            $command->newLine();
                            $command->info('Next steps:');
                            $command->line('  1. Open '.route('aura.dashboard'));
                            $command->line($createdAdmin
                                ? '  2. Log in with the administrator you just created.'
                                : '  2. Create an administrator with `php artisan aura:user`.');
                            $command->line('  3. Configure AI providers in config/ai.php and your environment when needed.');
                        };

                        if ($command->option('no-interaction')) {
                            $configOptions = array_filter([
                                '--teams' => $command->option('teams'),
                                '--registration' => $command->option('registration'),
                            ], fn ($value) => $value !== null);

                            $run('aura:install-config', $configOptions);
                            $run('migrate', ['--force' => true]);
                            RoleCatalogSeeder::seed();

                            $createdAdmin = ! $command->option('no-admin');

                            if ($createdAdmin) {
                                $adminOptions = [
                                    '--name' => $command->option('admin-name'),
                                    '--email' => $command->option('admin-email'),
                                    '--password' => $command->option('admin-password'),
                                ];

                                if (filled($command->option('team-name'))) {
                                    $adminOptions['--team-name'] = $command->option('team-name');
                                }

                                if ($command->option('no-global-admin')) {
                                    $adminOptions['--no-global-admin'] = true;
                                } else {
                                    $adminOptions['--global-admin'] = true;
                                }

                                $run('aura:user', $adminOptions);
                            }

                            $finish($command, $createdAdmin);

                            return;
                        }

                        if ($command->confirm('Do you want to modify the aura configuration?', true)) {
                            $run('aura:install-config');
                        }

                        if ($command->confirm('Do you want to run the migrations?', true)) {
                            $run('migrate');

                            // Seed the base Role Catalog (admin + user Global Roles)
                            // so a fresh install works in both Teams-on and
                            // Teams-off mode without hand-seeding. Idempotent.
                            RoleCatalogSeeder::seed();
                        }

                        $createdAdmin = $command->confirm('Do you want to create a user?', true);

                        if ($createdAdmin) {
                            $run('aura:user');
                        }

                        $finish($command, $createdAdmin);
                    });
            });

    }

    public function packageBooted()
    {
        Queue::after(fn () => Aura::flushState());
        Queue::exceptionOccurred(fn () => Aura::flushState());

        // Laravel Octane keeps a single PHP process alive across many requests,
        // so Aura's process-level static state (field caches, resource registry,
        // conditional-logic cache, team/scope statics and the user model) must be
        // reset at every request/task/tick boundary to prevent leakage between
        // requests, users and teams. Octane is an optional dependency, so the
        // event classes are referenced by name (guarded by class_exists) and
        // nothing breaks when octane is not installed.
        if (class_exists(RequestReceived::class)) {
            $events = $this->app['events'];

            foreach ([
                'Laravel\Octane\Events\RequestReceived',
                'Laravel\Octane\Events\TaskReceived',
                'Laravel\Octane\Events\TickReceived',
            ] as $octaneEvent) {
                $events->listen($octaneEvent, fn () => Aura::flushState());
            }
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../resources/dist') => public_path("vendor/{$this->package->shortName()}"),
                $this->package->basePath('/../resources/libs') => public_path("vendor/{$this->package->shortName()}/libs"),
                $this->package->basePath('/../resources/public') => public_path("vendor/{$this->package->shortName()}/public"),
            ], "{$this->package->shortName()}-assets");
        }

        Component::macro('notify', function ($message, $type = 'success') {
            $this->dispatch('notify', message: $message, type: $type);
        });

        // Search in multiple columns
        Builder::macro('searchIn', function ($columns, $search, $model) {
            return $this->where(function ($query) use ($columns, $search, $model) {
                foreach (Arr::wrap($columns) as $column) {
                    if ($model->isMetaField($column)) {
                        $metaTable = $model->getMetaTable();
                        $metaForeignKey = $model->getMetaForeignKey();

                        $query->orWhereExists(function ($subquery) use ($metaTable, $metaForeignKey, $column, $search, $model) {
                            $subquery->select(DB::raw(1))
                                ->from($metaTable)
                                ->whereColumn($model->getQualifiedKeyName(), $metaTable.'.'.$metaForeignKey)
                                ->where($metaTable.'.metable_type', $model->getMorphClass())
                                ->where($metaTable.'.key', $column)
                                ->where($metaTable.'.value', 'like', '%'.$search.'%');
                        });
                    } else {
                        $query->orWhere($model->getTable().'.'.$column, 'like', '%'.$search.'%');
                    }
                }
            });
        });

        // CheckCondition Blade Directive
        Blade::if('checkCondition', function ($model, $field, $post = null) {
            return \Aura\Base\Aura::checkCondition($model, $field, $post);
        });

        Blade::if('superadmin', function () {
            return auth()->user()->isSuperAdmin();
        });

        Blade::if('local', function () {
            return app()->environment('local');
        });

        Blade::if('production', function () {
            return app()->environment('production');
        });

        Blade::directive('auraStyles', function (string $expression) {
            return "<?php echo app('aura')::styles(); ?>";
        });

        Blade::directive('auraScripts', function (string $expression) {
            return "<?php echo app('aura')::scripts(); ?>";
        });

        $this
            ->bootGate()
            ->bootLivewireComponents();
    }

    public function packageRegistered()
    {
        parent::packageRegistered();

        $this->app->singleton(ResourceActionRegistry::class);
        $this->app->alias(ResourceActionRegistry::class, ResourceActionRegistryContract::class);

        $this->app->singleton('hook_manager', function ($app) {
            return new HookManager;
        });

        $this->app->singleton('dynamicFunctions', function ($app) {
            return new Facades\DynamicFunctions;
        });

        $this->app->singleton('dynamic_functions', function ($app) {
            return new DynamicFunctions;
        });

        $this->app->singleton('navigation', function ($app) {
            return new AuraNavigation;
        });

        $this->app->singleton(AggregateEngine::class, ResourceAggregateEngine::class);

        // Bind the concrete Aura instance as a process-persistent singleton so
        // its resource/field registrations and captured baseline survive across
        // requests on a long-running worker (Octane). Octane clears facade and
        // scoped container instances on every request; without a real singleton
        // the facade would re-resolve a fresh, empty Aura and lose every
        // registration after the first request. Per-request mutable state is
        // reset back to the boot baseline via Aura::flushState() instead.
        $this->app->singleton(\Aura\Base\Aura::class);

        $this->app->singleton(PreferenceRegistry::class);
        $this->app->singleton(PreferenceManager::class);
        $this->app->singleton(LivewireCollisionInspector::class, ArrayLivewireCollisionInspector::class);
        $this->app->singleton(RecordLayoutRegistry::class);
        $this->app->singleton(RecordLayoutResolver::class);
        $this->app->singleton(MediaAuthorization::class);
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(SettingsStore::class);
        $this->app->singleton(AiStatus::class);

        app(SettingsRegistry::class)->register('eminiarts/aura-cms', CoreSettingsPages::all());

        $this->app->scoped('aura', function (): Aura {
            return app(Aura::class);
        });

        app('aura')::registerResources([
            config('aura.resources.attachment'),
            config('aura.resources.option'),
            config('aura.resources.permission'),
            config('aura.resources.role'),
            config('aura.resources.user'),
        ]);

        if (config('aura.teams')) {
            app('aura')::registerResources([
                config('aura.resources.team'),
                config('aura.resources.team-invitation'),
            ]);
        }

        // Register Fields from src/Fields
        $fields = collect(app('files')->files(__DIR__.'/Fields'))
            ->map(function ($field) {
                $className = 'Aura\Base\Fields\\'.str($field->getFilename())->replace('.php', '');

                return $className !== 'Aura\Base\Fields\Field' ? $className : null;
            })
            ->filter()
            ->toArray();

        app('aura')::registerFields($fields);

        // Register App Resources
        app('aura')::registerResources(app('aura')::getAppResources());
        app('aura')::registerWidgets(app('aura')::getAppWidgets());
        app('aura')::registerFields(app('aura')::getAppFields());
    }

    public function registeringPackage() {}

    protected function getResources(): array
    {
        return config('aura.resources');
    }
}
