# WordPress Coding Standards

This document outlines the coding standards for the Simple Add Banners plugin, based on the official WordPress Coding Standards Handbook.

## Table of Contents

1. [Namespaces and Autoloading](#namespaces-and-autoloading)
2. [PHP Coding Standards](#php-coding-standards)
3. [JavaScript Coding Standards](#javascript-coding-standards)
4. [CSS Coding Standards](#css-coding-standards)
5. [Documentation Standards](#documentation-standards)
6. [Automated Tools](#automated-tools)

---

## Namespaces and Autoloading

This plugin uses PSR-4 autoloading with the `SimpleAddBanners` namespace.

### Directory to Namespace Mapping

| Directory | Namespace |
|-----------|-----------|
| `src/` | `SimpleAddBanners\` |
| `src/Admin/` | `SimpleAddBanners\Admin\` |
| `src/Frontend/` | `SimpleAddBanners\Frontend\` |
| `src/Tracking/` | `SimpleAddBanners\Tracking\` |

### Creating a New Class

1. Create the file in the appropriate directory:

```php
// src/Admin/Banner_List.php
<?php
namespace SimpleAddBanners\Admin;

class Banner_List {

    public function __construct() {
        // ...
    }
}
```

2. Regenerate the autoloader:

```bash
composer dump-autoload
```

3. Use the class anywhere:

```php
use SimpleAddBanners\Admin\Banner_List;

$list = new Banner_List();

// Or with full namespace
$list = new \SimpleAddBanners\Admin\Banner_List();
```

### Namespace Guidelines

- Root namespace is `SimpleAddBanners`
- Sub-namespaces match directory structure
- One class per file
- Class name matches filename exactly
- Use `use` statements at the top of files for cleaner code

### Third-Party Dependencies

Runtime dependencies are scoped to `SimpleAddBanners\Vendor` to prevent conflicts:

```php
// Using a scoped dependency
use SimpleAddBanners\Vendor\SomePackage\SomeClass;

$obj = new SomeClass();
```

---

## PHP Coding Standards

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Variables | Lowercase with underscores | `$banner_id` |
| Functions | Lowercase with underscores | `get_banner_stats()` |
| Actions/Filters | Lowercase with underscores | `simple_banners_before_render` |
| Classes/Traits/Interfaces | Capitalized with underscores | `Banner_Manager` |
| Constants | Uppercase with underscores | `SIMPLE_ADD_BANNERS_VERSION` |
| Namespaces | PascalCase | `SimpleAddBanners\Admin` |

### File Naming (PSR-4)

This plugin uses PSR-4 autoloading. Class files match the class name exactly:

| Class | File Path |
|-------|-----------|
| `SimpleAddBanners\Plugin` | `src/Plugin.php` |
| `SimpleAddBanners\Admin\Banner_Settings` | `src/Admin/Banner_Settings.php` |
| `SimpleAddBanners\Tracking\Click_Handler` | `src/Tracking/Click_Handler.php` |

**Note:** We use WordPress-style class names (underscores) with PSR-4 file naming.

### PHP Tags

Always use full PHP tags:

```php
// Correct
<?php echo esc_html( $variable ); ?>

// Incorrect
<? echo $variable; ?>
<?= $variable ?>
```

### Indentation

Use **real tabs**, not spaces. Exception: mid-line alignment may use spaces.

```php
$foo   = 'somevalue';
$foo2  = 'somevalue2';
$foo34 = 'somevalue3';
```

### Brace Style

Opening braces on the same line. Always use braces, even for single statements:

```php
// Correct
if ( $condition ) {
	do_something();
}

// Incorrect
if ( $condition )
	do_something();
```

### Space Usage

Spaces after commas and around operators:

```php
// Correct
$x = ( $a + $b ) * $c;
foo( $arg1, $arg2, $arg3 );
if ( ! $condition ) {

// Incorrect
$x=($a+$b)*$c;
foo($arg1,$arg2,$arg3);
if (!$condition) {
```

Control structures have spaces inside parentheses:

```php
// Correct
if ( $condition ) {
foreach ( $items as $item ) {

// Incorrect
if ($condition) {
foreach ($items as $item) {
```

### Arrays

Use long array syntax with spaces:

```php
// Correct
$array = array( 1, 2, 3 );

// Incorrect
$array = [1, 2, 3];
```

Multi-item arrays on multiple lines with trailing commas:

```php
$array = array(
	'first'  => 'value1',
	'second' => 'value2',
	'third'  => 'value3',
);
```

### Quotes

Use single quotes by default. Double quotes only for variable interpolation:

```php
// Correct
$str = 'Hello World';
$str = "Hello {$name}";

// Incorrect
$str = "Hello World";
$str = 'Hello ' . $name;
```

### Yoda Conditions

Place the constant/literal on the left side:

```php
// Correct
if ( true === $condition ) {
if ( 'value' === $variable ) {

// Incorrect
if ( $condition === true ) {
if ( $variable === 'value' ) {
```

### Type Casts

Use lowercase short forms with no space:

```php
// Correct
$int = (int) $value;
$bool = (bool) $value;
$float = (float) $value;

// Incorrect
$int = (integer) $value;
$int = ( int ) $value;
```

### Include/Require

**For classes in `src/`**: Use the Composer autoloader - no manual requires needed.

```php
// Classes are autoloaded - just use them
use SimpleAddBanners\Admin\Banner_Settings;

$settings = new Banner_Settings();
```

**For other files**: Use `require_once` without parentheses:

```php
// Correct
require_once SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'vendor/autoload.php';

// Incorrect
require_once( SIMPLE_ADD_BANNERS_PLUGIN_DIR . 'vendor/autoload.php' );
include 'file.php';
```

### Conditionals

Use `elseif`, not `else if`:

```php
// Correct
if ( $a ) {
	// ...
} elseif ( $b ) {
	// ...
}

// Incorrect
if ( $a ) {
	// ...
} else if ( $b ) {
	// ...
}
```

### Operators

Pre-increment preferred for standalone statements:

```php
// Preferred
++$i;

// Acceptable
$i++;
```

Avoid the error control operator `@`. Use proper error checking instead.

### OOP Rules

- Always declare visibility (`public`, `protected`, `private`)
- One class/interface/trait per file
- Always use parentheses for instantiation: `new Foo()`
- Order: visibility → static/readonly → type

```php
// Correct
public static int $count = 0;
private readonly string $name;

// Incorrect
static public $count = 0;
var $name;
```

### Database Queries

Always use prepared statements:

```php
// Correct
$wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}banners WHERE id = %d AND status = %s",
	$banner_id,
	'active'
);

// Placeholders: %d (integer), %f (float), %s (string), %i (identifier)
```

### What to Avoid

- `extract()` - makes code hard to debug
- `eval()` - security risk
- Backtick operator - use `exec()` if needed
- `goto` - creates spaghetti code
- Loose comparisons without careful consideration

---

## JavaScript Coding Standards

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Variables/Functions | camelCase | `bannerCount`, `getBannerStats()` |
| Constants | SCREAMING_SNAKE_CASE | `MAX_BANNERS` |
| Constructors/Classes | UpperCamelCase | `BannerManager` |

### Indentation and Spacing

Use **tabs** for indentation. Lines should be 80 characters max (100 soft limit).

```javascript
// Correct
if ( condition ) {
	doSomething();
}

// Space after ! negation
if ( ! condition ) {

// No trailing whitespace
```

### Semicolons

Always use semicolons. Never rely on Automatic Semicolon Insertion:

```javascript
// Correct
var banner = getBanner();

// Incorrect
var banner = getBanner()
```

### Strings

Use single quotes:

```javascript
// Correct
var message = 'Banner loaded';

// Incorrect
var message = "Banner loaded";
```

### Equality

Always use strict equality:

```javascript
// Correct
if ( value === 'active' ) {
if ( count !== 0 ) {

// Incorrect
if ( value == 'active' ) {
if ( count != 0 ) {
```

### Arrays and Objects

Inline for short declarations, multi-line with trailing commas for longer:

```javascript
// Short
var arr = [ 1, 2, 3 ];
var obj = { ready: true, count: 4 };

// Long
var config = {
	enabled: true,
	maxBanners: 10,
	rotation: 'random',
};
```

### Control Structures

Always use braces for multi-line blocks:

```javascript
if ( condition ) {
	doSomething();
} else {
	doSomethingElse();
}
```

### Comments

```javascript
// Single line comment with space after slashes.

/**
 * Multi-line JSDoc comment.
 *
 * @param {string} id Banner ID.
 * @return {Object} Banner data.
 */
```

---

## CSS Coding Standards

### Naming Conventions

Use lowercase with hyphens:

```css
/* Correct */
.banner-container { }
#banner-stats-panel { }

/* Incorrect */
.bannerContainer { }
.banner_container { }
```

### Structure

```css
/* Two blank lines between sections */


/* Section Header
---------------------------------------- */

.selector-one,
.selector-two {
	property: value;
	property: value;
}

.another-selector {
	property: value;
}
```

### Selectors

Each selector on its own line:

```css
/* Correct */
.selector-one,
.selector-two,
.selector-three {
	property: value;
}

/* Incorrect */
.selector-one, .selector-two, .selector-three {
	property: value;
}
```

Use double quotes for attribute selectors:

```css
input[type="text"] { }
```

### Properties

- Colon followed by space
- All values lowercase (except font names)
- Semicolon after every declaration

```css
.banner {
	display: block;
	margin: 0 auto;
	font-family: "Helvetica Neue", Arial, sans-serif;
}
```

### Values

| Rule | Correct | Incorrect |
|------|---------|-----------|
| Hex colors (short) | `#fff` | `#FFFFFF` |
| Zero values | `0` | `0px` |
| Font weights | `400`, `700` | `normal`, `bold` |
| Line-height | `1.5` | `1.5em` |
| Leading zeros | `0.5` | `.5` |

### Property Order

Group by type:
1. Display/Positioning
2. Box model (margin, padding, width, height)
3. Colors/Typography
4. Other

```css
.banner {
	/* Display */
	display: flex;
	position: relative;

	/* Box model */
	margin: 0;
	padding: 20px;
	width: 100%;

	/* Typography */
	font-size: 16px;
	color: #333;

	/* Other */
	cursor: pointer;
}
```

### Media Queries

Group at the bottom of the stylesheet:

```css
@media screen and (max-width: 768px) {
	.banner {
		padding: 10px;
	}
}
```

---

## Documentation Standards

### PHP DocBlocks

Use third-person singular verbs ("Gets", "Displays", not "Get", "Display"):

```php
/**
 * Gets banner statistics for the specified date range.
 *
 * Retrieves aggregated impression and click data from the database
 * for the given banner and date range.
 *
 * @since 1.0.0
 *
 * @param int    $banner_id  Banner ID.
 * @param string $start_date Start date in Y-m-d format.
 * @param string $end_date   Optional. End date in Y-m-d format.
 *                           Default current date.
 * @return array {
 *     Statistics data.
 *
 *     @type int $impressions Total impressions.
 *     @type int $clicks      Total clicks.
 *     @type float $ctr       Click-through rate.
 * }
 */
function get_banner_stats( $banner_id, $start_date, $end_date = '' ) {
```

### Class Documentation

```php
/**
 * Manages banner rendering and rotation.
 *
 * Handles the display of banners based on placement configuration,
 * rotation strategy, and device detection.
 *
 * @since 1.0.0
 */
class Banner_Renderer {

	/**
	 * Current placement slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $placement;
```

### Hook Documentation

```php
/**
 * Fires before a banner is rendered.
 *
 * @since 1.0.0
 *
 * @param int    $banner_id    Banner ID.
 * @param string $placement    Placement slug.
 * @param array  $banner_data  Banner configuration data.
 */
do_action( 'simple_banners_before_render', $banner_id, $placement, $banner_data );

/**
 * Filters the banner HTML output.
 *
 * @since 1.0.0
 *
 * @param string $html         Banner HTML.
 * @param int    $banner_id    Banner ID.
 * @param array  $banner_data  Banner configuration.
 * @return string Modified banner HTML.
 */
$html = apply_filters( 'simple_banners_html', $html, $banner_id, $banner_data );
```

### Version Tags

Always use three-digit versioning:

```php
@since 1.0.0
@deprecated 1.2.0 Use new_function() instead.
```

---

## Automated Tools

### PHP_CodeSniffer with WordPress Standards

Install:

```bash
composer require --dev wp-coding-standards/wpcs
composer require --dev phpcsstandards/phpcsutils
```

Configure `phpcs.xml`:

```xml
<?xml version="1.0"?>
<ruleset name="Simple Add Banners">
    <description>Coding standards for Simple Add Banners plugin</description>

    <file>.</file>
    <exclude-pattern>/vendor/*</exclude-pattern>
    <exclude-pattern>/node_modules/*</exclude-pattern>

    <rule ref="WordPress"/>
    <rule ref="WordPress-Extra"/>
    <rule ref="WordPress-Docs"/>

    <config name="text_domain" value="simple-add-banners"/>
    <config name="minimum_wp_version" value="6.0"/>
</ruleset>
```

Run:

```bash
./vendor/bin/phpcs
./vendor/bin/phpcbf  # Auto-fix
```

### ESLint for JavaScript

Install:

```bash
npm install --save-dev @wordpress/eslint-plugin
```

Create `.eslintrc.js`:

```javascript
module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
};
```

### Stylelint for CSS

Install:

```bash
npm install --save-dev @wordpress/stylelint-config
```

Create `.stylelintrc.json`:

```json
{
	"extends": "@wordpress/stylelint-config"
}
```

---

## References

- [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [PHP Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/)
- [WordPress Coding Standards (GitHub)](https://github.com/WordPress/WordPress-Coding-Standards)
