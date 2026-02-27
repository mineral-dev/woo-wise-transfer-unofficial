<?php
/**
 * Plugin Name: Wise Transfer (unofficial)
 * Plugin URI: https://mineral.co.id
 * Description: Unofficial WooCommerce payment gateway for Wise Transfer
 * Version: 1.0.0
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

define( 'WOO_WISE_TRANSFER_VERSION', '1.0.0' );
define( 'WOO_WISE_TRANSFER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOO_WISE_TRANSFER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Flush rewrite rules on version upgrade.
 */
function woo_wise_transfer_check_version() {
	$saved_version = get_option( 'wise_version', '0' );
	if ( version_compare( WOO_WISE_TRANSFER_VERSION, $saved_version, '>' ) ) {
		update_option( 'wise_version', WOO_WISE_TRANSFER_VERSION );
		flush_rewrite_rules();
	}
}
add_action( 'admin_init', 'woo_wise_transfer_check_version' );

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

	// Add "Transfer Details" action button on My Account → Orders list.
	add_filter( 'woocommerce_my_account_my_orders_actions', 'woo_wise_transfer_orders_actions', 10, 2 );

	// Add transfer details link to WooCommerce customer emails.
	add_action( 'woocommerce_email_before_order_table', 'woo_wise_transfer_email_link', 10, 4 );

	// Suppress default WooCommerce admin emails for Wise Transfer orders.
	add_filter( 'woocommerce_email_enabled_new_order', 'woo_wise_transfer_suppress_admin_email', 10, 2 );
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
 * Add a "Transfer Details" action button on the My Account → Orders list for wise_transfer orders.
 *
 * @param array    $actions Existing order actions.
 * @param WC_Order $order   Order object.
 * @return array
 */
function woo_wise_transfer_orders_actions( $actions, $order ) {
	if ( 'wise_transfer' !== $order->get_payment_method() ) {
		return $actions;
	}

	$actions['wise_transfer_details'] = array(
		'url'  => home_url( '/wise-transfer-details/' . $order->get_id() . '/?key=' . $order->get_order_key() ),
		'name' => __( 'Transfer Details', 'woo-wise-transfer' ),
	);

	return $actions;
}

/**
 * Add a "View Transfer Details" link to WooCommerce customer emails for wise_transfer orders.
 *
 * @param WC_Order $order         Order object.
 * @param bool     $sent_to_admin Whether email is sent to admin.
 * @param bool     $plain_text    Whether email is plain text.
 * @param WC_Email $email         Email object.
 */
function woo_wise_transfer_email_link( $order, $sent_to_admin, $plain_text, $email = null ) {
	if ( $sent_to_admin ) {
		return;
	}

	if ( 'wise_transfer' !== $order->get_payment_method() ) {
		return;
	}

	$url = home_url( '/wise-transfer-details/' . $order->get_id() . '/?key=' . $order->get_order_key() );

	if ( $plain_text ) {
		echo "\n" . esc_html__( 'View your transfer details and upload proof of payment:', 'woo-wise-transfer' ) . "\n" . esc_url( $url ) . "\n\n";
	} else {
		$font = "font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;";
		// echo '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0 24px;"><tr><td>';
		echo '<a href="' . esc_url( $url ) . '" style="display:inline-block;background:#163300;color:#9FE870;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;' . $font . '">';
		echo esc_html__( 'View Transfer Details & Upload Receipt', 'woo-wise-transfer' );
		echo '</a>';
		// echo '</td></tr></table>';
	}
}

/**
 * Suppress default WooCommerce admin "New Order" email for Wise Transfer orders.
 *
 * The plugin sends its own custom notification email to the admin,
 * so the default WooCommerce email is redundant.
 *
 * @param bool     $enabled Whether the email is enabled.
 * @param WC_Order $order   Order object (may be null).
 * @return bool
 */
