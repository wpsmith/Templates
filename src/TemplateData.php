<?php
/**
 * Template Data Class File.
 *
 * Assists in holding data for templates. Designed to be used with Template_Loader
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\WP
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2019 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      1.0.0
 */

namespace WPS\WP\Templates;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\TemplateData' ) ) {
	/**
	 * Class TemplateData.
	 *
	 * Usage:
	 *  Initialization.
	 *      $data = WPS\Templates\TemplateData::get_instance();
	 *
	 *      // Create the template (optional when using update method)
	 *      // This is how the data will be fetched.
	 *      $data->add_template( 'order' );
	 *
	 *      // Add some data.
	 *      // This will NOT update `is_private` each time.
	 *      $data->add( 'order', 'is_private', $is_private );
	 *
	 *      // Update/Add some data.
	 *      // This will update `post` each time.
	 *      $data->update( 'order', 'post', $post );
	 *
	 *  Within Template.
	 *      // Get the whole data object for the order template.
	 *      $data = \WPS\Templates\TemplateData::get_instance()->get('order');
	 *
	 *      // Access the data directly.
	 *      $post = $data['is_private'];
	 *      $post = $data['post'];
	 *
	 * @package WPS\WP
	 */
	class TemplateData extends Singleton {
		/**
		 * Holds the data.
		 *
		 * @var array
		 */
		protected $data = array();

		/**
		 * TemplateData constructor.
		 */
		protected function __construct() {

			global $wps_data;
			if ( ! $wps_data ) {
				$this->init_data();
			}

		}

		/**
		 * Initializes Data.
		 */
		protected function init_data() {

			global $wps_data;
			$wps_data = $this;

		}

		/**
		 * Determines whether the key exists within template in the data object.
		 *
		 * @param string $template Template slug.
		 * @param string $key      Key slug.
		 *
		 * @return bool
		 */
		public function key_exists( $template, $key ) {

			return ( $this->template_exists( $template ) && isset( $this->data[ $template ][ $key ] ) );

		}

		/**
		 * Determines whether a particular template exists in the data.
		 *
		 * @param string $template Template slug.
		 *
		 * @return bool
		 */
		public function template_exists( $template ) {

			return isset( $this->data[ sanitize_title_with_dashes( $template, 'save' ) ] );

		}

		/**
		 * Determines whether a particular key exists within a template.
		 *
		 * @param string $template Template slug.
		 * @param string $key      Key slug.
		 *
		 * @return bool
		 */
		public function exists( $template, $key ) {

			return $this->key_exists( sanitize_title_with_dashes( $template, 'save' ), sanitize_title_with_dashes( $key, 'save' ) );

		}

		/**
		 * Adds a template to the data; if template exists, will return an error.
		 *
		 * @param string $template Template slug.
		 *
		 * @return \WP_Error|bool
		 */
		public function add_template( $template, $data = array() ) {

			if ( $this->template_exists( $template ) ) {
				return new \WP_Error( 'template-exists', __( 'Template already exists.', 'wps' ) );
			}

			$this->data[ sanitize_title_with_dashes( $template, 'save' ) ] = $data;

			return true;

		}

		/**
		 * Adds data to a template by key; if template exists, will return an error.
		 *
		 * This method will not override existing data.
		 *
		 * @param string $template Template slug.
		 * @param string $key      Key slug.
		 * @param mixed  $data     Mixed data.
		 *
		 * @return \WP_Error|bool
		 */
		public function add( $template, $key, $data ) {

			if ( $this->key_exists( $template, $key ) ) {
				return new \WP_Error( 'template-key-exists', __( 'Template and/or key already exists.', 'wps' ) );
			}

			return $this->_update( $template, $key, $data );

		}

		/**
		 * Worker function that does the updating overriding any data.
		 *
		 * @access private
		 *
		 * @param string $template Template slug.
		 * @param string $key      Key slug.
		 * @param mixed  $data     Mixed data.
		 *
		 * @return bool Whether the data was updated appropriately.
		 */
		protected function _update( $template, $key, $data ) {

			if ( ! isset( $this->data[ $template ] ) ) {

				if ( ! $key ) {

					$this->data[ sanitize_title_with_dashes( $template, 'save' ) ] = array();

					return true;

				} else {

					$this->data[ sanitize_title_with_dashes( $template, 'save' ) ] = array(
						sanitize_title_with_dashes( $key, 'save' ) => $data,
					);

					return true;

				}

			} else {

				$this->data[ sanitize_title_with_dashes( $template, 'save' ) ][ sanitize_title_with_dashes( $key, 'save' ) ] = $data;

				return true;

			}

			return false;

		}

		/**
		 * Ensures the template exists and updates the data overriding any data.
		 *
		 * @param string $template Template slug.
		 * @param string $key      Key slug.
		 * @param mixed  $data     Mixed data.
		 *
		 * @return bool Whether the data was updated appropriately.
		 */
		public function update( $template, $key, $data ) {

			if ( ! $this->template_exists( $template ) ) {
				$this->add_template( $template );
			}

			return $this->_update( $template, $key, $data );

		}

		/**
		 * @param string $template Template slug.
		 * @param string $key      Key slug.
		 * @param mixed  $fallback Fallback value
		 *
		 * @return mixed
		 */
		public function get( $template, $key = '', $fallback = '' ) {

			if ( '' === $key && $this->template_exists( $template ) ) {

				return $this->data[ sanitize_title_with_dashes( $template, 'save' ) ];

			}

			if ( $this->key_exists( $template, $key ) ) {

				return $this->data[ sanitize_title_with_dashes( $template, 'save' ) ][ sanitize_title_with_dashes( $key, 'save' ) ];

			}

			return $fallback;
		}
	}
}
