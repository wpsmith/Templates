<?php
/**
 * Template Loader Class File.
 *
 * Assist in loading templates via plugins.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\WP
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2020 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/Templates
 * @version    0.1.0
 * @since      0.0.1
 */

namespace WPS\WP\Templates;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\TemplateLoader' ) ) {
	/**
	 * Template loader.
	 *
	 * Based on Gamajo's Gamajo_Template_Loader.
	 * Originally based on functions in Easy Digital Downloads (thanks Pippin!).
	 *
	 * When using in a plugin, create a new class that extends this one and just overrides the properties.
	 *
	 * @package WPS\WP
	 */
	class TemplateLoader {

		/**
		 * Prefix for filter names.
		 *
		 * An _ will be added at the end,
		 *
		 * @example 'plugin_prefix'.
		 * @var string
		 */
		protected $prefix = 'yourplugin';

		/**
		 * Directory name where custom templates for this plugin should be found in the theme.
		 *
		 * @example 'plugin-templates'.
		 * @var string
		 */
		protected $theme_template_directory = 'templates';

		/**
		 * Reference to the root directory path of this plugin.
		 *
		 * Can either be a defined constant, or a relative reference from where the subclass lives.
		 *
		 * @example YOUR_PLUGIN_TEMPLATE or plugin_dir_path( dirname( __FILE__ ) ); etc.
		 * @var string
		 */
		protected $plugin_directory = 'YOUR_PLUGIN_DIR';

		/**
		 * Reference to the template directory path of this plugin.
		 *
		 * Can either be a defined constant, or a relative reference from where the subclass lives.
		 *
		 * @example 'templates' or 'includes/templates', etc.
		 * @var string
		 */
		protected $templates_directory = 'templates';

		/**
		 * Internal use only: Store located template paths.
		 *
		 * @var array
		 */
		private $template_path_cache = [];

		/**
		 * Internal use only: Store variable names used for template data.
		 *
		 * Means unset_template_data() can remove all custom references from $wp_query.
		 *
		 * Initialized to contain the default 'yourplugin_data'.
		 *
		 * @var array
		 */
		private $template_data_var_names = [];

		/**
		 * TemplateLoader constructor.
		 *
		 * @param array $args Loader Args.
		 */
		public function __construct( $args = [] ) {

			if ( ! empty( $args ) ) {

				// Backwards compatibility.
				if ( isset( $args['filter_prefix'] ) ) {
					$args['prefix'] = $args['filter_prefix'];
					unset( $args['filter_prefix'] );
				}

				$defaults = [
					'prefix'                   => 'yourplugin',
					'theme_template_directory' => 'templates',
					'plugin_directory'         => 'plugin-templates',
					'templates_directory'      => 'templates',
				];

				$args = wp_parse_args( $args, $defaults );

				foreach ( $args as $var => $val ) {
					$this->{$var} = $val;
				}
			}

		}

		/**
		 * Clean up template data.
		 */
		public function __destruct() {
			$this->unset_template_data();
		}

		/**
		 * Make custom data available to template.
		 *
		 * Data is available to the template as properties under the `${PREFIX}_data` variable.
		 * i.e. A value provided here under `${PREFIX}_data['foo']` is available as `${PREFIX}_data->foo`.
		 *
		 * When an input key has a hyphen, you can use `${PREFIX}_data->{foo-bar}` in the template.
		 *
		 * @param mixed $data Custom data for the template.
		 * @param string $var_name Optional. Variable under which the custom data is available in the template.
		 *                         Default is '{PREFIX}_data'.
		 *
		 * @return self
		 */
		public function set_template_data( $data, $var_name = '' ) {
			global $wp_query;

			// Default to 'yourplugin_data'.
			$var_name = '' === $var_name ? $this->get_prefix() . '_data' : $var_name;

			// Set data to query vars.
			$wp_query->query_vars[ $var_name ] = (object) $data;

			// Add $var_name to custom variable store if not default value.
			if ( $this->get_prefix() . '_data' !== $var_name ) {
				$this->template_data_var_names[] = $var_name;
			}

			return $this;
		}

		/**
		 * Remove access to custom data in template.
		 *
		 * Good to use once the final template part has been requested.
		 */
		public function unset_template_data() {
			global $wp_query;

			// Remove any duplicates from the custom variable store.
			$custom_var_names = array_unique( $this->template_data_var_names );

			// Remove each custom data reference from $wp_query.
			foreach ( $custom_var_names as $var ) {
				if ( isset( $wp_query->query_vars[ $var ] ) ) {
					unset( $wp_query->query_vars[ $var ] );
				}
			}

			return $this;
		}

		/**
		 * Loads a template part.
		 *
		 * @param string $slug Template slug.
		 * @param string $name Optional. Default null.
		 *
		 * @return string
		 *
		 */
		public function load_template_part( $slug, $name = null ) {

			return $this->get_template_part( $slug, $name, true );

		}

		/**
		 * Retrieve a template part.
		 *
		 * @param string $slug Template slug.
		 * @param string $name Optional. Default null.
		 * @param bool $load Optional. Default false.
		 *
		 * @return string
		 * @uses  self::get_template_file_names() Create file names of templates.
		 * @uses  self::locate_template() Retrieve the name of the highest priority template file that exists.
		 *
		 */
		public function get_template_part( $slug, $name = null, $load = false ) {
			// Execute code for this part.
			do_action( 'get_template_part_' . $slug, $slug, $name );
			do_action( $this->get_prefix() . '_get_template_part_' . $slug, $slug, $name );

			// Get files names of templates, for given slug and name.
			$templates = $this->get_template_file_names( $slug, $name );

			// Return the part that is found.
			return $this->locate_template( $templates, $load, false );
		}

		/**
		 * Given a slug and optional name, create the file names of templates.
		 *
		 * Templates will be:
		 *  - $slug-$name.php
		 *  - $name.php
		 *  - $slug.php
		 *
		 * @param string $slug Template slug.
		 * @param string $name Template name.
		 *
		 * @return array
		 */
		protected function get_template_file_names( $slug, $name ) {
			$templates = [];
			if ( isset( $name ) ) {
				$templates[] = $slug . '-' . $name . '.php';
			}
			$templates[] = $name . '.php';
			$templates[] = $slug . '.php';

			/**
			 * Allow template choices to be filtered.
			 *
			 * The resulting array should be in the order of most specific first, to least specific last.
			 * e.g. 0 => recipe-instructions.php, 1 => recipe.php
			 *
			 * @param array $templates Names of template files that should be looked for, for given slug and name.
			 * @param string $slug Template slug.
			 * @param string $name Template name.
			 *
			 * @since 0.0.1
			 */
			return apply_filters( $this->get_prefix() . '_get_template_part', $templates, $slug, $name );
		}

		/**
		 * Retrieve the name of the highest priority template file that exists.
		 *
		 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
		 * inherit from a parent theme can just overload one file. If the template is
		 * not found in either of those, it looks in the theme-compat folder last.
		 *
		 * @param string|array $template_names Template file(s) to search for, in order.
		 * @param bool $load If true the template file will be loaded if it is found.
		 * @param bool $require_once Whether to require_once or require. Default true.
		 *                                     Has no effect if $load is false.
		 *
		 * @return string The template filename if one is located.
		 * @uses  self::get_template_paths() Return a list of paths to check for template locations.
		 *
		 */
		public function locate_template( $template_names, $load = false, $require_once = true ) {

			// Use $template_names as a cache key - either first element of array or the variable itself if it's a string.
			$cache_key = is_array( $template_names ) ? $template_names[0] : $template_names;

			// If the key is in the cache array, we've already located this file.
			if ( isset( $this->template_path_cache[ $cache_key ] ) ) {
				$located = $this->template_path_cache[ $cache_key ];
			} else {

				// No file found yet.
				$located = false;

				// Remove empty entries.
				$template_names = array_filter( (array) $template_names );
				$template_paths = $this->get_template_paths();

				// Try to find a template file.
				foreach ( $template_names as $template_name ) {
					// Trim off any slashes from the template name & replace any spaces with dashes.
					$template_name = str_replace( ' ', '-', ltrim( $template_name, '/' ) );

					// Try locating this template file by looping through the template paths.
					foreach ( $template_paths as $template_path ) {
						if ( file_exists( $template_path . $template_name ) && is_file( $template_path . $template_name ) ) {
							$located = $template_path . $template_name;

							// Store the template path in the cache.
							$this->template_path_cache[ $cache_key ] = $located;
							break 2;
						}
					}
				}

			}

			if ( $load && $located ) {
				load_template( $located, $require_once );
			}

			return $located;
		}

		/**
		 * Return a list of paths to check for template locations.
		 *
		 * Default is to check in a child theme (if relevant) before a parent theme, so that themes which inherit from a
		 * parent theme can just overload one file. If the template is not found in either of those, it looks in the
		 * theme-compat folder last.
		 *
		 * @return mixed
		 */
		protected function get_template_paths() {
			$theme_directory = trailingslashit( $this->theme_template_directory );

			$file_paths = [
				10  => trailingslashit( get_template_directory() ) . $theme_directory,
				100 => $this->get_templates_dir(),
			];

			// Only add this conditionally, so non-child themes don't redundantly check active theme twice.
			if ( $this->is_child_theme() ) {
				$file_paths[1] = trailingslashit( get_stylesheet_directory() ) . $theme_directory;
			}

			/**
			 * Allow ordered list of template paths to be amended.
			 *
			 * @param array $var Default is directory in child theme at index 1, parent theme at 10, and plugin at 100.
			 *
			 * @since 0.0.1
			 *
			 */
			$file_paths = apply_filters( $this->get_prefix() . '_template_paths', $file_paths );

			// sort the file paths based on priority.
			ksort( $file_paths, SORT_NUMERIC );

			return array_map( 'trailingslashit', $file_paths );
		}

		/**
		 * Whether a child theme is in use.
		 *
		 * @return bool true if a child theme is in use, false otherwise.
		 **/
		protected function is_child_theme() {

			return ( get_template_directory() !== get_stylesheet_directory() );

		}

		/**
		 * Return the path to the templates directory in this plugin.
		 *
		 * @return string
		 */
		protected function get_templates_dir() {

			return trailingslashit( $this->plugin_directory ) . $this->templates_directory;

		}

		/**
		 * Gets the sanitized prefix.
		 *
		 * @return string
		 */
		private function get_prefix() {
			return str_replace( '-', '_', $this->prefix );
		}

	}
}