function woo_wise_transfer_suppress_admin_email( $enabled, $order ) {
	if ( $order instanceof WC_Order && 'wise_transfer' === $order->get_payment_method() ) {
		return false;
	}
	return $enabled;
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
 * Register custom order status: Proof Uploaded.
 */
function woo_wise_transfer_register_order_status() {
	register_post_status( 'wc-wise-uploaded', array(
		'label'                     => _x( 'Proof Uploaded', 'Order status', 'woo-wise-transfer' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		/* translators: %s: number of orders */
		'label_count'               => _n_noop( 'Proof Uploaded <span class="count">(%s)</span>', 'Proof Uploaded <span class="count">(%s)</span>', 'woo-wise-transfer' ),
	) );
}
add_action( 'init', 'woo_wise_transfer_register_order_status' );

/**
 * Add the custom status to WooCommerce order status list.
 *
 * @param array $statuses Existing statuses.
 * @return array
 */
function woo_wise_transfer_add_order_status( $statuses ) {
	$statuses['wc-wise-uploaded'] = _x( 'Proof Uploaded', 'Order status', 'woo-wise-transfer' );
	return $statuses;
}
add_filter( 'wc_order_statuses', 'woo_wise_transfer_add_order_status' );

/**
 * Style the custom status badge in the admin order list.
 */
function woo_wise_transfer_order_status_style() {
	echo '<style>
		.order-status.status-wise-uploaded {
			background: #D6E4F0;
			color: #1A3E5C;
		}
	</style>';
}
add_action( 'admin_head', 'woo_wise_transfer_order_status_style' );

/**
 * Register rewrite rule for native /wise-transfer-details/ page.
 */
function woo_wise_transfer_add_rewrite_rule() {
	add_rewrite_rule( '^wise-transfer-details/(\d+)/?$', 'index.php?wise_order_view=1&wise_order_id=$matches[1]', 'top' );
}
add_action( 'init', 'woo_wise_transfer_add_rewrite_rule' );

/**
 * Register query vars.
 */
function woo_wise_transfer_register_query_vars( $vars ) {
	$vars[] = 'wise_order_view';
	$vars[] = 'wise_order_id';
	return $vars;
}
add_filter( 'query_vars', 'woo_wise_transfer_register_query_vars' );

/**
 * Handle native page request.
 */
function woo_wise_transfer_native_page() {
	global $wp_query;

	$order_id = $wp_query->get( 'wise_order_id' );

	// Check if this is our custom URL
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	if ( preg_match( '#^/wise-transfer-details/(\d+)/?(\?.*)?$#', $request_uri, $matches ) ) {
		$order_id = absint( $matches[1] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'woo-wise-transfer' ) );
		}

		// Only allow viewing Wise Transfer orders
		if ( 'wise_transfer' !== $order->get_payment_method() ) {
			wp_die( esc_html__( 'This order was not paid via Wise Transfer.', 'woo-wise-transfer' ) );
		}

		// Check permissions
		$current_user_id = get_current_user_id();
		$order_user_id   = $order->get_user_id();
		$order_key       = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$is_admin        = current_user_can( 'manage_woocommerce' );
		$is_owner        = $current_user_id && $current_user_id === $order_user_id;
		$has_valid_key   = $order_key && $order->get_order_key() === $order_key;

		if ( ! $is_owner && ! $is_admin && ! $has_valid_key ) {
			wp_die( esc_html__( 'You do not have permission to view this order.', 'woo-wise-transfer' ) );
		}

		// Enqueue styles and scripts (register with full URL since gateway enqueue may not have fired).
		wp_enqueue_style( 'woo-wise-transfer-checkout', WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/css/checkout.css', array(), WOO_WISE_TRANSFER_VERSION );
		wp_enqueue_script( 'woo-wise-transfer-copy-button', WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/js/copy-button.js', array(), WOO_WISE_TRANSFER_VERSION, true );

		// Load template file
		$template_path = WOO_WISE_TRANSFER_PLUGIN_DIR . 'templates/view-order.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback inline render
			header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
			echo '<!DOCTYPE html><html ';
			echo language_attributes();
			echo '><head>';
			echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
			echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
			echo '<title>' . sprintf( esc_html__( 'Order #%s Details', 'woo-wise-transfer' ), $order->get_id() ) . '</title>';
			wp_head();
			echo '</head><body>';
			echo woo_wise_transfer_render_view_order_page_html( $order );
			wp_footer();
			echo '</body></html>';
		}
		exit;
	}
}
add_action( 'template_redirect', 'woo_wise_transfer_native_page', 5 );

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

	// Flush rewrite rules for view-order endpoint
	flush_rewrite_rules();
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

/**
 * Flush rewrite rules on activation.
 */
function woo_wise_transfer_activate_rewrite() {
	woo_wise_transfer_add_rewrite_rule();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'woo_wise_transfer_activate_rewrite' );

/**
 * Shortcode: [wise_order_details order_id="123"]
 * Usage: Create a page and add this shortcode with the order ID
 * Or use [wise_order_details] to display current user's order by ID from URL parameter ?order_id=123
 * Optional: ?key=wc_order_xxx for guest access
 */
function woo_wise_transfer_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'order_id' => '',
	), $atts, 'wise_order_details' );

	$order_id = ! empty( $atts['order_id'] ) ? absint( $atts['order_id'] ) : 0;

	// Also check for order_id in URL query string
	if ( ! $order_id && isset( $_GET['order_id'] ) ) {
		$order_id = absint( $_GET['order_id'] );
	}

	if ( ! $order_id ) {
		return '<p class="wise-error">' . esc_html__( 'Order ID is required.', 'woo-wise-transfer' ) . '</p>';
	}

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return '<p class="wise-error">' . esc_html__( 'Order not found.', 'woo-wise-transfer' ) . '</p>';
	}

	// Only allow viewing Wise Transfer orders
	if ( 'wise_transfer' !== $order->get_payment_method() ) {
		return '<p class="wise-error">' . esc_html__( 'This order was not paid via Wise Transfer.', 'woo-wise-transfer' ) . '</p>';
	}

	// Check permissions: logged-in user owns order, OR admin, OR has valid order key (for guests)
	$current_user_id = get_current_user_id();
	$order_user_id   = $order->get_user_id();
	$order_key       = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
	$is_admin        = current_user_can( 'manage_woocommerce' );
	$is_owner        = $current_user_id && $current_user_id === $order_user_id;
	$has_valid_key  = $order_key && $order->get_order_key() === $order_key;

	if ( ! $is_owner && ! $is_admin && ! $has_valid_key ) {
		return '<p class="wise-error">' . esc_html__( 'You do not have permission to view this order.', 'woo-wise-transfer' ) . '</p>';
	}

	// Enqueue styles (register with full URL since gateway enqueue may not have fired).
	wp_enqueue_style( 'woo-wise-transfer-checkout', WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/css/checkout.css', array(), WOO_WISE_TRANSFER_VERSION );

	return woo_wise_transfer_render_view_order_page_html( $order );
}
add_shortcode( 'wise_order_details', 'woo_wise_transfer_shortcode' );

