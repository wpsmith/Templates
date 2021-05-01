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

if ( ! class_exists( __NAMESPACE__ . '\FileLoader' ) ) {
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
	class FileLoader {

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
		 * Directory name where custom files for this plugin should be found in the theme.
		 *
		 * @example 'plugin-templates'.
		 * @var string
		 */
		protected $theme_file_directory = 'templates';

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
		protected $files_directory = 'templates';

		/**
		 * Internal use only: Store located template paths.
		 *
		 * @var array
		 */
		protected $filepath_cache = [];

		/**
		 * FileLoader constructor.
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
					'prefix'               => $this->prefix,
					'theme_file_directory' => $this->theme_file_directory,
					'plugin_directory'     => $this->plugin_directory,
					'files_directory'      => $this->files_directory,
				];

				$args = \wp_parse_args( $args, $defaults );

				foreach ( $args as $var => $val ) {
					$this->{$var} = $val;
				}
			}

		}

		/**
		 * Clean up.
		 */
		public function __destruct() {
		}

		/**
		 * Loads a file.
		 *
		 * @param string $slug File slug.
		 * @param null $name Optional. Default null.
		 *
		 * @return string|array
		 */
		public function load( string $slug, $name = null ) {

			$located = $this->get( $slug, $name );
			if ( $located ) {
				\load_template( $located, false );
			}

			return $located;

		}

		/**
		 * Retrieve a file.
		 *
		 * @param string $slug File slug.
		 * @param null $name Optional. Default null.
		 *
		 * @return string
		 * @uses  self::get_filenames() Create file names of files.
		 * @uses  self::locate() Retrieve the name of the highest priority file that exists.
		 */
		public function get( string $slug, $name = null ) {
			// Execute code for this part.
			\do_action( 'get_part_' . $slug, $slug, $name );
			\do_action( $this->get_prefix() . '_get_part_' . $slug, $slug, $name );

			// Get files names of files, for given slug and name.
			$files = $this->get_filenames( $slug, $name );

			// Return the part that is found.
			return $this->locate( $files );
		}

		/**
		 * Given a slug and optional name, create the file names of files.
		 *
		 * Files will be:
		 *  - $slug-$name.php
		 *  - $name.php
		 *  - $slug.php
		 *
		 * @param string $slug File slug.
		 * @param string|null $name File name.
		 *
		 * @return array
		 */
		protected function get_filenames( string $slug, $name = null ): array {
			$files = [];
			if ( isset( $name ) ) {
				$files[] = $slug . '-' . $name . '.php';
				$files[] = $name . '.php';
			}
			$files[] = $slug . '.php';

			/**
			 * Allow file choices to be filtered.
			 *
			 * The resulting array should be in the order of most specific first, to least specific last.
			 * e.g. 0 => recipe-instructions.php, 1 => recipe.php
			 *
			 * @param array $files Names of files that should be looked for, for given slug and name.
			 * @param string $slug File slug.
			 * @param string $name File name.
			 *
			 * @since 0.0.1
			 */
			return \apply_filters( $this->get_prefix() . '_get_part', $files, $slug, $name );
		}

		/**
		 * Retrieve the name of the highest priority file that exists.
		 *
		 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
		 * inherit from a parent theme can just overload one file. If the file is
		 * not found in either of those, it looks in the theme-compat folder last.
		 *
		 * @param string|array $filenames File(s) to search for, in order.
		 * @param array $paths Paths to search in.
		 *
		 * @return string The filename if one is located.
		 * @uses  self::get_paths() Return a list of paths to check for file locations.
		 *
		 */
		public function locate( $filenames, array $paths = [] ) {

			$paths = ! empty( $paths ) ? $paths : $this->get_paths();

			// Use $filenames as a cache key - either first element of array or the variable itself if it's a string.
			$cache_key = is_array( $filenames ) ? md5( serialize( [ $filenames, $paths ] ) ) : $filenames;

			// If the key is in the cache array, we've already located this file.
			if ( isset( $this->filepath_cache[ $cache_key ] ) ) {
				$located = $this->filepath_cache[ $cache_key ];
			} else {

				// No file found yet.
				$located = false;

				// Remove empty entries.
				$filenames = array_filter( (array) $filenames );

				// Try to find a file.
				foreach ( $filenames as $filename ) {
					// Trim off any slashes from the filename & replace any spaces with dashes.
					$filename = str_replace( ' ', '-', ltrim( $filename, '/' ) );

					// Try locating this file by looping through the paths.
					foreach ( $paths as $path ) {
						if ( file_exists( $path . $filename ) && is_file( $path . $filename ) ) {
							$located = $path . $filename;

							// Store the filepath in the cache.
							$this->filepath_cache[ $cache_key ] = $located;
							break 2;
						}
					}
				}

			}

			return $located;
		}

		/**
		 * Return a list of paths to check for file locations.
		 *
		 * Default is to check in a child theme (if relevant) before a parent theme, so that themes which inherit from a
		 * parent theme can just overload one file. If the file is not found in either of those, it looks in the
		 * theme-compat folder last.
		 *
		 * @return array
		 */
		protected function get_paths(): array {
			$theme_directory = \trailingslashit( $this->theme_file_directory );

			$file_paths = [
				10  => \trailingslashit( \get_template_directory() ) . $theme_directory,
				100 => $this->get_dir(),
			];

			// Only add this conditionally, so non-child themes don't redundantly check active theme twice.
			if ( $this->is_child_theme() ) {
				$file_paths[1] = \trailingslashit( \get_stylesheet_directory() ) . $theme_directory;
			}

			/**
			 * Allow ordered list of file paths to be amended.
			 *
			 * @param array $var Default is directory in child theme at index 1, parent theme at 10, and plugin at 100.
			 *
			 * @since 0.0.1
			 *
			 */
			$file_paths = \apply_filters( $this->get_prefix() . '_file_paths', $file_paths );

			// sort the file paths based on priority.
			ksort( $file_paths, SORT_NUMERIC );

			return array_map( 'trailingslashit', $file_paths );
		}

		/**
		 * Whether a child theme is in use.
		 *
		 * @return bool true if a child theme is in use, false otherwise.
		 **/
		protected function is_child_theme(): bool {

			return ( \get_template_directory() !== \get_stylesheet_directory() );

		}

		/**
		 * Return the path to the files directory in this plugin.
		 *
		 * @return string
		 */
		protected function get_dir(): string {

			return trailingslashit( $this->plugin_directory ) . $this->files_directory;

		}

		/**
		 * Gets the sanitized prefix.
		 *
		 * @return string
		 */
		protected function get_prefix(): string {
			return str_replace( '-', '_', $this->prefix );
		}
	}
}
