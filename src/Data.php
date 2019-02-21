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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\Data' ) ) {
	/**
	 * Class Data.
	 *
	 * @package WPS\WP
	 */
	class Data {
		/**
		 * Holds the data.
		 *
		 * @var array
		 */
		protected $_data = array();

		/**
		 * TemplateData constructor.
		 */
		public function __construct( array $data = array() ) {

			$this->_data = $data;
		}

		public function __set( $name, $value ) {
			$this->_data[ $name ] = $value;
		}

		public function set( $name, $key, $value ) {
			if ( isset( $this->_data[ $name ] ) ){
				$this->_data[ $name ][ $key ] = $value;
			} else {
				$this->_data[ $name ] = array(
					$key => $value,
				);
			}
		}

		public function __get( $name ) {

			if ( isset( $this->_data[ $name ] ) ) {
				return $this->_data[ $name ];
			}

			return new \WP_Error( 'key-dns', __( 'Key does not exist', 'wps' ) );

		}

		public function get( $name, $key = '' ) {

			// If no $key, return just the $name value.
			if ( isset( $this->_data[ $name ] ) && ! $key ) {
				return $this->_data[ $name ];
			}

			// If $name and $key, return $key value.
			if ( isset( $this->_data[ $name ] ) && isset( $this->_data[ $name ][ $key ] ) ) {
				return $this->_data[ $name ][ $key ];
			}

			return new \WP_Error( 'key-name-dns', __( 'Key and/or name do/does not exist', 'wps' ) );

		}
	}
}
