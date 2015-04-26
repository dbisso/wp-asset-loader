# WP Asset Loader Library

Abstraction for the WordPress enqueuing system. 

### What?

Allows you to specify assets in a static manifest file instead using the wp_enqueue_* function. 

### Why?

To avoid cluttering the my theme code with what amounts to a list of dependencies.

### How?

Add a YAML file somewhere (probably in your theme directory). Something like this:

```yaml
scripts:
    skip-link-focus:
        # src is relative to the theme path at the moment.
        src: skip-link-focus-fix.js
    modernizr:
        src: vendor/modernizr.js
        version: 2.8.3
    a-jquery-plugin:
        src: vendor/jquery.my-plugin.js
        # Specify dependencies
        deps:
            - jquery
    # Pre-registered & built-in assets can be added too.
    comment-reply:
      registered: true
styles:
    theme-styles:
        src: style.css
    oldie:
        src: oldie.css
        # scripts and styles can both specify extra data
        data:
            # Adds conditional comments around the link element
            conditional: lt IE 9
```

Then in your theme:

```php
use DBisso\Service\AssetLoader\AssetLoader;

add_action( 'wp_enqueue_scripts', function() {
  // Create the loader, specifying the path to the assets manifest
  $asset_loader = new AssetLoader( get_stylesheet_directory() . '/assets.yml' );
  
  // Optionally add a condition check to prevent loading an asset on certain pages.
  $asset_loader->add_script_condition( 'comment-reply', function( $script ) {
    return is_singular() && comments_open() && get_option( 'thread_comments' );
  });
  
  // Loads the assets
  $asset_loader->load();
} );

```
