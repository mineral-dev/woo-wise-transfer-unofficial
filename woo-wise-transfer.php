<?php
/**
 * Plugin Name: Wise Transfer (unofficial)
 * Plugin URI: https://mineral.co.id
 * Description: Unofficial WooCommerce payment gateway for Wise Transfer
 * Version: 0.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * Author: Mineral
 * Author URI: https://mineral.co.id
 * Text Domain: woo-wise-transfer
 * Domain Path: /languages
 * Update URI: https://mineral.co.id
 */

defined( 'ABSPATH' ) || exit;

define( 'WOO_WISE_TRANSFER_VERSION', '0.0.1' );
define( 'WOO_WISE_TRANSFER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOO_WISE_TRANSFER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the plugin after WooCommerce is loaded.
 */
function woo_wise_transfer_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woo_wise_transfer_missing_wc_notice' );
		return;
	}
	require_once WOO_WISE_TRANSFER_PLUGIN_DIR . 'includes/class-woo-wise-transfer-gateway.php';
	add_filter( 'woocommerce_payment_gateways', 'woo_wise_transfer_add_gateway' );

	// Register AJAX handlers here so they work even if WC doesn't instantiate gateways during AJAX.
	add_action( 'wp_ajax_wise_upload_receipt', 'woo_wise_transfer_ajax_upload_receipt' );
	add_action( 'wp_ajax_nopriv_wise_upload_receipt', 'woo_wise_transfer_ajax_upload_receipt' );
}
add_action( 'plugins_loaded', 'woo_wise_transfer_init' );

/**
 * AJAX receipt upload handler — thin wrapper that delegates to the gateway instance.
 */
function woo_wise_transfer_ajax_upload_receipt() {
	$gateways = WC()->payment_gateways()->payment_gateways();
	if ( isset( $gateways['wise_transfer'] ) ) {
		$gateways['wise_transfer']->ajax_upload_receipt();
	} else {
		// Fallback: instantiate directly.
		$gateway = new Woo_Wise_Transfer_Gateway();
		$gateway->ajax_upload_receipt();
	}
}

/**
 * Admin notice when WooCommerce is not active.
 */
function woo_wise_transfer_missing_wc_notice() {
	echo '<div class="error"><p><strong>' .
		esc_html__( 'Wise Transfer (unofficial) requires WooCommerce to be installed and active.', 'woo-wise-transfer' ) .
		'</strong></p></div>';
}

/**
 * Register the gateway with WooCommerce.
 *
 * @param array $gateways Existing gateways.
 * @return array
 */
function woo_wise_transfer_add_gateway( $gateways ) {
	$gateways[] = 'Woo_Wise_Transfer_Gateway';
	return $gateways;
}

/**
 * Load plugin text domain.
 */
function woo_wise_transfer_load_textdomain() {
	load_plugin_textdomain( 'woo-wise-transfer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'woo_wise_transfer_load_textdomain' );

/**
 * Create the receipts upload directory on activation.
 */
function woo_wise_transfer_activate() {
	$upload_dir = wp_upload_dir();
	$receipts_dir = $upload_dir['basedir'] . '/wise-receipts';

	if ( ! file_exists( $receipts_dir ) ) {
		wp_mkdir_p( $receipts_dir );
		// Protect directory with .htaccess
		file_put_contents( $receipts_dir . '/.htaccess', 'Options -Indexes' . PHP_EOL );
	}
}
register_activation_hook( __FILE__, 'woo_wise_transfer_activate' );

/**
 * Add Settings link on the Plugins page.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function woo_wise_transfer_plugin_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wise_transfer' ) ) . '">' .
		esc_html__( 'Settings', 'woo-wise-transfer' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woo_wise_transfer_plugin_action_links' );

/**
 * Add plugin row meta links (developer contact).
 *
 * @param array  $links Plugin meta links.
 * @param string $file  Plugin file.
 * @return array
 */
function woo_wise_transfer_plugin_row_meta( $links, $file ) {
	if ( plugin_basename( __FILE__ ) === $file ) {
		$links[] = '<a href="mailto:andy@mineral.co.id">' . esc_html__( 'Contact Developer', 'woo-wise-transfer' ) . '</a>';
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'woo_wise_transfer_plugin_row_meta', 10, 2 );
