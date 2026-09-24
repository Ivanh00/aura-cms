# Aura CMS documentation

Documentation for Laravel developers using Aura CMS to define resources, fields, and admin workflows with the TALL stack.

These guides describe the current `main` branch. The public Composer release is `v1.0.0-beta.4`; follow [Installation](/installation) for release-specific setup.

## Getting started

- [Introduction](/introduction) - Learn how resources, fields, storage, teams, and authorization fit together
- [Installation](/installation) - Install Aura CMS and choose whether to enable teams
- [Quick start](/quick-start) - Generate a resource, add fields, choose storage, and grant access
- [Configuration](/configuration) - Configure paths, defaults, feature flags, and built-in resources

## Core concepts

- [Resources](/resources) - Define resource classes, fields, storage, navigation, and admin pages
- [Fields](/fields) - Configure built-in field types, visibility, defaults, validation, and storage
- [Meta fields](/meta-fields) - Store and query fields in the shared `meta` table
- [Custom tables](/custom-tables) - Move a resource to dedicated columns and manage its schema
- [Resource editor](/resource-editor) - Edit resource fields in the browser during local development

## Building

- [Creating resources](/creating-resources) - Generate and register resources, define fields, and customize behavior
- [Creating fields](/creating-fields) - Build custom field classes, lifecycle hooks, and Blade views
- [Table](/table) - Configure columns, search, filters, sorting, and actions
- [Widgets](/widgets) - Add metrics and charts to resource index pages
- [Recipes](/recipes) - Copy resource patterns for blogs, tags, custom tables, and attachments

## UI components

- [Livewire components](/livewire-components) - Use Aura's registered admin, modal, table, and media components
- [Media library](/media-manager) - Manage attachments, uploads, media picking, metadata, and thumbnails
- [Global search](/global-search) - Configure keyboard search across permitted resources and fields
- [Notifications](/notifications) - Use toast messages and Laravel database notifications, with host channels when needed

## Users and access

- [Authentication](/authentication) - Configure users, registration, invitations, password flows, and two-factor authentication
- [Roles and permissions](/roles-permissions) - Define role catalogs, team roles, resource permissions, and policies
- [Teams](/teams) - Configure teams, membership, switching, and how queries are scoped to a team
- [Settings](/settings) - Configure global and team settings in the admin panel
- [Profile](/profile) - Customize profile fields, password and two-factor settings, and account deletion

## Customization

- [Themes](/themes) - Configure palettes, semantic colors, fonts, dark mode, and assets
- [Customizing views](/customizing-views) - Override package views, table views, components, and layouts
- [Record layouts](/record-layouts) - Add plugin and resource panels to record pages
- [Preferences](/preferences) - Define typed values with user, team, and instance-wide scopes
- [Plugins](/plugins) - Package resources, fields, widgets, routes, and views for reuse
- [Hooks and events](/hooks-events) - Register navigation, view, resource, field, and Livewire extension hooks

## Advanced

- [API reference](/api-reference) - Reference the Aura facade, Resource and Field contracts, and host API patterns
- [AI in Aura](/ai-connector) - Configure Laravel AI providers for core features and plugins
- [Frontend integration](/frontend-integration) - Query resource data in Blade or a host API and resolve field values
- [Performance](/performance) - Measure requests and tune storage, caching, queries, media, and widgets
- [Testing](/testing) - Test resources, fields, permissions, Livewire components, and browser flows
- [Migration](/migration) - Upgrade releases and move resource data between storage models
- [Troubleshooting](/troubleshooting) - Diagnose asset, navigation, storage, team, permission, validation, and media issues
- [Best practices](/best-practices) - Apply conventions for resources, fields, storage, authorization, and testing
