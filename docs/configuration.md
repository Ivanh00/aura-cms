# Configuration

This page describes configuration on the current main branch. The public Composer release, v1.0.0-beta.4, uses an older installer that handles environment variables differently. See [Installation](/docs/installation) for setup notes for that release.

<a id="configuration-overview"></a>

## Configuration overview

Aura uses two configuration files in your Laravel application:

- `config/aura.php` controls routes, teams, components, resources, theme, features, authentication, reporting and media.
- `config/aura-settings.php` controls discovery paths and the middleware arrays used by Aura's routes.

The package defaults apply without publishing either file. Publish them when you need to change a value:

```bash
php artisan vendor:publish --tag=aura-config
```

The installer publishes both files, then offers to run the interactive configuration command. See [Installation](/docs/installation) for the complete installer flow.

<a id="quick-configuration"></a>

## Quick configuration

After publishing `config/aura.php`, you can use the interactive command:

```bash
php artisan aura:install-config
```

The command lets you choose whether to enable teams, public registration and individual features. It also asks about selected theme preferences. Other settings require editing the file directly. See [the installer section](#configuration-command) for details.

<a id="main-configuration"></a>

## Main configuration

### Path and domain

```php
// config/aura.php
'path' => env('AURA_PATH', 'admin'),
'domain' => env('AURA_DOMAIN'),
```

The admin area uses the configured path as its URL prefix. By default, it includes `/admin`, `/admin/profile`, `/admin/settings` and the routes for each registered resource. Set a domain to restrict these routes to one host, or leave it empty to accept every host.

Authentication routes do not use the admin domain or prefix. Login, registration, password recovery and two-factor authentication use paths at the root of the application, such as `/login`, `/forgot-password` and `/reset-password`. The `/register` route is available only when registration is enabled.

After authentication, Aura redirects users to the admin path by default. The `auth.redirect` setting derives this destination from `AURA_PATH`. If you replace it with a fixed path, update it whenever the admin path changes. Two-factor authentication also respects this setting and the intended URL.

### Teams

```php
'teams' => env('AURA_TEAMS', true),
```

Teams are enabled by default. Aura reads this setting during migration to decide whether to create the team tables and columns. Disabling teams also disables team scoping, the team switcher and team registration routes. Roles then use the global role catalog.

Set the value before the first migration. Changing it on an existing installation requires a deliberate schema and data migration. Do not treat it as a runtime toggle for an already-migrated database. See [Teams](/docs/teams) and [Installation](/docs/installation#without-teams).

### Authentication settings

The keys below live under `auth` in `config/aura.php`.

| Key | Default | What it controls |
| --- | --- | --- |
| `registration` | `env('AURA_REGISTRATION', true)` | Registers the public registration routes and controls the registration link. Disabled registration returns 404. |
| `redirect` | `'/'.trim(env('AURA_PATH', 'admin'), '/')` | The destination used by Aura's login, registration, password confirmation, email verification and invitation-registration controllers. The default is `/admin`. |
| `2fa` | `true` | Registers two-factor setup and management routes. Enrolled accounts still require a login challenge when management is disabled. |
| `user_invitations` | `true` | Allows invitation registration and shows invitation actions in the User and Team Invitation resources. The invitation registration routes also require `teams`. |
| `invitation_expiry` | `7` | Number of days that the signed invitation link remains valid. |
| `create_teams` | `env('AURA_CREATE_TEAMS', true)` | The `TeamPolicy::create()` check. Set it to `false` to prevent team creation, including for Global Admins. |

The redirect setting applies to Aura's authentication routes. It does not change the defaults used elsewhere in your Laravel application.

### Feature flags

Use the following keys in the feature settings. Aura ignores additional keys.

| Key | Default | What it controls |
| --- | --- | --- |
| `dashboard` | `true` | The Dashboard navigation item. The `/admin` route still registers when the item is hidden. |
| `global_search` | `true` | The global-search component, its navigation button and the search UI. |
| `bookmarks` | `true` | The bookmark button. The button also requires `global_search`. |
| `notifications` | `true` | The notification component and its navigation button. |
| `plugins` | `true` | The Plugins quick action on the dashboard. The `/admin/plugins` route still registers when this is `false`. |
| `settings` | `true` | The Settings navigation item and component. The component returns 404 when disabled and requires a Super Admin. |
| `profile` | `true` | The profile navigation item and component. The component rejects access when disabled. |
| `create_resource` | `true` | The Create Resource navigation item and dashboard quick actions. The navigation item is restricted to Super Admins, and the modal rejects other users. |
| `resource_editor` | `config('app.env') == 'local'` | The Resource Editor. The route and component require the `local` or `testing` environment, this flag and a Super Admin. Vendor resources cannot be edited. |
| `custom_tables_for_resources` | `false` | The Resource Editor migration listener. See [Custom tables](#custom-tables). |
| `legacy_fields_append` | `true` | Whether the `fields` accessor is appended during resource array and JSON serialization. Set it to `false` and call `$resource->append('fields')` only where serialized field values are needed. |

The Resource Editor is enabled by default only in the local environment. Enabling its flag in another environment does not bypass the environment restriction shown above.

### Components

To replace a top-level Livewire page or the media manager modal, change its class in the components array:

```php
'components' => [
    'dashboard' => Aura\Base\Livewire\Dashboard::class,
    'profile' => Aura\Base\Livewire\Profile::class,
    'settings' => Aura\Base\Livewire\Settings::class,
    'media-manager' => Aura\Base\Livewire\MediaManager::class,
],
```

The dashboard, profile and settings routes use the first three components. The `aura::media-manager` Livewire modal uses the media manager entry. Each replacement should be a subclass or a compatible component.

### Built-in resources

The resources array defines the classes Aura uses for its built-in resources:

```php
'resources' => [
    'user' => Aura\Base\Resources\User::class,
    'team' => Aura\Base\Resources\Team::class,
    'team-invitation' => Aura\Base\Resources\TeamInvitation::class,
    'role' => Aura\Base\Resources\Role::class,
    'permission' => Aura\Base\Resources\Permission::class,
    'option' => Aura\Base\Resources\Option::class,
    'attachment' => Aura\Base\Resources\Attachment::class,
],
```

Aura registers teams and team invitations only when teams are enabled. To customize a built-in resource, extend its class and update the matching entry. Define its fields in the static `getFields()` method:

```php
namespace App\Aura\Resources;

use Aura\Base\Resources\User as BaseUser;

class User extends BaseUser
{
    public static function getFields(): array
    {
        return array_merge(parent::getFields(), [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Department',
                'slug' => 'department',
            ],
        ]);
    }
}
```

`php artisan aura:customize` can generate the subclass and update the matching config entry. See [Creating resources](/docs/creating-resources).

<a id="view-customization"></a>

### Views

You can configure these three views:

```php
'views' => [
    'layout' => 'aura::layout.app',
    'login-layout' => 'aura::layout.login',
    'logo' => 'aura::application-logo',
],
```

- `layout` is read by the package's config-driven Blade views, including the dashboard, profile and team views. The default Livewire dashboard and profile routes render `aura::components.layout.app` directly.
- `login-layout` wraps login, registration, password reset and forgot-password pages.
- `logo` is rendered inside the login layout.

Dashboard, index, detail, create, edit and navigation views do not have configuration keys. To change them, publish and override the relevant Blade view.

The configured layout uses a Blade component alias, while full-page Livewire components use a view name. The defaults are `aura::layout.app` and `aura::components.layout.app`, respectively. See [Customizing views](/docs/customizing-views) to choose the correct override.

<a id="theme-configuration"></a>

### Theme

The theme defaults live under `theme` in `config/aura.php`:

```php
'theme' => [
    'color-palette' => 'aura',
    'gray-color-palette' => 'slate',
    'darkmode-type' => 'auto',
    'font' => [
        'family' => ['ui-sans-serif', 'system-ui', 'sans-serif'],
        'stylesheet' => false,
    ],
    'colors' => [
        'light' => [
            'primary' => 'var(--primary-600)',
            'background' => '255 255 255',
            'panel' => '250 250 250',
            'border' => '228 228 231',
            'text' => '24 24 27',
            'muted' => '82 82 91',
            'success' => '22 163 74',
            'warning' => '217 119 6',
            'danger' => '220 38 38',
        ],
        'dark' => [
            'primary' => 'var(--primary-600)',
            'background' => '9 9 11',
            'panel' => '24 24 27',
            'border' => '63 63 70',
            'text' => '244 244 245',
            'muted' => '161 161 170',
            'success' => '22 163 74',
            'warning' => '217 119 6',
            'danger' => '220 38 38',
        ],
    ],
    'sidebar-size' => 'standard',
    'sidebar-type' => 'dark',
    'sidebar-darkmode-type' => 'dark',
    'login-bg' => false,
    'login-bg-darkmode' => false,
    'app-favicon' => false,
    'app-favicon-darkmode' => false,
],
```

Aura applies theme values in order: package defaults, your application's theme configuration, then saved settings. Saved settings take precedence and live in the options table. A Super Admin can edit them at `/admin/settings` when the settings feature is enabled.

With teams enabled, each team's settings use a record named `team.{id}.settings`. Without teams, the record is named `settings`.

The Settings page initializes and edits the dark mode preference, primary and gray palettes, sidebar size, and sidebar appearance in both light and dark mode. It also saves logos and the shades for custom palettes. Logos and custom shades are stored as option data rather than theme configuration keys.

The theme settings accept these values:

| Setting | Available values |
| --- | --- |
| `darkmode-type` | `auto`, `light`, `dark` |
| `sidebar-size` | `standard`, `compact` |
| `sidebar-type` and `sidebar-darkmode-type` | `primary`, `light`, `dark` |

The light and dark color maps define colors for primary actions, backgrounds, panels, borders, text, muted content, success, warnings and danger. Use the keys shown in the example above. Each value must be an RGB channel string such as `24 24 27` or a variable reference such as `var(--primary-600)`. Invalid values fall back to the package default. Aura exposes these colors through CSS variables named `--aura-color-*`.

`font.family` accepts an array or a comma-separated string. `font.stylesheet` accepts a host-local public path such as `fonts/brand.css`. External URLs, backslashes, control characters and `..` path segments are rejected. The default system stack makes no font request.

Primary and gray palettes accept the following names. The primary palette also supports the additional colors in the second row.

| Palette | Available names |
| --- | --- |
| Primary and gray | slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc, neutral, stone, sandstone, rose-quartz, olive, smaragd, custom |
| Additional primary colors | aura, red, orange, amber, yellow, lime, forest-green, green, emerald, mountain-meadow, teal, ocean-breeze, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose, sandal, desert-sand, salmon, autumn-rust |

Choose `custom` to use shades saved on the Settings page.

Set `login-bg` and `login-bg-darkmode` to public image paths to add login backgrounds. Both default to `false`. When you provide both images, the layout switches to the dark image when the document has the `dark` class.

Set `app-favicon` and `app-favicon-darkmode` to public paths for your favicons. Both default to `false`, which renders an empty favicon URL. If you provide only the light favicon, Aura uses it in both modes.

<a id="media-configuration"></a>

### Media

The media settings are read from `config/aura.php`:

| Key | Default | What it controls |
| --- | --- | --- |
| `disk` | `public` | The filesystem disk used for uploads, thumbnails and image responses. A non-public disk uses that disk's URL resolver. |
| `path` | `media` | The directory inside the configured disk where uploads are stored. |
| `quality` | `80` | JPEG quality used when encoding thumbnails. |
| `restrict_to_dimensions` | `true` | Whether the image route accepts only the width and height pairs listed in `dimensions`. |
| `max_file_size` | `10000` | Maximum upload size in kilobytes for each file. The uploader reads this key for both server validation and its browser policy. |
| `generate_thumbnails` | `true` | Whether the queued image-thumbnail job pre-renders each configured size after an image is saved. The image route can still generate thumbnails on demand. |
| `dimensions` | `xs` 200, `sm` 600, `md` 1200, `lg` 2000, `thumbnail` 600x600 | Named dimensions used by `Attachment::thumbnail()` and thumbnail generation. |

The default upload limit is 10,000 KB per file, about 9.8 MiB, with at most 20 files per batch. The uploader accepts only these extensions: jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv, zip, mp4, mov, avi, mp3 and wav. SVG files are blocked.

Thumbnail generation requires PHP GD. Aura uses Intervention Image 3 with its GD driver.

<a id="reporting-configuration"></a>

### Reporting

The shipped reporting block is:

```php
'reporting' => [
    'projection' => [
        'reads_enabled' => false,
    ],
],
```

Typed projection reads are not available in this build. Leave `reads_enabled` set to `false`. Setting it to `true` does not enable the projection stack. It only changes the error text when a report requests a meta-backed numeric metric.

<a id="settings-configuration"></a>

## `aura-settings.php`

### Discovery paths

The default paths are:

```php
'paths' => [
    'resources' => [
        'namespace' => 'App\\Aura\\Resources',
        'path' => app_path('Aura/Resources'),
    ],
    'fields' => [
        'namespace' => 'App\\Aura\\Fields',
        'path' => app_path('Aura/Fields'),
    ],
],

'widgets' => [
    'namespace' => 'App\\Aura\\Widgets',
    'path' => app_path('Aura/Widgets'),
],
```

When the application boots, Aura scans these directories and uses each file's relative path to determine its class name within the configured namespace. Discovered resources must extend `Aura\Base\Resource`. Keep non-class files out of the field and widget directories, because discovery expects every file there to contain a class.

The field path is `aura-settings.paths.fields.path`. It is not a key under `config('aura')`. The published `widgets.register` array is not consumed by the current discovery code. Register extra classes from a service provider with `Aura::registerResources()`, `Aura::registerFields()` or `Aura::registerWidgets()`.

### Middleware stacks

The default stacks are:

```php
'middleware' => [
    'aura-admin' => ['web', 'auth'],
    'aura-guest' => ['web'],
    'aura-base' => [
        Aura\Base\Http\Middleware\EncryptCookies::class,
        Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        Illuminate\Session\Middleware\StartSession::class,
        Illuminate\View\Middleware\ShareErrorsFromSession::class,
        Aura\Base\Http\Middleware\VerifyCsrfToken::class,
        Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
],
```

Aura applies the guest middleware stack to authentication routes and the admin stack to impersonation and admin routes. Add your application's middleware to `aura-admin` when it must run on every authenticated Aura route. The base stack is available for your application to use, but Aura does not attach it to any route by default.

<a id="environment-variables"></a>

## Environment variables

Aura reads these environment variables through `config/aura.php`:

| Variable | Config key | Default | Effect |
| --- | --- | --- | --- |
| `AURA_PATH` | `path` and the default `auth.redirect` | `admin` | Changes the admin URL prefix and the default post-authentication path. |
| `AURA_DOMAIN` | `domain` | empty | Restricts the admin route group to one host. |
| `AURA_TEAMS` | `teams` | `true` | Selects the teams-on or teams-off schema and behavior at migration and runtime. |
| `AURA_REGISTRATION` | `auth.registration` | `true` | Enables or disables public registration routes and the registration link. |
| `AURA_CREATE_TEAMS` | `auth.create_teams` | `true` | Allows or prevents team creation in `TeamPolicy`. |
| `AURA_AI_ENABLED` | `ai.enabled` | `true` | Enables or disables AI features backed by the Laravel AI SDK. |

There are no package-supported `AURA_FEATURES_*`, `AURA_THEME_*` or `AURA_MEDIA_*` variables. Change those values in the published config file. Clear Laravel's config cache after changing environment-backed configuration:

```bash
php artisan config:clear
```

<a id="configuration-command"></a>

## `aura:install-config`

Run this command after publishing `config/aura.php`:

```bash
php artisan aura:install-config
```

The command asks whether to enable teams and public registration. You can also choose to configure each boolean feature flag and selected theme preferences: the primary and gray palettes, dark mode, sidebar size and sidebar appearance.

It skips fonts, semantic colors, login backgrounds, favicons and the sidebar's dark mode appearance. Edit those values directly in the configuration file, or use the Settings page for preferences available there.

In non-interactive mode, only the two command options below are available:

```bash
php artisan aura:install-config \
    --no-interaction \
    --teams=false \
    --registration=false
```

On the current main branch, the command preserves comments and environment expressions when updating the published file. With the default configuration, it saves the teams and registration choices to `.env` as `AURA_TEAMS` and `AURA_REGISTRATION`.

It leaves the admin path, domain and team creation environment variables unchanged. It also leaves redirect, invitation, component, resource, view, reporting and media settings unchanged.

The command updates the running process configuration and clears both the configuration cache and application cache. The public beta can replace `env()` expressions with literal values during installation. See [Installation](/docs/installation) for the beta caveat before changing beta configuration.

<a id="custom-tables"></a>

## Custom tables

Per-resource storage and the Resource Editor migration listener are separate settings.

Set these properties on a resource when it should use its own table:

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    public static $customTable = true;

    protected $table = 'products';
}
```

Using a custom table does not disable meta storage. By default, fields outside the resource's base fillable columns can still use the shared meta table. Set `public static bool $usesMeta = false;` when every input field should use a column on the custom table.

`features.custom_tables_for_resources` only controls migration listeners used when the Resource Editor saves fields:

- `false` disables the listener.
- `true` or `'single'` keeps one `create_{table}_table` migration in sync.
- `'multiple'` creates a new migration for each save.

This feature flag does not give a resource its own table. `$customTable = true` does that. See [Custom tables](/docs/custom-tables).

<a id="configuration-troubleshooting"></a>

## Troubleshooting

If a config edit has no effect, check the resolved value with Tinker or a temporary route and clear the config cache:

```bash
php artisan config:clear
php artisan tinker
```

Saved theme settings override the corresponding configuration defaults. Check the options table for values selected on the Settings page. When teams are enabled, check the record for the current team.

If a newly discovered resource does not appear in navigation, verify its namespace and path in `aura-settings.paths.resources`, then clear the application cache. The navigation cache is separate from Laravel's config cache.

Changing whether teams are enabled after migration requires a schema and data migration. Do not use a destructive reset on an application that contains data.

## Related guides

- [Installation](/docs/installation)
- [Quick start](/docs/quick-start)
- [Creating resources](/docs/creating-resources)
- [Teams](/docs/teams)
- [Themes](/docs/themes)
- [Settings](/docs/settings)
- [Custom tables](/docs/custom-tables)
- [Media Library](/docs/media-manager)
