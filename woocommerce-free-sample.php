<?php
/*
Plugin Name: WooCommerce Free Sample For Orders Plugin
Plugin URI:  https://github.com/kgoedecke/woocommerce-free-sample
Description: Allows you to add a free sample (freebie) for orders with more than X items and specify conditions for adding a free item to orders.
Version:     1.0.0
Author:      HaveALook UG
Author URI:  https://havealooklabs.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: woocommerce-free-sample
Domain Path: /languages
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	add_action( 'admin_notices', 'woo_free_sample_plugin_required_fail' );
	return;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-woocommerce-free-sample-plugin.php' );
add_action( 'plugins_loaded', array( 'WooCommerce_Free_Sample_Plugin', 'get_instance' ) );

/**
 * Show a notice about the WooCommerce plugin is not activated.
 *
 * @since 1.0.0
 */
function woo_free_sample_plugin_required_fail() {
	$message = sprintf( __( 'plugin requires a <a href="%s" target="_blank">WooCommerce</a> plugin.', 'woocommerce-free-sample' ), esc_url( 'https://wordpress.org/plugins/woocommerce/' ) );

	$html_message = sprintf( '<div class="error"><p><strong>%1$s</strong> %2$s</p></div>', esc_html__( 'WooCommerce Free Sample For Orders', 'woocommerce-free-sample' ), $message );

	echo wp_kses_post( $html_message );
}
