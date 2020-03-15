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

if ( ! class_exists( __NAMESPACE__ . '\Template_Loader' ) ) {
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
	class Template_Loader extends TemplateLoader {}
}
