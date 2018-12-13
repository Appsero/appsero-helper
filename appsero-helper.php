<?php
/**
 * Plugin Name: AppSero Helper
 * Plugin URI: https://wedevs.com
 * Description: Helper plugin to connect WP marketplace to AppSero
 * Author: Tareq Hasan
 * Author URI: https://tareq.co
 * Version: 1.0.0
 *
 * AppSero Helper is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * AppSero Helper is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AppSero Helper. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Define necessary constant
 */
if( ! defined( 'ASHP_ROOT_PATH' ) ){
    define('ASHP_ROOT_PATH', plugin_dir_path( __FILE__ ));
}

/**
 * Load all classes
 */
require_once ASHP_ROOT_PATH . 'classes/load.php';
