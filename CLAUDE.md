# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Simple Add Banners is a WordPress plugin for displaying commercial banners with impression and click tracking. It provides banner management, placement configuration, and advertiser-ready statistics without external dependencies.

## Plugin Architecture

### Directory Structure
- `simple-add-banners.php` - Main plugin file with header metadata and constants
- `uninstall.php` - Cleanup logic when plugin is deleted
- `includes/` - Core PHP classes (shared logic)
- `admin/` - Admin-specific assets (css/, js/, images/)
- `public/` - Frontend assets (css/, js/, images/)
- `languages/` - Translation files

### Key Constants
```php
SIMPLE_ADD_BANNERS_VERSION   // Plugin version
SIMPLE_ADD_BANNERS_PLUGIN_DIR // Absolute path to plugin directory
SIMPLE_ADD_BANNERS_PLUGIN_URL // URL to plugin directory
```

### Text Domain
Use `simple-add-banners` for all translatable strings.

## Core Functionality (Per PROJECT_INITIATION.md)

### Data Model
- **Banners**: Desktop/mobile creatives, destination URLs, scheduling (start/end dates), weight/priority
- **Placements**: Unique slugs, multiple banners per placement, rotation strategies (random/weighted/ordered)
- **Statistics**: Aggregated daily by banner and placement (impressions, clicks)

### Rendering Pipeline
- Shortcode interface
- Widget interface
- Shared rendering logic

### Tracking Requirements
- Client-side impression tracking (must work under page caching)
- Click tracking via redirect endpoint
- Signed tracking tokens for security
- No raw IP storage (GDPR-aware)

## Security Patterns

Follow WordPress security standards:
```php
// Output escaping
esc_html(), esc_attr(), esc_url()

// Input sanitization
sanitize_text_field(), absint()

// Database queries
$wpdb->prepare()

// CSRF protection
wp_nonce_field(), wp_verify_nonce()

// Capability checks
current_user_can('manage_options')
```

## Development Commands

### Setup
```bash
composer install   # Install PHP dependencies
npm install        # Install Node dependencies
```

### Linting

```bash
# PHP (WordPress Coding Standards)
composer lint:php                              # Check all PHP files
composer lint:php:file -- path/to/file.php    # Check specific file
composer fix:php                               # Auto-fix all PHP files
composer fix:php:file -- path/to/file.php     # Auto-fix specific file
composer compat:php                            # Check PHP 7.4+ compatibility

# JavaScript
npm run lint:js                                # Check all JS files
npm run lint:js:file -- path/to/file.js       # Check specific file
npm run fix:js                                 # Auto-fix all JS files

# CSS
npm run lint:css                               # Check all CSS files
npm run lint:css:file -- path/to/file.css     # Check specific file
npm run fix:css                                # Auto-fix all CSS files

# Combined
npm run lint                                   # Check JS + CSS
npm run fix                                    # Fix JS + CSS
npm run lint:all                               # Check PHP + JS + CSS
npm run fix:all                                # Fix PHP + JS + CSS
```

## Development Notes

- Requires WordPress 6.0+ and PHP 7.4+
- No external SaaS dependencies allowed
- Statistics must be first-party and authoritative
- Impression definition must be clearly documented (e.g., viewport-based)
- Follow WordPress Coding Standards (see `docs/CODING_STANDARDS.md`)
