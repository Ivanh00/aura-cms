# Plugins

An Aura plugin is a Laravel package that adds resources, fields, routes, views, migrations, Livewire components, or other application code. Use a plugin when the feature should be developed and installed as a package. Put a resource or field that belongs to one application under `app/Aura` instead. See [Creating Resources](/docs/creating-resources) and [Creating Fields](/docs/creating-fields).

Aura Base provides the registration points and the admin integration. It does not scan arbitrary directories for plugins, generate a REST API, or turn resource widgets into global dashboard widgets. A plugin owns its routes, policies, controllers, Livewire components, and other application code.

## Generate a plugin

Run the generator from the Laravel application that contains Aura:

~~~bash
php artisan aura:plugin acme/blog
~~~

If you omit the argument, the command asks for it. Use the `vendor/name` form. The command then asks which stub to copy:

| Option | Value | Files added |
| --- | --- | --- |
| Complete plugin | `plugin` | Service provider, config, migration stub, command, facade, and package class |
| Resource plugin | `plugin-resource` | Service provider, resource class, and two field view stubs |
| Field plugin | `plugin-field` | Service provider, field class, and two field view stubs |

The generator offers these three templates. There is no separate template for widgets.

The generator performs these steps:

1. It creates `plugins/{vendor}/{name}` and copies the selected directory from Aura's `stubs` directory.
2. It runs the generated `configure.php` script to replace placeholders and rename classes and files for your package. The script deletes itself when finished.
3. The configure script asks for an author username. It uses the Git remote as its initial guess.
4. The command offers to add the generated service provider to `config/app.php`.
5. It adds a PSR-4 entry for the plugin source directory to the application's root `composer.json`.
6. It runs `composer dump-autoload`.

