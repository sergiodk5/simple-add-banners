# Simple Add Banners

A self-hosted WordPress plugin for displaying commercial banners with impression and click tracking. Built for publishers who need transparent, advertiser-ready statistics without external dependencies or licensing restrictions.

## Features

### Implemented (MVP)
- Banner management with desktop/mobile image variants
- WordPress Media Library integration for image selection
- REST API for banner CRUD operations
- Vue 3 + TypeScript admin interface with PrimeVue components
- Start/end date scheduling
- Status management (active/inactive)
- Weight-based priority system

### Planned
- Placement management (define where banners appear)
- Banner rotation strategies (random, weighted, ordered)
- Shortcode rendering
- Widget support
- Impression tracking (client-side, cache-safe)
- Click tracking via redirect endpoint
- Aggregated daily statistics
- Admin reporting UI
- CSV export

## Requirements

- WordPress 6.0+
- PHP 8.4+
- Node.js 18+ (for admin UI development)

## Installation

1. Clone or download the plugin to `wp-content/plugins/simple-add-banners/`
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install and build the admin UI:
   ```bash
   cd admin-ui
   npm install
   npm run build
   ```
4. Activate the plugin in WordPress admin

## Development

### Setup

```bash
# Install PHP dependencies
composer install

# Install Node dependencies for admin UI
cd admin-ui && npm install
```

### Development Server

```bash
cd admin-ui
npm run dev
```

### Build for Production

```bash
cd admin-ui
npm run build
```

### Linting

```bash
# PHP (WordPress Coding Standards)
composer lint:php              # Check all PHP files
composer fix:php               # Auto-fix PHP files
composer compat:php            # Check PHP 8.4+ compatibility

# JavaScript/TypeScript (ESLint)
npm run lint:js                # Check JS/TS files
npm run fix:js                 # Auto-fix JS/TS files

# CSS (Stylelint)
npm run lint:css               # Check CSS files
npm run fix:css                # Auto-fix CSS files

# Combined
npm run lint                   # Check JS + CSS
npm run fix                    # Fix JS + CSS
npm run lint:all               # Check PHP + JS + CSS
npm run fix:all                # Fix PHP + JS + CSS
```

### Testing

```bash
# PHP unit tests (Pest)
composer test

# PHP tests with coverage
composer test:coverage

# Vue unit tests (Vitest)
cd admin-ui
npm run test:run

# Vue tests with coverage
npm run test:coverage
```

### Dependency Scoping

Runtime dependencies are scoped using wpify/scoper to prevent conflicts with other plugins:

```bash
composer scoper        # Scope dependencies to lib/ folder
composer scoper:clean  # Remove scoped dependencies
```

## Architecture

### Directory Structure

```
simple-add-banners/
├── simple-add-banners.php    # Bootstrap file
├── uninstall.php             # Cleanup on deletion
├── src/                      # PHP classes (PSR-4)
│   ├── Plugin.php            # Main plugin class
│   ├── Admin/                # Admin functionality
│   ├── Api/                  # REST API endpoints
│   ├── Database/             # Database operations
│   └── ...
├── admin-ui/                 # Vue 3 admin interface
│   ├── src/
│   │   ├── components/       # Vue components
│   │   ├── services/         # API client & services
│   │   ├── types/            # TypeScript definitions
│   │   └── ...
│   └── ...
├── assets/                   # Compiled assets
├── languages/                # Translation files
└── docs/                     # Documentation
```

### Namespace

Uses PSR-4 autoloading with the `SimpleAddBanners` namespace:

```php
SimpleAddBanners\              # Root namespace
SimpleAddBanners\Admin\        # Admin classes
SimpleAddBanners\Api\          # REST API classes
SimpleAddBanners\Database\     # Database classes
```

### Key Constants

```php
SIMPLE_ADD_BANNERS_VERSION     # Plugin version
SIMPLE_ADD_BANNERS_PLUGIN_DIR  # Absolute path to plugin
SIMPLE_ADD_BANNERS_PLUGIN_URL  # URL to plugin directory
```

### Text Domain

Use `simple-add-banners` for all translatable strings.

## Security

The plugin follows WordPress security best practices:

- Output escaping with `esc_html()`, `esc_attr()`, `esc_url()`
- Input sanitization with `sanitize_text_field()`, `absint()`
- Prepared database queries with `$wpdb->prepare()`
- CSRF protection with nonces
- Capability checks for admin operations
- Signed tracking tokens (planned)
- No raw IP storage (GDPR-aware)

## License

GPL v2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

Asterios Patsikas - [sergiodk5.com](https://sergiodk5.com)
