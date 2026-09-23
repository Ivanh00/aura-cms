<?php

namespace Aura\Base\Providers;

use Aura\Base\Events\SaveFields;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Navigation\Navigation;
use Aura\Base\Settings\SettingsPageAuthorizer;
use Aura\Base\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $isSuperAdmin = DynamicFunctions::add(fn () => auth()->user()->isSuperAdmin());

        Navigation::add(array_filter([
            config('aura.features.dashboard') ? [
                'icon' => "<x-aura::icon icon='dashboard' />",
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'sort' => 0,
                'group' => 'Aura',
                'route' => 'aura.dashboard',
            ] : null,
            config('aura.features.create_resource') ? [
                'icon' => "<x-aura::icon icon='collection' />",
                'name' => 'Create Resource',
                'slug' => 'create_resource',
                'group' => 'settings',
                'sort' => 300,
                'onclick' => "Livewire.dispatch('openModal', { component : 'aura::create-resource' })",
                'route' => false,
                'conditional_logic' => DynamicFunctions::add(function () {
                    return auth()->user()->isSuperAdmin();
                }),
            ] : null,
            config('aura.features.settings') ? [
                'icon' => "<x-aura::icon icon='config' />",
                'name' => 'Settings',
                'slug' => 'settings',
                'group' => 'settings',
                'sort' => 300,
                'route' => 'aura.settings',
                'conditional_logic' => $isSuperAdmin,
            ] : null,
        ]));

        // Plugins register their pages after this provider boots, so resolve them when the sidebar renders.
        if (config('aura.features.settings')) {
            app('hook_manager')->addHook('navigation', function ($navigation) {
                foreach (app(SettingsRegistry::class)->pages() as $page) {
                    if ($page->slug === 'general') {
                        continue;
                    }

                    $navigation->push([
                        'icon' => View::exists('aura::components.icon.'.$page->icon) ? "<x-aura::icon icon='{$page->icon}' />" : '',
                        'name' => $page->title,
                        'slug' => 'settings-'.$page->slug,
                        'group' => 'settings',
                        'sort' => 301 + $page->order,
                        'route' => route('aura.settings.page', $page->slug),
                        'conditional_logic' => DynamicFunctions::add(
                            fn () => app(SettingsPageAuthorizer::class)->canView($page, auth()->user())
                        ),
                    ]);
                }

                return $navigation;
            });
        }

        // Validator::extend('json', function ($attribute, $value, $parameters, $validator) {
        //     json_decode($value);
        //     return json_last_error() === JSON_ERROR_NONE;
        // });

        $customTableMigrations = config('aura.features.custom_tables_for_resources');

        if ($customTableMigrations === 'multiple') {
            // Create New Migrations every time a new field is saved
            Event::listen(SaveFields::class, CreateDatabaseMigration::class);
        } elseif ($customTableMigrations === true || $customTableMigrations === 'single') {
            // Modify Existing Migration every time a new field is saved, syncs the database
            Event::listen(SaveFields::class, ModifyDatabaseMigration::class);
        }

    }

    /**
     * Register any application services.
     */
    public function register(): void {}
}