/**
 * Render the view order page HTML (returns string for shortcode).
 *
 * @param WC_Order $order Order object.
 * @return string
 */
function woo_wise_transfer_render_view_order_page_html( $order ) {
	// Get gateway settings for the shared card partial.
	$gateway          = new Woo_Wise_Transfer_Gateway();
	$account_email    = $gateway->get_option( 'account_email' );
	$account_name     = $gateway->get_option( 'account_name' );
	$bank_name        = $gateway->get_option( 'bank_name' );
	$account_number   = $gateway->get_option( 'account_number' );
	$currency         = $gateway->get_option( 'currency' );
	$swift_code       = $gateway->get_option( 'swift_code' );
	$receipt_url      = $order->get_meta( '_wise_receipt_url' );

	wp_enqueue_script( 'woo-wise-transfer-copy-button', WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/js/copy-button.js', array(), WOO_WISE_TRANSFER_VERSION, true );

	ob_start();
	?>
	<div class="wise-view-order-wrapper">
		<div class="wise-view-order-container">
			<div class="wise-view-order-header">
				<h1><?php printf( esc_html__( 'Order #%s', 'woo-wise-transfer' ), $order->get_id() ); ?></h1>
				<p><?php echo esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></p>
			</div>

			<?php include WOO_WISE_TRANSFER_PLUGIN_DIR . 'templates/partials/transfer-details-card.php'; ?>

			<a href="<?php echo esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ); ?>" class="wise-back-link"><?php esc_html_e( '← Back to My Account', 'woo-wise-transfer' ); ?></a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
