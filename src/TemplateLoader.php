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
	class TemplateLoader extends FileLoader {

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
			return $this->load( $slug, $name );
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
			if ($load) {
				return $this->load( $slug, $name );
			}

			return $this->get( $slug, $name );
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
			$located = $this->locate( $template_names );
			if ( $located & $load ) {
				\load_template( $located, $require_once );
			}
			return $located;
		}
	}
}
