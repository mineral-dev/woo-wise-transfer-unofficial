<?php
/**
 * Wise Transfer Order Details Template
 *
 * Standalone page rendered at /wise-transfer-details/{order_id}/?key={order_key}
 * Shows transfer details card + receipt upload form.
 *
 * Expected variable: $order (WC_Order)
 *
 * @package Woo_Wise_Transfer
 */

defined( 'ABSPATH' ) || exit;

// Get gateway settings for the shared card partial.
$gateway          = new Woo_Wise_Transfer_Gateway();
$account_email    = $gateway->get_option( 'account_email' );
$account_name     = $gateway->get_option( 'account_name' );
$bank_name        = $gateway->get_option( 'bank_name' );
$account_number   = $gateway->get_option( 'account_number' );
$currency         = $gateway->get_option( 'currency' );
$swift_code       = $gateway->get_option( 'swift_code' );
$receipt_url      = $order->get_meta( '_wise_receipt_url' );
$card_subtitle    = __( 'Please transfer the order amount to the account below, then upload your proof of payment.', 'woo-wise-transfer' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php printf( esc_html__( 'Order #%s Details', 'woo-wise-transfer' ), $order->get_id() ); ?></title>
	<?php wp_head(); ?>
</head>
<body>
	<div class="wise-view-order-wrapper wise-view-order-wrapper--standalone">
		<div class="wise-view-order-container">
			<div class="wise-view-order-header">
				<h1><?php printf( esc_html__( 'Order #%s', 'woo-wise-transfer' ), $order->get_id() ); ?></h1>
			</div>

			<?php include WOO_WISE_TRANSFER_PLUGIN_DIR . 'templates/partials/transfer-details-card.php'; ?>
			<?php include WOO_WISE_TRANSFER_PLUGIN_DIR . 'templates/partials/receipt-upload-form.php'; ?>

			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wise-back-link"><?php esc_html_e( '← Back to Home', 'woo-wise-transfer' ); ?></a>
		</div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
