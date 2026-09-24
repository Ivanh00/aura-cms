# Settings

The Settings page lets administrators configure the admin theme and installed plugins. Aura saves these choices in a database option record, separately from configuration files and user preferences. AI provider credentials remain in environment-backed configuration; the AI page is read-only.

![Settings page](/images/docs/settings/settings-general.png)

## Settings page

The page is available at `/admin/settings` by default. If you change the admin path through `aura.path`, the settings URL becomes `/{path}/settings`. You can link to it using the named route `aura.settings`.

Aura uses its built-in Livewire settings component by default. You can replace it through `aura.components.settings`:

~~~php
// config/aura.php
'components' => [
    'settings' => Aura\Base\Livewire\Settings::class,
],
~~~

The route uses the middleware configured as `aura-settings.middleware.aura-admin`, which includes the `web` and `auth` middleware.

## Access and authorization

When the page opens, the component checks that the settings feature is enabled and that the user is a super admin:

| Check | Result when it fails |
| --- | --- |
| `config('aura.features.settings')` | 404 |
| `auth()->user()->isSuperAdmin()` | 403 |

Set the feature flag to `false` to remove the page:

~~~php
// config/aura.php
'features' => [
    'settings' => false,
],
~~~

The General page requires a super admin role in the current team. Registered plugin pages may declare their own view and update abilities. The read-only AI status page is the exception: it requires the instance-level Global Admin gate and is not available to a team Super Admin.

The separate option resource follows the normal resource policy. Super admins and global admins have blanket access through that policy, while other users need the relevant resource permissions. Access to the option resource does not grant access to the Settings page.

## Fields on the page

Every registered settings page has its own sidebar entry and URL:

1. **Settings** (`/admin/settings`, route `aura.settings`) contains the theme and appearance fields below.
2. **AI** (`/admin/settings/ai`) shows configured Laravel AI SDK providers and connection tests to Global Admins. It contains no editable credentials.
3. Installed plugins add their own pages with `Aura::registerSettingsPages()`. A page with the slug `seo` is served at `/admin/settings/seo`. Link to it with `route('aura.settings.page', 'seo')`.

Editable pages save into the same settings record. Saving one page leaves the values of the other pages untouched.

