# AI in Aura

Aura uses the official Laravel AI SDK for AI features in core and plugins. Provider selection, models, endpoints, and credentials use the host application's `config/ai.php`. Aura does not copy credentials into its database or provide a second connector abstraction.

Values created by the retired database-backed connector are ignored. Aura removes those legacy AI keys the next time the affected settings record is saved; move any credential that is still needed into the application's environment first.

The Aura installer publishes `config/ai.php` when the file does not already exist. After installation, add credentials to the application's environment and clear Laravel's configuration cache:

```bash
php artisan config:clear
```

## Configure a provider

Laravel AI includes providers such as OpenAI, Anthropic, Gemini, Ollama, and OpenAI-compatible endpoints. Select the default text provider with `ai.default` and configure its entry under `ai.providers`.

For example, OpenAI uses `OPENAI_API_KEY`. An OpenAI-compatible GLM or local endpoint can use `OPENAI_COMPATIBLE_URL` and `OPENAI_COMPATIBLE_API_KEY`. A trusted local endpoint may omit the key if the server accepts unauthenticated requests. Refer to the published file for the complete provider list.

Aura-wide AI features can be disabled independently:

```dotenv
AURA_AI_ENABLED=false
```

This sets `aura.ai.enabled` to `false`. `AiStatus::configured()` then returns `false`, and plugins should hide or disable AI actions.

## Provider status

Global Admins can open `/admin/settings/ai` to see which providers are configured, which one is the default, and whether credentials are present. The page never renders credential values and has no editable inputs or Save action. A connection test sends a short prompt through the selected Laravel AI provider and redacts configured credentials from errors.

After changing environment values in production, rebuild or clear the configuration cache before checking this page.

## Use the SDK directly

Core features and plugins call Laravel AI directly. Do not resolve an Aura connector or select a separate Aura provider:

```php
use function Laravel\Ai\agent;

$response = agent(
    instructions: 'Write concise search metadata.',
)->prompt('Summarize this article for a search result.');

$text = $response->text;
```

Omitting the provider lets the host application's `ai.default` configuration control deployment. Before presenting an AI action, check the shared status service:

```php
use Aura\Base\Ai\AiStatus;

if (app(AiStatus::class)->configured()) {
    // Show the AI action.
}
```

Use the Laravel AI SDK's fakes in automated tests so tests do not make network requests.

## Installation footprint

Installing Aura installs Laravel AI and registers its normal SDK services and generator commands. Aura publishes only the SDK configuration file. Laravel AI's optional conversation migration is not published or run by `aura:install`; applications that use persisted SDK conversations can publish that migration explicitly.
