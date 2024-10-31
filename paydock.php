<?php

/**
 * Copyright (c) Paydock, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * Plugin Name: Paydock for WooCommerce
 * Plugin URI: https://github.com/PayDock/e-commerce-woo
 * Description: Paydock simplify how you manage your payments. Reduce costs, technical headaches & streamline compliance using Paydock's payment orchestration.
 * Author: Paydock
 * Author URI: https://www.paydock.com/
 * Version: 3.0.5
 * Requires at least: 6.4.2
 * Text Domain: paydock
 * Tested up to: 6.4.2
 * WC requires at least: 6.4.2
 * WC tested up to: 8.5
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'PAYDOCK_PLUGIN_FILE' ) ) {
	define( 'PAYDOCK_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'PAYDOCK_PLUGIN_PATH' ) ) {
	define( 'PAYDOCK_PLUGIN_PATH', dirname( __FILE__ ) );
}

if ( ! defined( 'PAYDOCK_PLUGIN_URL' ) ) {
	define( 'PAYDOCK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'PAYDOCK_PLUGIN_VERSION' ) ) {
	define( 'PAYDOCK_PLUGIN_VERSION', '3.0.5' );
}

require_once 'vendor/autoload.php';

Paydock\PaydockPlugin::getInstance();