See [Plugins](/docs/plugins#register-a-settings-page) for the plugin registration interface.

| Field | Slug | Type | Values or behavior |
| --- | --- | --- | --- |
| Logo | `logo` | `Image` | Selects an attachment for the admin navigation |
| Logo Darkmode | `logo-darkmode` | `Image` | Selects the dark-mode navigation logo |
| Size | `sidebar-size` | `Radio` | `standard`, `compact` |
| Sidebar | `sidebar-type` | `Radio` | `primary`, `light`, `dark` |
| Darkmode | `darkmode-type` | `Radio` | `auto`, `light`, `dark` |
| Sidebar Darkmode | `sidebar-darkmode-type` | `Radio` | `primary`, `light`, `dark`, shown when `darkmode-type` is `auto` |
| Primary Color Palette | `color-palette` | `Select` | Preset palette or `custom` |
| Gray Color Palette | `gray-color-palette` | `Select` | Preset palette or `custom` |

The primary palette options are:

~~~text
aura, red, orange, amber, yellow, lime, forest-green, green, emerald,
mountain-meadow, teal, ocean-breeze, cyan, sky, blue, indigo, violet,
purple, fuchsia, pink, rose, sandal, desert-sand, salmon, autumn-rust,
slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc,
neutral, stone, sandstone, rose-quartz, olive, smaragd, custom
~~~

The gray palette options are:

~~~text
slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc,
neutral, stone, sandstone, rose-quartz, olive, smaragd, custom
~~~

Selecting a custom palette shows color fields for each of these shades:

~~~text
primary-25, primary-50, primary-100, primary-200, primary-300,
primary-400, primary-500, primary-600, primary-700, primary-800,
primary-900, primary-950

gray-25, gray-50, gray-100, gray-200, gray-300, gray-400,
gray-500, gray-600, gray-700, gray-800, gray-900, gray-950
~~~

The built-in page does not include login backgrounds, favicons, fonts, or semantic colors. Configure those under `aura.theme` using the `login-bg`, `login-bg-darkmode`, `app-favicon`, `app-favicon-darkmode`, `font`, and `colors` keys. You can also expose them through a custom settings component.

## Storage and Team context

Aura stores settings in the `options` table through its option resource:

~~~php
namespace Aura\Base\Resources;

use Aura\Base\Resource;

class Option extends Resource
{
    public static $customTable = true;
    public static ?string $slug = 'option';
    public static string $type = 'Option';

    protected $table = 'options';
    protected $fillable = ['name', 'value', 'team_id'];
    protected $casts = ['value' => 'array'];
}
~~~

The settings are stored as JSON in the value column. Eloquent casts them to a PHP array when you read the record. `SettingsStore` owns settings persistence, current-team resolution, secret encryption, and cache invalidation.

### Teams enabled

Teams are enabled by default, and each team has its own settings. When the page opens, it loads or creates a row for the current team:

~~~text
name: team.{currentTeamId}.settings
team_id: currentTeamId
~~~

With teams enabled, option queries are scoped to the current team. When saving an option without a team ID, Aura fills it from the authenticated user's current team. Both the option name and its `team_id` column identify the team.

When the user switches teams, the Settings page reads the new team's settings. It does not merge a shared database settings row into each team's settings.

### Teams-off mode

Set `aura.teams` to `false`, normally through `AURA_TEAMS=false`. The page uses one row:

~~~text
name: settings
team_id: null
~~~

With teams disabled, option queries have no team scope. Reading settings through the Aura facade returns this shared row.

## Defaults and precedence

When it first creates the settings row, Aura combines defaults from every registered page. The General page contributes six appearance settings from the application's theme configuration:

~~~json
{
    "darkmode-type": "auto",
    "sidebar-type": "dark",
    "color-palette": "aura",
    "gray-color-palette": "slate",
    "sidebar-size": "standard",
    "sidebar-darkmode-type": "dark"
}
~~~

The example shows the shipped defaults. If your application changes `aura.theme`, Aura uses your configured values when it creates the row.

After loading the row, the component prepares a form value for every declared input. Saving the form writes all of those values back to the option record. The saved settings can therefore include logos, custom color shades, and empty values alongside the six initial keys.

Runtime theme resolution uses three sources in this order:

1. Aura's package theme defaults.
2. The host application's `config('aura.theme')`.
3. The saved settings for the current team, or the shared settings when teams are disabled.

Later sources override earlier ones. Theme token resolution uses this order for fonts and semantic colors. Navigation uses saved sidebar settings, falling back to the theme configuration when a key is missing. The color renderer uses the saved palette names and custom shade values.

Take care when choosing a helper to read settings. Both `Aura::option('theme')` and `Aura::options()` read application configuration, not saved database values. Use `Aura::setting()` for values managed by the Settings page.

## Reading and updating settings

Read a current setting through the Aura facade:

~~~php
use Aura\Base\Facades\Aura;

$palette = Aura::setting(
    'color-palette',
    config('aura.theme.color-palette'),
);
~~~

The helper selects the current team's settings when teams are enabled. Do not store credentials in Aura settings; use environment-backed Laravel configuration.

Use `SettingsStore` to update the current context programmatically:

~~~php
use Aura\Base\Settings\SettingsStore;

app(SettingsStore::class)->put('color-palette', 'emerald');
~~~

The store writes to the current team's settings row when teams are enabled, or the shared settings row when they are disabled. It does not check permissions, so authorize the caller before invoking it.

Aura does not generate a REST endpoint for settings or options. If your application needs an HTTP API, define a route and controller and apply your own authorization.

## The Option Resource

The option resource provides a separate interface for managing option records at `/admin/option` by default. It is registered under `aura.resources.option`.

It declares two fields:

| Field | Slug | Type | Rules |
| --- | --- | --- | --- |
| Name | `name` | `Text` | Required, shown on the index |
| Value | `value` | `Textarea` | Required, hidden from the index |

The model casts stored JSON values to PHP arrays. To find an option by name under the current query scope, use `Option::byName($name)`.

Use this resource to create, read, update, and delete generic option records. For theme settings, prefer the Aura facade helpers shown above. They handle the settings name, team context, and cache together.

## User preferences

User preferences are separate from the Settings page. Use the user model's option methods to read, update, or delete them. These methods prefix each name with `user.{userId}.`.

~~~php
$user = auth()->user();

$user->updateOption('sidebarToggled', false);
$user->updateOption('table_view.Post', 'kanban');

$view = $user->getOption('table_view.Post');

$user->deleteOption('table_view.Post');
~~~

With teams enabled, Aura saves and reads preferences for both the user and their current team. A user can therefore have different preferences in each team. With teams disabled, preference rows have no team ID.

Aura's navigation uses user preferences for sidebar collapse and expanded groups. Table components use user preferences for table views, columns, column order, saved filters, Kanban statuses, and bookmarks. These rows do not override the theme settings row.

The team model has the same methods for team-specific application options. Its option methods prefix names with `team.{teamId}.`.

## Extending Settings

Composer plugins should register a `SettingsPage`; this keeps storage, ordering, field collision checks, and secret handling inside Aura. See [Register a settings page](/docs/plugins#register-a-settings-page).

Applications can still replace the whole Livewire component when they need custom behavior that a registered page cannot provide. Extend the built-in component and register it under `aura.components.settings`:

~~~php
namespace App\Livewire;

use Aura\Base\Livewire\Settings as BaseSettings;

class CustomSettings extends BaseSettings
{
    public static function getFields(): array
    {
        return array_merge(parent::getFields(), [
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Custom',
                'slug' => 'panel-custom',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Support email',
                'slug' => 'support-email',
            ],
        ]);
    }
}
~~~

~~~php
// config/aura.php
'components' => [
    'settings' => App\Livewire\CustomSettings::class,
],
~~~

Aura saves the added support email field alongside the other settings. Read it through the facade:

~~~php
$email = Aura::setting('support-email');
~~~

## Cache invalidation

Saving the built-in Settings page invalidates only the cache entries for the shared or current-team settings record. Programmatic changes should use `SettingsStore::put()` or `SettingsStore::store()` for the same scoped invalidation.

Updating the option model directly bypasses these guarantees.

The user model's update and delete methods clear the cache entry for the preference they change. Team option writes also clear their prefixed entry. The facade cache key includes the current team ID, so switching teams selects a different cached value.

## Supported APIs

These are the supported PHP entry points for this data:

| API | Reads or writes | Context |
| --- | --- | --- |
| `Aura::setting($key, $default, $teamId)` | Reads a saved setting | Current team when teams are enabled. Pass `$teamId` when there is no authenticated user, for example on public routes |
| `SettingsStore::put($key, $value)` | Writes one setting | Current team when teams are enabled |
| `SettingsStore::store($option, $values)` | Writes a form payload | The supplied settings option |
| `Aura::getOption($name)` | Reads a cached option value | Current team when teams are enabled |
| `Aura::updateOption($name, $value)` | Writes an option value and clears facade cache | Current team when teams are enabled |
| `Aura::option($key)` | Reads a top-level value from `config('aura')` | Application configuration |
| `Aura::options()` | Reads the full `config('aura')` array | Application configuration |
| `Option::byName($name)` | Reads an option model | Current query scope |
| `Team::getOption()` / `updateOption()` / `deleteOption()` | Reads or writes team-prefixed options | One team |
| `User::getOption()` / `updateOption()` / `deleteOption()` | Reads or writes user-prefixed options | One user and current team |
| `Aura::registerSettingsPages($source, $pages)` | Registers settings pages, fields, and defaults | Application boot process |
| `Settings::getFields()` | Resolves the fields from the settings registry | The configured Settings component |

Authorize programmatic writes in your application. The Settings page and the resource interface check access, but the model and facade helpers do not.

## Related guides

- [Themes](/docs/themes) for palette rendering and theme tokens
- [Configuration](/docs/configuration) for `aura.php` and component configuration
- [Teams](/docs/teams) for team context and teams-off mode
- [Fields](/docs/fields) for field definitions
- [Scoped preferences](/docs/preferences) for typed preference declarations
