<?php
/**
 * Wise Transfer Details Card — reusable partial.
 *
 * Pure account details card. Does NOT include upload form or receipt nudge.
 *
 * Expected variables:
 *   $order          WC_Order  — the order object
 *   $account_email  string
 *   $account_name   string
 *   $bank_name      string
 *   $account_number string
 *   $currency       string
 *   $swift_code     string
 *
 * Optional:
 *   $card_subtitle  string  — override subtitle text
 *
 * @package Woo_Wise_Transfer
 */

defined( 'ABSPATH' ) || exit;

$card_subtitle = isset( $card_subtitle ) ? $card_subtitle : __( 'Please transfer the order amount to the account below.', 'woo-wise-transfer' );
?>

<div class="wise-card">
	<?php if ( 'completed' === $order->get_status() ) : ?>
	<div class="wise-paid-watermark">PAID</div>
	<?php endif; ?>
	<div class="wise-card-header">
		<img src="<?php echo esc_url( WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/images/wise-logo.svg' ); ?>" alt="Wise" class="wise-card-logo">
		<h3 class="wise-card-title"><?php esc_html_e( 'Transfer Details', 'woo-wise-transfer' ); ?></h3>
		<p class="wise-card-subtitle"><?php echo esc_html( $card_subtitle ); ?></p>
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
</div>
