# Template Loader

[![Code Climate](https://codeclimate.com/github/wpsmith/Templates/badges/gpa.svg)](https://codeclimate.com/github/wpsmith/Templates)

A class to copy into your WordPress plugin, to allow loading template parts with fallback through the child theme > parent theme > plugin.

This is largely based on Gary Jones's [Gamajo_Template_Loader](https://github.com/GaryJones/Gamajo-Template-Loader/). The main difference between Gary's version and this one is twofold:
1. I didn't want to extend the class for each and every plugin I created...call me lazy.
1. I didn't want to load the template parts automatically.

## Description

Easy Digital Downloads, WooCommerce, and Events Calendar plugins, amongst others, allow you to add files to your theme to override the default templates that come with the plugin. As a developer, adding this convenience in to your own plugin can be a little tricky.

The `get_template_part()` function in WordPress was never really designed with plugins in mind, since it relies on `locate_template()` which only checks child and parent themes. So we can add in a final fallback that uses the templates in the plugin, we have to use a custom `locate_template()` function, and a custom `get_template_part()` function. The solution here just wraps them up as a class for convenience.

## Installation

This isn't a WordPress plugin on its own, so the usual instructions don't apply. Instead:

### Manually install class
1. Copy [`Templates/src/TemplateLoader.php`](TemplateLoader.php) for basic usage

or:

1. Copy [`Templates/src/TemplateLoaderData.php`](TemplateLoaderData.php) and `Templates/src/TemplateLoader.php`](TemplateLoader.php) into your plugin. It can be into a file in the plugin root, or better, an `includes` directory.

or:

### Install class via Composer
1. Tell Composer to install this class as a dependency: `composer require wpsmith/templates`
2. Recommended: Install the Mozart package: `composer require coenjacobs/mozart --dev` and [configure it](https://github.com/coenjacobs/mozart#configuration).
3. The class is now renamed to use your own prefix, to prevent collisions with other plugins bundling this class.

## Implementation & Usage
Unlike Gamajo's [Gamajo_Template_Loader](https://github.com/GaryJones/Gamajo-Template-Loader#implement-class), you do not have to implement a new class.

Assuming...
~~~php
// Set at the root folder of your plugin (e.g., wp-content/plugins/yourplugin/yourplugin.php).
// Defines YOUR_PLUGIN_DIRNAME as yourplugin.
define( 'YOUR_PLUGIN_DIRNAME', dirname( __FILE__ ) );
~~~

### Initialization

We can initialize `TemplateLoader`:
 
~~~php
// Create the loader with my prefix and directory.
// This assumes:
//  - plugin templates are found in the plugin folder: wp-content/plugins/yourplugin/templates/...
//  - theme templates are found in the plugin folder: wp-content/themes/yourtheme/templates/...
$template_loader = new TemplateLoader( [
    'prefix'           => 'wps',
    'plugin_directory' => YOUR_PLUGIN_DIRNAME,
] );
~~~

Or,
~~~php
// Create the loader with all the parts.
// This declares:
//  - plugin templates are found in the plugin folder: wp-content/plugins/yourplugin/templates/...
//  - theme templates are found in the plugin folder: wp-content/themes/yourtheme/templates/yourplugin/...
$template_loader = new TemplateLoader( [
    'prefix'                   => 'wps',
    'theme_template_directory' => 'templates/yourplugin',
    'templates_directory'      => 'templates',
    'plugin_directory'         => WPS_PLUGIN_DIRNAME,
] );
~~~

You could utilize a template loader helper which will always return the same template loader:
~~~php
/**
 * Gets the plugin template loader.
 *
 * @return \WPS\WP\Templates\TemplateLoader
 */
function yourprefix_get_template_loader() {
	static $loader;
	if ( $loader === null ) {
		$loader = new \WPS\WP\Templates\TemplateLoader( [
			'prefix'                   => 'wps',
            'theme_template_directory' => 'templates/yourplugin',
            'templates_directory'      => 'templates',
            'plugin_directory'         => WPS_PLUGIN_DIRNAME,
		) );
	}

	return $loader;
}
~~~

Finally, you can use it for configuration purposes too.

~~~php
/**
 * Gets a configuration file as a data array.
 *
 * @return array
 */
function yourprefix_get_config( $config ) {
    static $loader;
    
    if ( $loader === null ) {
        $loader = new TemplateLoader( [
            'filter_prefix'            => 'wps',
            'theme_template_directory' => 'config',
            'templates_directory'      => 'config',
            'plugin_directory'         => WPS_PLUGIN_DIRNAME,
        ] );
    }

    $template = $loader->get_template_part( 'config', $config );

    $data = array();
    if ( is_readable( $template ) ) {
        $data = require $template;
    }

    return (array) $data;
}
~~~


### Usage

* Use it to call the `load_template_part()` method. This could be within a shortcode callback, or something you want theme developers to include in their files.

    ~~~php
    $template_loader->load_template_part( 'recipe' );
    ~~~

* Use it to call the `get_template_part()` method. This could be within a shortcode callback, or something you want theme developers to include in their files.

    ~~~php
    // This will return the path to the particular template part.
    $template_loader->get_template_part( 'recipe' );
    
    // This will load the particular template part.
    $template_loader->get_template_part( 'recipe', null, true );
    ~~~

* If you want to pass data to the template, call the `set_template_data()` method with an array before calling `get_template_part()`. `set_template_data()` returns the loader object to allow for method chaining.

    ~~~php
    $data = [ 'foo' => 'bar', 'baz' => 'boom' ];
    $template_loader
      ->set_template_data( $data );
      ->get_template_part( 'recipe' );
    ~~~
  
    The value of `bar` is now available inside the recipe template as `$wps_data->foo`.
    
    If you wish to use a different variable name, add a second parameter to `set_template_data()`:
    
    ~~~php
    $data = array( 'foo' => 'bar', 'baz' => 'boom' );
    $meal_planner_template_loader
      ->set_template_data( $data, 'context' )
      ->get_template_part( 'recipe', 'ingredients' );
    ~~~
    
    The value of `bar` is now available inside the recipe template as `$context->foo`.
    
    This will try to load up:
    - Theme Templates:
       - `wp-content/themes/my-theme/meal-planner/recipe-ingredients.php`
       - `wp-content/themes/my-theme/meal-planner/ingredients.php`
       - `wp-content/themes/my-theme/meal-planner/recipe.php`
    - Plugin Templates:
       - `wp-content/plugins/meal-planner/templates/recipe-ingredients.php`
       - `wp-content/plugins/meal-planner/templates/ingredients.php`.
       - `wp-content/plugins/meal-planner/templates/recipe.php`.



## Change Log

See the [change log](CHANGELOG.md).

## License

[GPL 2.0 or later](LICENSE).

## Contributions

Contributions are welcome - fork, fix and send pull requests against the `develop` branch please.

## Credits

Based upon [Gary Jones's](https://garyjones.io) [Gamajo_Template_Loader](https://github.com/GaryJones/Gamajo-Template-Loader/)
Built by [Travis Smith](https://twitter.com/wp_smith)  
Copyright 2013-2020 [Travis Smith](https://wpsmith.net)