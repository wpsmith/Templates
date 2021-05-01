<?php
/**
 * Config Loader Class File.
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

if ( ! class_exists( __NAMESPACE__ . '\ConfigLoader' ) ) {
	/**
	 * Config loader.
	 *
	 * When using in a plugin, create a new class that extends this one and just overrides the properties.
	 *
	 * @package WPS\WP
	 */
	class ConfigLoader extends FileLoader {

		/**
		 * Directory name where custom files for this plugin should be found in the theme.
		 *
		 * @example 'plugin-templates'.
		 * @var string
		 */
		protected $theme_file_directory = 'config';

		/**
		 * Reference to the template directory path of this plugin.
		 *
		 * Can either be a defined constant, or a relative reference from where the subclass lives.
		 *
		 * @example 'templates' or 'includes/templates', etc.
		 * @var string
		 */
		protected $files_directory = 'config';

		/**
		 * Loads a template part.
		 *
		 * @param string $slug Template slug.
		 * @param string $name Optional. Default null.
		 *
		 * @return array
		 */
		public function load( string $slug, $name = null ): array {
			$files = $this->get_filenames( $slug, $name );

			$primary_file = $this->locate( $files );
			$default_file = $this->locate( $files, [ $this->get_dir() ] );

			$data = [];

			if ( is_readable( $primary_file ) ) {
				$data = require $primary_file;
			}

			if ( empty( $data ) && is_readable( $default_file ) ) {
				$data = require $default_file;
			}

			return (array) $data;
		}

	}
}
