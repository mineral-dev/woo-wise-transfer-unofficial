<?php
/**
 * Wise Transfer Details Card — reusable partial.
 *
 * Shared between the standalone view-order template and the shortcode/fallback renderer.
 *
 * Expected variables:
 *   $order          WC_Order  — the order object
 *   $account_email  string
 *   $account_name   string
 *   $bank_name      string
 *   $account_number string
 *   $currency       string
 *   $swift_code     string
 *   $receipt_url    string
 *
 * @package Woo_Wise_Transfer
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wise-card">
	<?php if ( 'completed' === $order->get_status() ) : ?>
	<div class="wise-paid-watermark">PAID</div>
	<?php endif; ?>
	<div class="wise-card-header">
		<img src="<?php echo esc_url( WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/images/wise-logo.svg' ); ?>" alt="Wise" class="wise-card-logo">
		<h3 class="wise-card-title"><?php esc_html_e( 'Transfer Details', 'woo-wise-transfer' ); ?></h3>
		<p class="wise-card-subtitle"><?php esc_html_e( 'Please transfer the order amount to the account below.', 'woo-wise-transfer' ); ?></p>
	</div>

	<table class="wise-details-table">
		<?php if ( $account_email ) : ?>
		<tr class="wise-details-row">
			<td class="wise-details-label"><?php esc_html_e( 'Email address', 'woo-wise-transfer' ); ?></td>
			<td class="wise-details-value">
				<span class="wise-details-value-with-copy">
					<?php echo esc_html( $account_email ); ?>
					<button type="button" class="wise-copy-btn" data-copy="<?php echo esc_attr( $account_email ); ?>" title="<?php esc_attr_e( 'Copy', 'woo-wise-transfer' ); ?>">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
					</button>
				</span>
			</td>
		</tr>
		<?php endif; ?>

		<?php if ( $account_name ) : ?>
		<tr class="wise-details-row">
			<td class="wise-details-label"><?php esc_html_e( 'Account holder', 'woo-wise-transfer' ); ?></td>
			<td class="wise-details-value"><?php echo esc_html( $account_name ); ?></td>
		</tr>
		<?php endif; ?>

		<?php if ( $bank_name ) : ?>
		<tr class="wise-details-row">
			<td class="wise-details-label"><?php esc_html_e( 'Bank name', 'woo-wise-transfer' ); ?></td>
			<td class="wise-details-value"><?php echo esc_html( $bank_name ); ?></td>
		</tr>
		<?php endif; ?>

		<?php if ( $account_number ) : ?>
		<tr class="wise-details-row">
			<td class="wise-details-label"><?php esc_html_e( 'Account number', 'woo-wise-transfer' ); ?></td>
			<td class="wise-details-value">
				<span class="wise-details-value-with-copy">
					<?php echo esc_html( $account_number ); ?>
					<button type="button" class="wise-copy-btn" data-copy="<?php echo esc_attr( $account_number ); ?>" title="<?php esc_attr_e( 'Copy', 'woo-wise-transfer' ); ?>">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
					</button>
				</span>
			</td>
		</tr>
		<?php endif; ?>

		<?php if ( $currency ) : ?>
		<tr class="wise-details-row">
			<td class="wise-details-label"><?php esc_html_e( 'Currency', 'woo-wise-transfer' ); ?></td>
			<td class="wise-details-value"><?php echo esc_html( $currency ); ?></td>
		</tr>
		<?php endif; ?>

		<?php if ( $swift_code ) : ?>
		<tr class="wise-details-row">
			<td class="wise-details-label"><?php esc_html_e( 'SWIFT code', 'woo-wise-transfer' ); ?></td>
			<td class="wise-details-value">
				<span class="wise-details-value-with-copy">
					<?php echo esc_html( $swift_code ); ?>
					<button type="button" class="wise-copy-btn" data-copy="<?php echo esc_attr( $swift_code ); ?>" title="<?php esc_attr_e( 'Copy', 'woo-wise-transfer' ); ?>">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
					</button>
				</span>
			</td>
		</tr>
		<?php endif; ?>

		<tr class="wise-details-row">
			<td class="wise-details-label"><?php esc_html_e( 'Amount', 'woo-wise-transfer' ); ?></td>
			<td class="wise-details-value"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
		</tr>
	</table>

	<?php if ( $receipt_url ) : ?>
	<?php
		$receipt_filename = $order->get_meta( '_wise_receipt_filename' );
		$uploaded_at      = $order->get_meta( '_wise_receipt_uploaded_at' );
		$is_image         = preg_match( '/\.(jpe?g|png)$/i', $receipt_filename );
	?>
	<div class="wise-nudge" style="margin: 24px 32px;">
		<?php if ( $is_image ) : ?>
		<img class="wise-nudge-thumb" src="<?php echo esc_url( $receipt_url ); ?>" alt="">
		<?php else : ?>
		<span class="wise-nudge-icon">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
		</span>
		<?php endif; ?>
		<div class="wise-nudge-body">
			<p class="wise-nudge-title"><?php echo esc_html( $receipt_filename ); ?></p>
			<?php if ( $uploaded_at ) : ?>
			<p class="wise-nudge-subtitle"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $uploaded_at ) ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
</div>
