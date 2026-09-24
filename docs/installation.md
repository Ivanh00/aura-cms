# Installation

Install Aura in a fresh Laravel application, then sign in to the admin panel. The public Composer release is version 1.0.0-beta.4, referred to as beta4 in this guide. The main branch is newer and may include installer options and fixes that are not in the beta release.

## Requirements

- PHP 8.4 or newer
- Laravel 13 for the main branch. Beta4 also supports Laravel 12.
- Livewire 4, installed by Composer
- Composer 2
- The PHP GD extension, which Aura uses to generate thumbnails with Intervention Image3.
- A configured database connection

Configure the database in `.env` before running the installer. A fresh Laravel application can use its default SQLite database. If you use Laravel Herd, open the application's `.test` URL after installation instead of running `php artisan serve`.

## Install the public beta

Create a fresh application without authentication scaffolding when the Laravel installer asks:

```bash
laravel new aura-new
cd aura-new
```

When using Herd, set the local application URL in `.env`:

```dotenv
APP_NAME="Aura New"
APP_URL=http://aura-new.test
```

Install the public beta and start the guided installer:

```bash
composer require 'eminiarts/aura-cms:^1.0@beta'
php artisan aura:install
```

Follow the prompts to update Laravel's standard user model, configure Aura, run the migrations, and create the first administrator. For beta4, answer **Yes** when asked whether to use teams. If you do not need teams, use the [separate installation sequence](#without-teams) below.

The administrator creation prompt grants Global Admin status by default. Disable public registration if only administrators should create accounts.

Beta4 does not create the public storage link. Run this after the installer:

```bash
php artisan storage:link
```

Do not pass `--teams=false` to the beta4 installer. Although it writes the setting, the migration in the same process does not read the updated configuration. The separate installation sequence below avoids this issue.

<a id="local-checkout"></a>

## Use current main from a local checkout

Use a path repository when you need the current package checkout during development. The example assumes the Aura checkout is next to the Laravel application at `../aura-cms`.

```bash
composer config repositories.aura-cms path ../aura-cms
composer require 'eminiarts/aura-cms:dev-main'
php artisan aura:install
```

The installer on the main branch creates the public storage link at `public/storage` when it is missing. It applies your teams and registration choices before running the migration and stops if any command it runs fails.

## Scripted installation on current main

On the current main branch, the installer validates your options before publishing files or changing the database. Supply all administrator details as shown below. To create the administrator later, pass `--no-admin`, then run `php artisan aura:user` after installation:

```bash
php artisan aura:install \
    --no-interaction \
    --teams=false \
    --registration=false \
    --admin-name="Aura Admin" \
    --admin-email="admin@example.com" \
    --admin-password="use-a-secret-value"
```

The administrator password must contain at least eight characters, and the email address must be valid. Replace the example password before running the command.

To enable teams, change `--teams=false` to `--teams=true`. On the current main branch, you can also supply `--team-name` or use `--no-global-admin` to create an administrator without Global Admin status.

## What `aura:install` does

The installer publishes Aura's two configuration files, Laravel AI's `config/ai.php` when it is not already present, compiled assets, and Aura migrations. It does not publish Laravel AI's optional conversation migration. The assets go in `public/vendor/aura`. Depending on your answers to the prompts, it then updates the user model, applies your configuration choices, runs the migrations, and creates an administrator. The Aura migration creates Aura's tables and adds the required columns to Laravel's existing users table.

The user model command, `aura:extend-user-model`, updates the standard Laravel model in `app/Models/User.php`. It expects the model to extend `Authenticatable`. If your application has a custom user model, update it manually to extend `Aura\Base\Resources\User` while preserving its existing contracts and behavior.

To republish Aura's compiled assets, run the following command. You do not need to build Aura with npm or change the application's Vite configuration:

```bash
php artisan aura:publish
```

Aura's media library uses Laravel's public disk by default. Uploaded media will only be accessible once the public storage link exists. The installer on the current main branch creates it for you. Beta4 requires the storage link command shown above.

<a id="without-teams"></a>

## Install beta4 without teams

After installing the beta4 package with Composer, run the following commands instead of the guided installer. Each command starts a new PHP process, so the migration reads the teams setting saved by the configuration command:

```bash
php artisan vendor:publish --tag=aura-config
php artisan aura:install-config --no-interaction --teams=false --registration=false
php artisan aura:extend-user-model
php artisan vendor:publish --tag=aura-migrations
php artisan migrate
php artisan aura:publish
php artisan aura:user
php artisan storage:link
```

Keep teams disabled in `config/aura.php` by leaving the `teams` setting at `false`. Changing this setting after installation requires a planned schema migration. Do not use a destructive reset command on an application that contains data.

You can use the installer without interactive prompts and supply the administrator details directly:

```bash
php artisan aura:install \
    --no-interaction \
    --teams=true \
    --registration=false \
    --admin-name="Aura Admin" \
    --admin-email="admin@example.com" \
    --admin-password="use-a-secret-value"
php artisan storage:link
```

Beta4 can display its final installer message even if one of its commands fails. Check the output for errors and verify that the login page works before treating the installation as complete.

## Sign in

The default admin path is `/admin`, and the login path is `/login`. Open the application's URL followed by `/login` and use the administrator credentials entered during installation.

For the `aura-new` Herd application, the usual URL is:

```text
http://aura-new.test/login
```

After signing in, open `/admin`. See [Configuration](/docs/configuration) for the path and domain settings.

![Login page](/images/docs/installation/login-page.png)

![Dashboard](/images/docs/installation/dashboard-overview.png)

## Create a resource

After the admin panel works, create the resource from the [Introduction](/docs/introduction) example:

```bash
php artisan aura:resource Article
```

Edit the generated class, then open `/admin/article`. By default, Aura stores records in its posts table and additional field values in its meta table. You can add fields outside the core columns without a migration. The title and content fields use existing columns in the posts table.

If teams are enabled, Aura saves the new record in the current team. See [Teams](/docs/teams) before changing the teams setting.

## Troubleshooting

### Aura-owned tables already exist

The migration stops if the database already contains tables with names Aura needs, such as `posts`, `meta`, `roles`, or `permissions`. Use a database without these conflicts, or plan how to resolve the conflicting schemas. Do not drop an existing application's tables as an installation shortcut.

### The User model was not updated

If you declined the prompt to update the user model, run the following command and accept it:

```bash
php artisan aura:extend-user-model
```

The command only edits a standard user model that contains `extends Authenticatable`. Review custom user models manually.

### Configuration changes have no effect in beta4

In beta4, the configuration command writes your choices directly into `config/aura.php`, replacing any environment variable expressions. Review this file after installation. If the application has cached configuration, run `php artisan config:clear`.

The command on the current main branch preserves `env()` expressions and writes your selected values to `.env`.

Beta4 can also write `var(--primary-600]` into `theme.colors.light.primary` and `theme.colors.dark.primary`. Correct those values to `var(--primary-600)` if present.

### Assets or media are missing

Republish assets, create the storage link, and verify that GD is enabled:

```bash
php artisan aura:publish
php artisan storage:link
php -m | grep -i '^gd$'
```

### A new resource is missing from the sidebar

Beta4 caches the navigation list. Clear the application cache after adding a resource:

```bash
php artisan cache:clear
```

<a id="deployment"></a>

## Deployment

Commit the published Aura configuration and migrations with the application. During deployment, run `php artisan migrate --force`. After updating the Aura package, republish its assets with `php artisan aura:publish`.

## Next steps

- [Quick Start](/docs/quick-start)
- [Resources](/docs/resources)
- [Fields](/docs/fields)
- [Configuration](/docs/configuration)
