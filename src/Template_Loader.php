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
 * @package    WPS\Templates
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2018 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.2.0
 * @since      0.1.0
 */

namespace WPS\Templates;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS\Templates\Template_Loader' ) ) {
	/**
	 * Template loader.
	 *
	 * Based on Gamajo's Gamajo_Template_Loader.
	 * Originally based on functions in Easy Digital Downloads (thanks Pippin!).
	 *
	 * When using in a plugin, create a new class that extends this one and just overrides the properties.
	 *
	 * @package WPS\Templates
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	class Template_Loader {

		/**
		 * Prefix for filter names.
		 *
		 * An _ will be added at the end,
		 *
		 * @example 'your_plugin'.
		 * @var string
		 */
		protected $filter_prefix = 'yourplugin'; // or.

		/**
		 * Directory name where custom templates for this plugin should be found in the theme.
		 *
		 * @example 'your-plugin-templates'.
		 * @var string
		 */
		protected $theme_template_directory = 'templates'; // or 'your-plugin' or 'your-plugin-templates' etc.

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
		 * Template_Loader constructor.
		 *
		 * @param array $args Loader Args.
		 */
		public function __construct( $args = array() ) {

			if ( ! empty( $args ) ) {

				$defaults = array(
					'filter_prefix'            => 'yourplugin',
					// or 'your_plugin'.
					'theme_template_directory' => 'templates',
					// or 'your-plugin' or 'your-plugin-templates' etc.
					'plugin_directory'         => 'yourplugin',
					// or YOUR_PLUGIN_DIR or plugin_dir_path( dirname( __FILE__ ) ); etc.
					'templates_directory'      => 'templates',
					// or includes/templates, etc.
				);

				$args = wp_parse_args( $args, $defaults );

				foreach ( $args as $var => $val ) {
					$this->{$var} = $val;
				}
			}

			$this->init( $this );

		}

		/**
		 * Initializes loader.
		 *
		 * @param self $obj Template Loader.
		 */
		public function init( $obj ) {

			if ( method_exists( $obj, 'loaded' ) ) {
				if ( ! did_action( 'after_setup_theme' ) ) {
					add_action( 'after_setup_theme', array( $obj, 'loaded' ), 99 );
				} else {
					$this->loaded();
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
		 * Remove access to custom data in template.
		 *
		 * Good to use once the final template part has been requested.
		 */
		public function unset_template_data() {
			global $wp_query;

			if ( isset( $wp_query->query_vars['data'] ) ) {
				unset( $wp_query->query_vars['data'] );
			}
		}

		/**
		 * Retrieve a template part.
		 *
		 * @uses  self::get_template_possble_parts() Create file names of templates.
		 * @uses  self::locate_template() Retrieve the name of the highest priority template file that exists.
		 *
		 * @param string $slug Template slug.
		 * @param string $name Optional. Default null.
		 * @param bool   $load Optional. Default false.
		 *
		 * @return string
		 */
		public function get_template_part( $slug, $name = null, $load = false ) {
			// Execute code for this part.
			do_action( 'get_template_part_' . $slug, $slug, $name );

			// Get files names of templates, for given slug and name.
			$templates = $this->get_template_file_names( $slug, $name );

			// Return the part that is found.
			return $this->locate_template( $templates, $load, false );
		}

		/**
		 * Given a slug and optional name, create the file names of templates.
		 *
		 * @since 1.0.0
		 *
		 * @param string $slug Template slug.
		 * @param string $name Template name.
		 *
		 * @return array
		 */
		protected function get_template_file_names( $slug, $name ) {
			$templates = array();
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
			 * @since 1.0.0
			 *
			 * @param array  $templates Names of template files that should be looked for, for given slug and name.
			 * @param string $slug      Template slug.
			 * @param string $name      Template name.
			 */

			return apply_filters( $this->filter_prefix . '_get_template_part', $templates, $slug, $name );
		}

		/**
		 * Retrieve the name of the highest priority template file that exists.
		 *
		 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
		 * inherit from a parent theme can just overload one file. If the template is
		 * not found in either of those, it looks in the theme-compat folder last.
		 *
		 * @uses  self::get_template_paths() Return a list of paths to check for template locations.
		 *
		 * @param string|array $template_names Template file(s) to search for, in order.
		 * @param bool         $load           If true the template file will be loaded if it is found.
		 * @param bool         $require_once   Whether to require_once or require. Default true.
		 *                                     Has no effect if $load is false.
		 *
		 * @return string The template filename if one is located.
		 */
		public function locate_template( $template_names, $load = false, $require_once = true ) {
			// No file found yet.
			$located = false;

			// Remove empty entries.
			$template_names = array_filter( (array) $template_names );
			$template_paths = $this->get_template_paths();

			// Try to find a template file.
			foreach ( $template_names as $template_name ) {
				// Trim off any slashes from the template name.
				$template_name = str_replace( ' ', '-', ltrim( $template_name, '/' ) );

				// Try locating this template file by looping through the template paths.
				foreach ( $template_paths as $template_path ) {
					if ( file_exists( $template_path . $template_name ) ) {
						$located = $template_path . $template_name;
						break 2;
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

			$file_paths = array(
				10  => trailingslashit( get_template_directory() ) . $theme_directory,
				100 => $this->get_templates_dir(),
			);

			// Only add this conditionally, so non-child themes don't redundantly check active theme twice.
			if ( $this->is_child_theme() ) {
				$file_paths[1] = trailingslashit( get_stylesheet_directory() ) . $theme_directory;
			}

			/**
			 * Allow ordered list of template paths to be amended.
			 *
			 * @since 1.0.0
			 *
			 * @param array $var Default is directory in child theme at index 1, parent theme at 10, and plugin at 100.
			 */
			$file_paths = apply_filters( $this->filter_prefix . '_template_paths', $file_paths );

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
		 * May be overridden in subclass.
		 *
		 * @return string
		 */
		protected function get_templates_dir() {

			return trailingslashit( $this->plugin_directory ) . $this->templates_directory;

		}

	}
}
