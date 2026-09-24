# Aura uses the official Laravel AI SDK

Aura core and plugins call `laravel/ai` directly. Provider credentials, endpoints, models, and the default provider belong to the host application's environment-backed `config/ai.php`. Aura keeps only a small status service for availability checks and connection testing.

The previous Aura connector duplicated provider transports, response parsing, configuration, and secret storage already owned by the SDK. It also made plugin integrations depend on an Aura-specific abstraction and placed deployment credentials in team-scoped database settings.

## Consequences

- `laravel/ai` is a required core dependency.
- Aura does not store or edit AI credentials in the database.
- Plugins use the SDK default provider unless their own documented feature requires otherwise.
- `/admin/settings/ai` is a Global Admin-only, read-only status page.
- `AURA_AI_ENABLED=false` disables Aura AI features without changing provider configuration.
- The Aura installer publishes the SDK configuration file but not its optional conversation migration.