For the simple name `acme/blog`, the generated source namespace is `Acme\Blog\`, the source path is `plugins/acme/blog/src`, and the provider class is `Acme\Blog\BlogServiceProvider`.

Use a lowercase vendor and package name separated by one slash, as in the example above. The generator does not validate malformed names before splitting them or writing the Composer namespace entry.

The generated plugin becomes active once the application can autoload its classes and has registered its provider. For local development, the generator adds the autoload mapping to the application's Composer file and offers to register the provider in `config/app.php`. A distributed package can use Laravel package discovery instead, unless the host application disables it. See [Package a plugin](#package-a-plugin) for the Composer configuration.

## Generated files

The resource and field templates produce the following files for the example package. The generator has already replaced placeholder names with your package name.

~~~text
plugins/acme/blog/
├── composer.json
├── resources/views/components/fields/blog.blade.php
├── resources/views/components/fields/blog-view.blade.php
├── src/Blog.php
├── src/BlogServiceProvider.php
├── README.md
├── CHANGELOG.md
└── LICENSE.md
~~~

The complete `plugin` stub also contains:

~~~text
config/blog.php
database/factories/ModelFactory.php
database/migrations/create_blog_table.php.stub
src/Commands/BlogCommand.php
src/Facades/Blog.php
~~~

The resource template registers its resource automatically. With the field template, you need to add the registration yourself, as shown in [Field plugins](#field-plugins).

Both templates retain an unused `Skeleton` facade alias under `extra.laravel` in their Composer files. Neither includes a facade class. Do not use the alias unless you add a facade class and update its name.

The generated Composer constraints are older than Aura's current requirements. They allow PHP 8.1, package tools 1.14, and Laravel 10 contracts, while the current Aura source targets PHP 8.4, Laravel 13, Livewire 4, and package tools 1.16. Update the generated constraints for the host application before distributing your plugin.

The template constraints are `php: ^8.1`, `spatie/laravel-package-tools: ^1.14.0`, and `illuminate/contracts: ^10.0`.

## Configure the service provider

Plugin providers extend Spatie's `PackageServiceProvider`. Configure only the package features that the plugin actually ships:

~~~php
<?php

namespace Acme\Blog;

use Acme\Blog\Commands\BlogCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BlogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('blog')
            ->hasConfigFile()
            ->hasViews('acme-blog')
            ->hasRoutes('web')
            ->hasMigration('create_blog_table')
            ->hasCommand(BlogCommand::class);
    }
}
~~~

This provider loads the package's configuration, views, and routes, makes its migration available for publication, and registers its Artisan command. The methods configure each feature as follows:

| Method | Behavior |
| --- | --- |
| `hasConfigFile()` | Loads `config/blog.php` under the package name. |
| `hasViews('acme-blog')` | Registers `acme-blog::` as the namespace for views and anonymous components. |
| `hasRoutes('web')` | Loads `routes/web.php` from the package. |
| `hasMigration()` | Publishes the named migration into the host application when the package is installed. It does not run the migration. |
| `hasCommand()` | Registers the command with Artisan. |

The package-tools lifecycle is:

1. `configurePackage()` runs during service-provider registration.
2. `packageRegistered()` runs later in the same registration phase, after package configuration has been merged.
3. `packageBooted()` runs during the framework boot phase after package tools have loaded routes, views, migrations, and commands.

Register Aura resources and fields in `configurePackage()` or in `packageBooted()` while the application is still booting. Register record layout panels before the application's booted callbacks run. Do not defer those registrations with `$this->app->booted()`, because the record layout registry is finalized at that point.

## A small resource plugin

This example registers one resource and gives its index page a count widget. It is a complete plugin feature once the files are placed under the package's `src` directory.

`src/BlogServiceProvider.php`:

~~~php
<?php

namespace Acme\Blog;

use Acme\Blog\Resources\Post;
use Aura\Base\Facades\Aura;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BlogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('blog')
            ->hasViews('acme-blog');

        Aura::registerResources([Post::class]);
    }
}
~~~

`src/Resources/Post.php`:

~~~php
<?php

namespace Acme\Blog\Resources;

use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Base\Resource;
use Aura\Base\Widgets\ValueWidget;

class Post extends Resource
{
    public static string $type = 'BlogPost';

    public static ?string $slug = 'blog-post';

    protected static ?string $group = 'Acme';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'type' => Text::class,
                'slug' => 'title',
                'validation' => 'required|max:255',
                'on_index' => true,
            ],
            [
                'name' => 'Summary',
                'type' => Textarea::class,
                'slug' => 'summary',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [
            [
                'name' => 'Posts',
                'slug' => 'post-count',
                'type' => ValueWidget::class,
                'method' => 'count',
                'style' => ['width' => '100'],
            ],
        ];
    }
}
~~~

The provider registers the resource with Aura. Each field definition names its field class and uses a slug to identify the value in forms, storage, and tables. Plugin resources have the same default storage and custom-table options as application resources. See [Resources](/docs/resources), [Meta Fields](/docs/meta-fields), and [Custom Tables](/docs/custom-tables).

When a resource redeclares an inherited static property, keep the type declared by `Aura\Base\Resource`. For example, `$group` and `$name` are nullable strings. Some inherited properties are intentionally untyped. Adding a conflicting type causes a PHP fatal error.

## Register resources, fields, widgets, and settings

The Aura facade exposes registration methods for common plugin extension points:

| Method | Use |
| --- | --- |
| `Aura::registerResources([...])` | Add resource classes to Aura's resource registry. Registered resources receive navigation and admin routes when their class is valid. |
| `Aura::registerFields([...])` | Add field classes to the Resource Editor's field list and option groups. |
| `Aura::registerWidgets([...])` | Add widget classes to Aura's widget registry. A resource still needs a definition in `getWidgets()` for the index renderer to mount it. |
| `Aura::registerSettingsPages($source, [...])` | Add plugin settings pages at `/admin/settings/{slug}` with a sidebar entry. |

~~~php
use Aura\Base\Facades\Aura;

Aura::registerResources([\Acme\Blog\Resources\Post::class]);
Aura::registerFields([\Acme\Blog\Fields\ColorPicker::class]);
Aura::registerWidgets([\Acme\Blog\Widgets\ReadingTime::class]);
~~~

Registering a widget does not add it to the global dashboard. To display it above a resource's index table, include its definition in that resource's `getWidgets()` method. See [Widgets](/docs/widgets).

### Register a settings page

Plugins can add a settings page without defining a resource, route, or storage model. Each page gets a sidebar entry in the settings group and the URL `/admin/settings/{slug}` (`route('aura.settings.page', $slug)`). Register immutable page definitions from the plugin service provider:

~~~php
use Aura\Base\Facades\Aura;
use Aura\Base\Settings\SettingsPage;

public function packageBooted(): void
{
    Aura::registerSettingsPages('acme/seo', [
        new SettingsPage(
            slug: 'seo',
            title: 'SEO',
            icon: 'search',
            order: 25,
            fields: [[
                'name' => 'Title pattern',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'seo-title-pattern',
                'validation' => 'required|string|max:255',
            ]],
            defaults: [
                'seo-title-pattern' => '[Post Title] [Separator] [Site Name]',
            ],
        ),
    ]);
}
~~~

Use the plugin's lowercase Composer package name as the source. Page slugs use kebab-case, and field slugs must be unique across every registered settings page. Registration fails immediately on a page or field collision.

Read a value through Aura so current-team context is handled consistently:

~~~php
$pattern = Aura::setting(
    'seo-title-pattern',
    '[Post Title] [Separator] [Site Name]',
);
~~~

`Aura::setting()` reads the current team's settings. Public routes, console commands, and queued jobs have no authenticated team, so pass the team id explicitly: `Aura::setting('seo-site-name', teamId: $teamId)`. To find which team a public request belongs to, `app(SettingsStore::class)->all()` returns the settings of every team.

Settings pages are for non-sensitive application preferences. Keep credentials in environment-backed configuration and show only redacted, read-only status in the admin area.

## Resource plugins

The `plugin-resource` stub generates a resource class and registers it in the generated provider. For `acme/blog`, the generated class is `Acme\Blog\Blog`, with slug `blog` and type `Blog`. The stub does not create a `Resources\Post` class.

The generated resource is equivalent to this shape:

~~~php
namespace Acme\Blog;

use Aura\Base\Resource;

class Blog extends Resource
{
    public static ?string $slug = 'blog';

    public static string $type = 'Blog';

    protected static ?string $group = 'Acme';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'title',
                'validation' => 'required',
                'on_index' => true,
                'style' => ['width' => '100'],
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
~~~

The generated provider registers this resource during package configuration. Add views, routes, migrations, and other package code only when the resource needs them.

<a id="field-plugins"></a>

## Field plugins

A custom field extends Aura's base field class and provides separate components for editing and displaying its value. Set `$edit` and `$view` to the package's anonymous component names. Aura resolves them through the field's `edit()` and `view()` methods:

~~~php
namespace Acme\Blog\Fields;

use Aura\Base\Fields\Field;

class ColorPicker extends Field
{
    public $edit = 'acme-blog::fields.colorpicker';

    public $view = 'acme-blog::fields.colorpicker-view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Default color',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'default',
                'instructions' => 'Hex value, for example #FF0000',
            ],
        ]);
    }
}
~~~

The field template currently has a defect. It declares the edit view as `$component`, but Aura expects `$edit`. Rename that property before using the generated field.

The generated provider sets the package name and registers its views, but does not register the field. Add the registration yourself:

~~~php
use Aura\Base\Facades\Aura;
use Acme\Blog\Fields\ColorPicker;

public function configurePackage(Package $package): void
{
    $package
        ->name('blog')
        ->hasViews('acme-blog');

    Aura::registerFields([ColorPicker::class]);
}
~~~

The two generated views live under `resources/views/components/fields`. With the `acme-blog` view namespace, `acme-blog::fields.colorpicker` resolves to the anonymous component at `components/fields/colorpicker.blade.php`.

The edit view receives the field definition in `$field` and must bind to the form field slug:

~~~blade
{{-- resources/views/components/fields/colorpicker.blade.php --}}
<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text
        wire:model="form.fields.{{ $field['slug'] }}"
        :disabled="optional($field)['disabled']"
        :placeholder="$field['placeholder'] ?? $field['name']"
        id="resource-field-{{ $field['slug'] }}"
    />
</x-aura::fields.wrapper>
~~~

The display view can delegate to the resource's display method:

~~~blade
{{-- resources/views/components/fields/colorpicker-view.blade.php --}}
<x-aura::fields.wrapper :field="$field">
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>
~~~

The Resource Editor uses `getFields()` to show the field's configuration settings. Merge the parent's definitions, as in the example, to retain the standard name, slug, validation, type, view, and conditional-logic settings.

Implement `set($post, $field, $value)` only when the field transforms a value before saving. Aura calls it when the concrete field class defines it. The base `Field` class does not define `set()`, so do not call `parent::set()`.

## Resource layout panels

Plugins can add validated Livewire panels to record pages. Register them before the application finishes booting:

~~~php
use Aura\Base\Facades\Aura;
use Aura\Base\RecordLayout\RecordLayoutPanel;
use Aura\Base\RecordLayout\RecordLayoutRegion;
use Acme\Blog\Livewire\ReadingStatsPanel;

public function packageBooted(): void
{
    Aura::registerRecordLayoutPanels('acme/blog', [
        new RecordLayoutPanel(
            key: 'reading-stats',
            region: RecordLayoutRegion::RightSidebar,
            component: ReadingStatsPanel::class,
            order: 20,
            resources: ['blog-post'],
            ability: 'view-reading-stats',
            eagerLoad: ['author'],
        ),
    ]);
}
~~~

Identify the plugin with its lowercase Composer package name and give each panel a key unique to that package. Use a concrete Livewire component that accepts the record through a `model` property or mount parameter. It must also accept the modal state through an `inModal` property or parameter.

Aura checks the declared ability before rendering, loads declared relationships together, and skips hidden or unauthorized panels. Panel actions must still authorize their own state-changing requests.

A resource can provide panels without a plugin registry by implementing `DefinesRecordLayoutPanels` and returning `RecordLayoutPanel` objects. See [Record Layouts](/docs/record-layouts) for the complete contract, preferences, ordering, and resource scoping.

## Navigation and hooks

Use the hook manager to modify values at supported points in Aura. For example, the navigation hook lets you change the collection used to build the sidebar:

~~~php
app('hook_manager')->addHook('navigation', function ($navigation) {
    $navigation->push([
        'name' => 'Reports',
        'slug' => 'reports',
        'route' => 'aura.dashboard',
        'group' => 'Acme',
    ]);

    return $navigation;
});
~~~

Each callback receives one argument and must return the modified value. The navigation callback receives the resource collection. Aura does not call hooks named `resource.fields`, `dashboard.widgets`, `navigation.after`, or `aura.hooks`.

Use `Navigation::add()` for menu items:

~~~php
use Aura\Base\Navigation\Navigation;

public function packageBooted(): void
{
    Navigation::add([
        [
            'name' => 'Reports',
            'slug' => 'reports',
            'icon' => 'chart-bar',
            'route' => 'aura.dashboard',
            'group' => 'Acme',
        ],
    ]);
}
~~~

You can pass a callback as the second argument to conditionally add menu items. It must return a boolean and runs immediately when you add the items. It does not check authorization on each request. Protect the destination route or action with Laravel authorization.

To remove all navigation items, call `Navigation::clear()`. This registers a hook that returns an empty collection.

## Inject view fragments

You can insert a rendered view at one of Aura's named slots. Register a callback for the slot, and Aura appends its output wherever that slot appears:

~~~php
use Aura\Base\Facades\Aura;

Aura::registerInjectView('widgets_before', function () {
    return view('acme-blog::partials.banner');
});
~~~

The callback is called through Laravel's container and its result is cast to a string. The slots currently rendered by Aura are:

| Location | Slots |
| --- | --- |
| Resource index | `index_before`, `widgets_before`, `widgets_after` |
| Table | `table_before`, `table_after`, `table_before_{Type}`, `table_after_{Type}` |
| Table header | `header_before`, `header_after` |
| Breadcrumbs | `breadcrumbs_before`, `breadcrumbs_after` |
| Resource edit | `post_edit_title_before`, `post_edit_title_after`, `post_edit_breadcrumbs_before`, `post_edit_breadcrumbs_after` |
| Profile | `profile_before_header`, `profile_after_header` |

There is no core `head` or `dashboard.footer` slot. A plugin that needs those locations must own the relevant layout or view override.

## Config, routes, and migrations

Package tools loads your routes without adding Aura's middleware or URL prefix. Apply them in the route file when a route belongs in the admin area:

~~~php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::middleware(config('aura-settings.middleware.aura-admin'))
    ->prefix(config('aura.path'))
    ->name('aura.')
    ->group(function () {
        Route::get('/blog', \Acme\Blog\Livewire\Feed::class)
            ->name('blog.feed');
    });
~~~

The admin middleware defaults to `web` and `auth` in `config/aura-settings.php`. The URL prefix comes from `config('aura.path')`, which defaults to `admin`. There is no `aura.middleware.*` configuration key.

For a package migration, call `hasMigration('create_blog_table')` or `hasMigrations([...])` with the files the package ships. The default package-tools behavior publishes those files into the host application's `database/migrations` directory. The host then runs `php artisan migrate`. Do not assume that installing a plugin changes the database without a migration step.

Use `hasConfigFile()` for package-owned settings. Use Aura's `config/aura.php` and `config/aura-settings.php` only for host-level Aura configuration. A plugin's config file is not an Aura feature flag unless the plugin reads that config key itself.

## Plugin discovery and the admin page

Aura does not scan `plugins/` and does not discover a provider from a directory name. The local generator updates the host Composer autoload map and can add a provider to `config/app.php`. A separately installed Composer package can use Laravel package discovery through its own `extra.laravel.providers` metadata.

Super admins can view installed package information on the Plugins page. Its URL is `/admin/plugins` by default, or `/{aura-path}/plugins` if you changed the admin prefix. The route name is `aura.plugins`.

The page reads the application's Composer file and lockfile, along with each installed package's Composer file. It shows the package name, locked version, description, and keywords. It does not install or update packages, or check Packagist for newer versions.

The `aura.features.plugins` setting defaults to `true` and controls the Plugins quick action on the dashboard. The `/plugins` route is registered regardless of that setting, so disabling the flag does not disable the route or its authorization check.

## Test a plugin

Orchestra Testbench can boot both Aura and the plugin provider:

~~~php
namespace Acme\Blog\Tests;

use Acme\Blog\BlogServiceProvider;
use Aura\Base\AuraServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AuraServiceProvider::class,
            BlogServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('aura.teams', false);
    }
}
~~~

The test application still needs Aura's schema and the plugin's own migrations. In a package test suite, load the migration stub or migration files from the checked-out Aura package and run the plugin migrations before creating records. Do not point tests at an unrelated `vendor/aura/base` path. The current package name is `eminiarts/aura-cms`.

Aura's resource pages are Livewire components behind GET routes. There are no POST `*.create` routes. Drive the component or the model in a feature test:

~~~php
use Aura\Base\Livewire\Resource\Create;
use function Pest\Livewire\livewire;

test('creates a blog post', function () {
    $this->actingAs($this->user);

    livewire(Create::class, ['slug' => 'blog-post'])
        ->set('form.fields.title', 'Hello')
        ->call('save')
        ->assertHasNoErrors();
});
~~~

Test a custom field's transformation directly when the field implements `set()`:

~~~php
test('normalises a color', function () {
    $field = new \Acme\Blog\Fields\ColorPicker;

    expect($field->set(null, ['slug' => 'color'], '#FF0000'))
        ->toBe('#FF0000');
});
~~~

For more examples, see Aura's focused tests:

| Feature | Test file |
| --- | --- |
| Plugin generator | `tests/Feature/Aura/CreatePluginTest.php` |
| Plugins page | `tests/Feature/Livewire/PluginsPageTest.php` |
| Navigation | `tests/Feature/NavigationTest.php` |
| View slots | `tests/Feature/Table/SettingsTableTest.php` |
| Record layout panels | `tests/Feature/Resource/RecordLayoutTest.php` |

<a id="package-a-plugin"></a>

## Package a plugin

For a Composer-installed plugin, ship a package `composer.json` with a PSR-4 mapping and the provider under `extra.laravel.providers`:

~~~json
{
    "name": "acme/blog",
    "autoload": {
        "psr-4": {
            "Acme\\Blog\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Acme\\Blog\\BlogServiceProvider"
            ]
        }
    }
}
~~~

Declare the Aura package version and framework constraints that the plugin supports. Include setup instructions for config publication, migrations, routes, permissions, and any required host configuration. The generator's root Composer mapping is for the current application and should not be treated as the distribution package's release metadata.

## Related documentation

- [Creating Resources](/docs/creating-resources)
- [Creating Fields](/docs/creating-fields)
- [Widgets](/docs/widgets)
- [Record Layouts](/docs/record-layouts)
- [Testing](/docs/testing)
- [Configuration](/docs/configuration)
