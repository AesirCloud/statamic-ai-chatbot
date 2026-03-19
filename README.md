# AesirCloud Statamic AI Chatbot

`aesircloud/statamic-ai-chatbot` is a Statamic addon scaffold for building a provider-flexible AI chatbot with FAQ handling, knowledge retrieval, support escalation, and lead capture.

## Included in this scaffold

- Reusable Statamic addon package structure under the `AesirCloud\\StatamicAiChatbot` namespace
- Configurable provider defaults for text generation, embeddings, and reranking
- Database models and migrations for bot profiles, FAQs, sources, knowledge chunks, chats, and leads
- Public widget tag via `{{ ai_chatbot:widget }}` with Vue-powered chat and lead capture UI
- Control panel utility surface for profile visibility, provider defaults, and sync triggering
- Source driver contracts and first-party Statamic content plus YouTube transcript connectors
- Lead delivery drivers for database, email, and webhook dispatch
- Console commands for knowledge sync and retention pruning

## Installation

1. Require the package in a Statamic 5.x or 6.x app running Laravel 12+ and PHP 8.3+.
2. Publish the package config:

```bash
php artisan vendor:publish --tag=statamic-ai-chatbot-config
```

3. Publish and run the package migrations along with the Laravel AI SDK migrations.
4. Install the frontend dependencies in the host app and build the addon assets:

```bash
npm install
npm run build
```

5. Add a bot profile record, FAQs, and source connections, then render the widget in Antlers:

```antlers
{{ ai_chatbot:widget profile="default" }}
```

## Configuration notes

- `config/statamic-ai-chatbot.php` controls provider defaults, widget defaults, retention, queue behavior, and lead destinations.
- Text generation and embeddings are intentionally separated, so you can use one provider for chat and another for retrieval.
- The current YouTube connector expects transcript text in the source connection config. It already handles URL normalization and can be extended to use a real transcript ingestion provider later.

## Current implementation boundaries

- The control panel utility is a functional dashboard, not yet a full CRUD editor for all records.
- The widget currently uses JSON requests instead of server-sent streaming.
- Knowledge retrieval is keyword-first with optional embeddings stored as JSON arrays, which keeps the package database-agnostic for v1.
- Public widget asset delivery assumes the package assets are built and published with the host application.
