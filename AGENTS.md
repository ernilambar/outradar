# OutRadar

## Overview

WordPress plugin that logs outbound HTTP requests. PHP 7.4+ backend with namespaced classes under `app/`, Vite-built JavaScript/CSS admin assets under `src/`.

## Setup

```bash
pnpm install
composer install
```

## Commands

```bash
pnpm run build          # Build JS/CSS assets via Vite (output: build/)
composer run lint       # PHP syntax check + PHPCS (WordPress standards)
composer run format     # Auto-fix PHP via PHPCBF
pnpm run format         # Auto-fix JS/CSS/JSON via Prettier
pnpm run ready          # Build assets + install production vendor
```

## Conventions

- **Namespace mapping**: `Nilambar\OutRadar\` → `app/`. Sub-namespaces match subdirectory names (e.g., `Core`, `Services`, `Admin`, `HTTP`, `Utils`).
- **Class filenames**: Pascal case with underscores (e.g., `Cron_Tracker.php`, `WP_Utils.php`).
- **ABSPATH guard**: Every PHP file must start with `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- **Text domain**: All strings use `'outradar'`. Generate POT with `composer run pot`.
- **Security**: Escape all output (`esc_html`, `esc_attr`, `esc_url_raw`), sanitize all input, use nonces for AJAX/admin actions. Never trust user data.
- **Database**: Use `$wpdb` with `$wpdb->prepare()` for all queries. Table name via `$wpdb->prefix . OUTRADAR_TABLE`.

## Quality Gate

Run these commands and verify exit code 0 before declaring a task complete:

```bash
composer run lint
pnpm run build
```
