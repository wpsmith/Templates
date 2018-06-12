<?php
/**
 * Simple Sidebars Support Class File
 *
 * Assist in adding support for simple sidebars for post type archives.
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
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\Templates;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS\Templates\Simple_Sidebars' ) ) {
	/**
	 * Class Simple_Sidebars
	 * @package WPS\Templates
	 */
	class Simple_Sidebars {

		/**
		 * Post Type
		 *
		 * @var string
		 */
		public $post_type;

		/**
		 * Simple_Sidebars constructor.
		 *
		 * @param string $post_type Post Type.
		 */
		public function __construct( $post_type ) {
			$this->post_type = $post_type;

			add_action( 'widgets_init', array( $this, 'widgets_init' ) );
			add_action( 'genesis_header', array( $this, 'add_sidebars' ) );
			add_filter( 'sidebars_widgets', array( $this, 'sidebars_widgets_filter' ) );
		}

		/**
		 * Whether the theme supports 3 column layouts.
		 *
		 * @return bool
		 */
		private function has_3_column_layout() {

			$layouts = genesis_get_layouts();

			$three_column_layouts = array(
				'content-sidebar-sidebar',
				'sidebar-content-sidebar',
				'sidebar-sidebar-content',
			);

			foreach ( $three_column_layouts as $layout ) {
				if ( array_key_exists( $layout, $layouts ) ) {
					return true;
				}
			}

			return false;

		}

		/**
		 * Adds sidebars.
		 */
		public function add_sidebars() {
			global $wp_query;

			// For some reason, this doesn't work, `if ( is_singular( $this->post_type ) || is_post_type_archive( $this->post_type ) )`.
			if ( $wp_query->is_singular( $this->post_type ) || $wp_query->is_post_type_archive( $this->post_type ) ) {
				remove_action( 'genesis_sidebar', 'ss_do_sidebar' );
				add_action( 'genesis_sidebar', array( $this, 'primary_sidebar' ) );

				if ( $this->has_3_column_layout() ) {
					remove_action( 'genesis_sidebar_alt', 'ss_do_sidebar_alt' );
					add_action( 'genesis_sidebar_alt', array( $this, 'secondary_sidebar' ) );
				}
			}
		}

		/**
		 * Filter the widgets in each widget area.
		 *
		 * @param array $widgets Array of widgets.
		 *
		 * @return mixed
		 */
		public function sidebars_widgets_filter( $widgets ) {

			$sidebars = array();

			if ( ! is_front_page() && ( is_singular( $this->post_type ) || is_post_type_archive( $this->post_type ) ) ) {

				$sidebars = array(
					'sidebar'     => $this->post_type . '-primary',
					'sidebar-alt' => $this->post_type . '-primary-secondary',
				);

			}

			$widgets = $this->swap_widgets( $widgets, $sidebars );

			return $widgets;

		}

		/**
		 * Take the $widgets array and swap the contents of each widget area with a custom widget area, if specified.
		 *
		 * @param array $widgets Array of widgets.
		 * @param array $sidebars Array of sidebars.
		 *
		 * @return mixed
		 */
		private function swap_widgets( $widgets, $sidebars ) {

			if ( is_admin() ) {
				return $widgets;
			}

			foreach ( (array) $sidebars as $old_sidebar => $new_sidebar ) {

				if ( ! is_registered_sidebar( $old_sidebar ) ) {
					continue;
				}

				if ( $new_sidebar && ! empty( $widgets[ $new_sidebar ] ) ) {
					$widgets[ $old_sidebar ] = $widgets[ $new_sidebar ];
				} else {
					$widgets[ $new_sidebar ] = $widgets[ $old_sidebar ];
				}
			}

			return $widgets;

		}

		/**
		 * Does the sidebar.
		 *
		 * @param string $id Sidebar ID.
		 */
		private function do_sidebar( $id ) {
			if ( function_exists( 'genesis_widget_area' ) ) {
				genesis_widget_area( $this->post_type . '-' . $id );
			} else {
				dynamic_sidebar( $this->post_type . '-' . $id );
			}
		}

		/**
		 * Does the primary sidebar.
		 */
		public function primary_sidebar() {
			$this->do_sidebar( 'primary' );
		}

		/**
		 * Does the secondary sidebar.
		 */
		public function secondary_sidebar() {
			$this->do_sidebar( 'secondary' );
		}

		/**
		 * Register our sidebars and widgetized areas.
		 */
		public function widgets_init() {
			$post_type = get_post_type_object( $this->post_type );

			if ( function_exists( 'genesis_register_widget_area' ) ) {
				genesis_register_widget_area( array(
					'name'        => __( $post_type->label . ' Widget Area', WPS_TEXT_DOMAIN ),
					'id'          => $this->post_type . '-primary',
					'description' => sprintf( '%s %s.', __( 'This is the primary sidebar for', WPS_TEXT_DOMAIN ), $post_type->label ),
				) );

				if ( $this->has_3_column_layout() ) {
					genesis_register_widget_area( array(
						'name'        => __( $post_type->label . ' Widget Area', WPS_TEXT_DOMAIN ),
						'id'          => $this->post_type . '-secondary',
						'description' => sprintf( '%s %s.', __( 'This is the primary sidebar for', WPS_TEXT_DOMAIN ), $post_type->label ),
					) );
				}
			}
		}
	}
}