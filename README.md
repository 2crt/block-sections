## Block Sections

This package provides a structured foundation and toolkit for efficiently creating hierarchical ACF blocks that work together seamlessly to build sections conforming to a specific design.

**When to use this package**

0. When your front-end needs to pass "pixel-perfect" visual quality assurance and satisfy the discerning eye of an OCD designer.
1. For implementing Gutenberg-based content management with true WYSIWYG functionality, adhering to precise visual layouts or existing HTML structures, while maintaining ACF flexible content-level control over HTML.
2. To offer a streamlined page-building experience that aligns perfectly with your design specifications.
3. To deliberately limit options for content editors, embracing the "decisions, not options" philosophy.

**When NOT to use this package**

0. If you have flexibility in terms of design or HTML structure(or you don't have to achive "pixel-perfect" match between figma and WordPress front-end), core Gutenberg blocks (supplemented with a generic toolset of static blocks) will likely better suit your needs.
1. For non-nested sections, this package is unnecessary. Instead, refer to this simpler approach: https://www.billerickson.net/innerblocks-with-acf-blocks/.

**Usage instructions**

1. Install via Composer:
   ```
   composer require 2crt/block-sections
   ```

2. Add to your theme's `composer.json`:
   ```json
   {
     "autoload": {
       "psr-4": {
         "Sections\\": "sections/"
       }
     }
   }
   ```
   ... and run `composer install` to setup auto-loading

3. Ensure Composer autoload is required (e.g., in `functions.php`):
   ```php
   require __DIR__ . '/vendor/autoload.php';
   ```

4. Generate a section using the custom WP-CLI command:
   ```
   wp make:section HeroCallToAction
   ```

Follow the CLI prompts. The generated boilerplate serves as a foundation for developing your custom sections.
