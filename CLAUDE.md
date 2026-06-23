# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install          # Install dev dependencies (PHPCS, coding standards)
composer run phpcs        # Check coding standards
composer run format       # Auto-fix coding standard violations (phpcbf)
composer run lint         # Alias for phpcs

pnpm install              # Install JS dependencies
pnpm run build            # Build JS/CSS assets
```

**Always use `pnpm` — never `npm` or `yarn`.**

## Architecture

**OutPulse** is a WordPress plugin (PHP 8.0+, WordPress 7.0+) that intercepts every outbound HTTP request via `WP_HTTP`, logs it to a custom DB table with source attribution, and will expose an admin UI for analysis.

### Boot sequence

`outpulse.php` → defines constants (`OUTPULSE_VERSION`, `OUTPULSE_DIR`, `OUTPULSE_TABLE`) → loads `vendor/autoload.php` → registers activation hook (`DB::create_table`) → `(new Bootstrap())->run()`.

`Bootstrap::run()` hooks `plugins_loaded` → `Bootstrap::boot()` checks `outpulse_logging_enabled` option → if enabled, calls `Interceptor::init()`.

### Request capture flow

1. `Interceptor::capture_args()` — fires on `http_request_args` (priority `PHP_INT_MAX`) to stash request headers and body before dispatch, keyed by `md5(url+method)`.
2. `Interceptor::on_response()` — fires on `http_api_debug` after every `WP_HTTP` response. Skips `context !== 'response'` (redirect intermediates). Hands off to `Logger::write()`.
3. `Logger::write()` — calls `Tracer::get_source()` for attribution, detects context (cli/cron/admin/frontend), then calls `DB::insert()`.
4. `Tracer::get_source()` — walks `debug_backtrace()`, skips OutPulse own frames and `wp-includes/` frames, returns the first frame in `wp-content/plugins/` or `wp-content/themes/`. Falls back to `[WordPress Core]` or `[Unknown]`.

### Namespace & autoloading

PSR-4: `Nilambar\Outpulse\` maps to `app/`. All plugin classes live under `app/Core/` for Phase 1. Future phases (admin UI, enrichment) will add sub-namespaces under `app/`.

### DB table

Single table `wp_outpulse_log` (constant `OUTPULSE_TABLE = 'outpulse_log'`). Created via `dbDelta()` on activation. Indexed on `domain`, `source_plugin`, `timestamp`. Dropped on uninstall.

## Quality Gate

Every task must end with:
1. `composer lint` — 0 errors, 0 warnings (run `composer format` to auto-fix first)

### Coding standards

- WordPress standard (`WordPress` ruleset) with `WordPress.DB` excluded — direct `$wpdb` calls are intentional.
- `NilambarCodingStandard` + Slevomat namespace rules enforced.
- All cross-namespace class references require explicit `use` statements (Slevomat `ReferenceUsedNamesOnly`). Global WordPress classes (`WP_Error`, `wpdb`) need `use` statements when referenced in namespaced files.
- `use` statements must be alphabetically sorted and free of duplicates/unused entries.
- Short array syntax `[]` throughout.
- Text domain: `outpulse`.
