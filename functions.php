<?php
/**
 * StudyChurch functions and definitions
 *
 * When using a child theme (see http://codex.wordpress.org/Theme_Development and
 * http://codex.wordpress.org/Child_Themes), you can override certain functions
 * (those wrapped in a function_exists() call) by defining them first in your child theme's
 * functions.php file. The child theme's functions.php file is included before the parent
 * theme's file, so the child theme functions would be used.
 *
 * @package StudyChurch
 * @since 0.1.0
 */

// Useful global constants
define( 'SC_VERSION', '0.1.5.1' );
define( 'BP_DEFAULT_COMPONENT', 'profile' );

require_once( get_template_directory() . '/vendor/autoload.php' );

/**
 *
 * @since  1.0.0
 *
 * @return \StudyChurch\Setup
 * @author Tanner Moushey
 */
function studychurch() {
	return StudyChurch\Setup::get_instance();
}

studychurch();
