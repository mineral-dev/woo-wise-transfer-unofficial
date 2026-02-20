<?php
/**
 * Wise Transfer Payment Gateway.
 *
 * @package Woo_Wise_Transfer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Woo_Wise_Transfer_Gateway class.
 */
class Woo_Wise_Transfer_Gateway extends WC_Payment_Gateway {

	/**
	 * Notification email address.
	 *
	 * @var string
	 */
	private $notification_email;

	/**
	 * Account email.
	 *
	 * @var string
	 */
	private $account_email;

	/**
	 * Account holder name.
	 *
	 * @var string
	 */
	private $account_name;

	/**
	 * Bank name.
	 *
	 * @var string
	 */
	private $bank_name;

	/**
	 * Account number.
	 *
	 * @var string
	 */
	private $account_number;

	/**
	 * Currency.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * SWIFT code.
	 *
	 * @var string
	 */
	private $swift_code;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'wise_transfer';
		$this->icon               = WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/images/wise-logo.svg';
		$this->has_fields         = true;
		$this->method_title       = __( 'Wise Transfer (unofficial)', 'woo-wise-transfer' );
		$this->method_description = __( 'Unofficial WooCommerce payment gateway for Wise Transfer. Customers upload proof of payment after checkout.', 'woo-wise-transfer' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->notification_email = $this->get_option( 'notification_email' );
		$this->account_email      = $this->get_option( 'account_email' );
		$this->account_name       = $this->get_option( 'account_name' );
		$this->bank_name          = $this->get_option( 'bank_name' );
		$this->account_number     = $this->get_option( 'account_number' );
		$this->currency           = $this->get_option( 'currency' );
		$this->swift_code         = $this->get_option( 'swift_code' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_order_details_before_order_table', array( $this, 'thankyou_page_block' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_admin_order_receipt' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers are registered in woo-wise-transfer.php to ensure they run during AJAX requests.
	}

	/**
	 * Admin settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'general_section' => array(
				'title' => __( 'General', 'woo-wise-transfer' ),
				'type'  => 'title',
			),
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woo-wise-transfer' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Wise Transfer', 'woo-wise-transfer' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'woo-wise-transfer' ),
				'type'        => 'text',
				'description' => __( 'Payment method title shown to customers at checkout.', 'woo-wise-transfer' ),
				'default'     => __( 'Wise Transfer', 'woo-wise-transfer' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woo-wise-transfer' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions displayed to the customer during checkout.', 'woo-wise-transfer' ),
				'default'     => __( 'Pay via Wise bank transfer. You will receive bank transfer details after placing your order.', 'woo-wise-transfer' ),
				'desc_tip'    => true,
			),
			'account_section' => array(
				'title' => __( 'Account Information', 'woo-wise-transfer' ),
				'type'  => 'title',
			),
			'account_email' => array(
				'title'       => __( 'Email Address', 'woo-wise-transfer' ),
				'type'        => 'email',
				'description' => __( 'The Wise account email address.', 'woo-wise-transfer' ),
				'desc_tip'    => true,
			),
			'account_name' => array(
				'title'       => __( 'Full Name', 'woo-wise-transfer' ),
				'type'        => 'text',
				'description' => __( 'Full name of the account holder.', 'woo-wise-transfer' ),
				'desc_tip'    => true,
			),
			'bank_name' => array(
				'title'       => __( 'Bank Name', 'woo-wise-transfer' ),
				'type'        => 'text',
				'description' => __( 'The bank name associated with the Wise account.', 'woo-wise-transfer' ),
				'desc_tip'    => true,
			),
			'account_number' => array(
				'title'       => __( 'Account Number', 'woo-wise-transfer' ),
				'type'        => 'text',
				'description' => __( 'The bank account number.', 'woo-wise-transfer' ),
				'desc_tip'    => true,
			),
			'currency' => array(
				'title'       => __( 'Currency', 'woo-wise-transfer' ),
				'type'        => 'text',
				'description' => __( 'The currency for the account (e.g. USD, EUR, GBP).', 'woo-wise-transfer' ),
				'default'     => 'USD',
				'desc_tip'    => true,
			),
			'swift_code' => array(
				'title'       => __( 'SWIFT Code', 'woo-wise-transfer' ),
				'type'        => 'text',
				'description' => __( 'The SWIFT/BIC code for international transfers.', 'woo-wise-transfer' ),
				'desc_tip'    => true,
			),
			'notification_section' => array(
				'title' => __( 'Notifications', 'woo-wise-transfer' ),
				'type'  => 'title',
			),
			'notification_email' => array(
				'title'       => __( 'Notification Email', 'woo-wise-transfer' ),
				'type'        => 'email',
				'description' => __( 'Email address to receive payment confirmation notifications.', 'woo-wise-transfer' ),
				'default'     => get_option( 'admin_email' ),
				'desc_tip'    => true,
			),
			'email_preview' => array(
				'title' => __( 'Email Previews', 'woo-wise-transfer' ),
				'type'  => 'email_preview',
			),
		);
	}

	/**
	 * Custom admin options output.
	 * Also handles email preview rendering when ?wise_preview= is set.
	 */
	public function admin_options() {
		// Handle email preview requests.
		if ( ! empty( $_GET['wise_preview'] ) ) {
			$preview_type = sanitize_text_field( wp_unslash( $_GET['wise_preview'] ) );
			$nonce        = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			if ( wp_verify_nonce( $nonce, 'wise_email_preview' ) && in_array( $preview_type, array( 'order_placed', 'receipt_uploaded' ), true ) ) {
				$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
				$data     = $this->get_sample_email_data( $preview_type, $order_id );

				if ( 'order_placed' === $preview_type ) {
					$html = $this->render_order_placed_email( $data );
				} else {
					$html = $this->render_receipt_uploaded_email( $data );
				}

				// Make links open in new tab (like WC preview).
				$html = str_replace( '<a ', '<a target="_blank" ', $html );

				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Email HTML is fully escaped internally.
				exit;
			}
		}

		?>
		<h2><?php esc_html_e( 'Wise Transfer (unofficial) Settings', 'woo-wise-transfer' ); ?></h2>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

	/**
	 * Generate HTML for the email_preview custom field type.
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field data.
	 * @return string
	 */
	public function generate_email_preview_html( $key, $data ) {
		$nonce    = wp_create_nonce( 'wise_email_preview' );
		$base_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wise_transfer' );

		// Fetch recent Wise Transfer orders for the dropdown.
		$recent_orders = wc_get_orders( array(
			'payment_method' => 'wise_transfer',
			'limit'          => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'return'         => 'ids',
		) );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html( $data['title'] ); ?></th>
			<td class="forminp wise-email-preview-controls">
				<select id="wise-preview-order">
					<option value=""><?php esc_html_e( 'Sample data', 'woo-wise-transfer' ); ?></option>
					<?php foreach ( $recent_orders as $oid ) : ?>
						<?php
						$o = wc_get_order( $oid );
						if ( ! $o ) {
							continue;
						}
						?>
						<option value="<?php echo esc_attr( $oid ); ?>">
							<?php
							printf(
								/* translators: 1: order ID, 2: customer name, 3: order total */
								esc_html__( '#%1$s — %2$s (%3$s)', 'woo-wise-transfer' ),
								esc_html( $oid ),
								esc_html( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ),
								wp_strip_all_tags( $o->get_formatted_order_total() )
							);
							?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button" id="wise-preview-btn">
					<?php esc_html_e( 'Preview', 'woo-wise-transfer' ); ?>
				</button>

				<script>
				(function(){
					var url = <?php echo wp_json_encode( $base_url . '&_wpnonce=' . $nonce . '&wise_preview=receipt_uploaded' ); ?>;
					document.getElementById('wise-preview-btn').addEventListener('click', function(){
						var orderId = document.getElementById('wise-preview-order').value;
						var full = orderId ? url + '&order_id=' + encodeURIComponent( orderId ) : url;
						window.open( full, '_blank' );
					});
				})();
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue checkout CSS and JS.
	 */
	public function enqueue_checkout_assets() {
		if ( ! is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_order_received_page() ) {
			return;
		}

		wp_enqueue_style(
			'woo-wise-transfer-checkout',
			WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/css/checkout.css',
			array(),
			WOO_WISE_TRANSFER_VERSION
		);

		wp_enqueue_script(
			'woo-wise-transfer-checkout',
			WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/js/checkout.js',
			array( 'jquery' ),
			WOO_WISE_TRANSFER_VERSION,
			true
		);

		wp_localize_script( 'woo-wise-transfer-checkout', 'woo_wise_transfer', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wise_transfer_upload' ),
			'i18n'     => array(
				'invalid_format'  => __( 'Please upload a JPG, PNG, or PDF file.', 'woo-wise-transfer' ),
				'file_too_large'  => __( 'File is too large. Maximum size is 5MB.', 'woo-wise-transfer' ),
				'copied'          => __( 'Copied!', 'woo-wise-transfer' ),
				'uploading'       => __( 'Uploading...', 'woo-wise-transfer' ),
				'upload_success'  => __( 'Receipt uploaded successfully!', 'woo-wise-transfer' ),
				'upload_failed'   => __( 'Upload failed. Please try again.', 'woo-wise-transfer' ),
			),
		) );
	}

	/**
	 * Enqueue admin CSS.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		if ( 'wise_transfer' !== $section ) {
			return;
		}

		wp_enqueue_style(
			'woo-wise-transfer-admin',
			WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WOO_WISE_TRANSFER_VERSION
		);
	}

	/**
	 * Payment fields displayed at checkout — description only.
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo '<p>' . wp_kses_post( wpautop( wptexturize( $this->description ) ) ) . '</p>';
		}
	}

	/**
	 * Validate payment fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		return true;
	}

	/**
	 * Process the payment.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Order not found.', 'woo-wise-transfer' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->set_status( 'on-hold', __( 'Awaiting Wise Transfer payment confirmation.', 'woo-wise-transfer' ) );
		$order->save();

		WC()->cart->empty_cart();

		// Send notification email.
		$this->send_notification_email( $order );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Handle AJAX receipt upload from thank you page.
	 */
	public function ajax_upload_receipt() {
		// Verify nonce — return JSON on failure instead of wp_die().
		if ( ! wp_verify_nonce( isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '', 'wise_transfer_upload' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'woo-wise-transfer' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order || 'wise_transfer' !== $order->get_payment_method() ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order.', 'woo-wise-transfer' ) ) );
		}

		// Verify the order belongs to the current user or matches the order key.
		$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';
		if ( $order->get_order_key() !== $order_key ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'woo-wise-transfer' ) ) );
		}

		if ( empty( $_FILES['wise_receipt']['name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'woo-wise-transfer' ) ) );
		}

		// Check for PHP upload errors.
		if ( ! empty( $_FILES['wise_receipt']['error'] ) ) {
			$php_error = $_FILES['wise_receipt']['error'];
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE   => __( 'File exceeds server upload limit.', 'woo-wise-transfer' ),
				UPLOAD_ERR_FORM_SIZE  => __( 'File exceeds form upload limit.', 'woo-wise-transfer' ),
				UPLOAD_ERR_PARTIAL    => __( 'File was only partially uploaded.', 'woo-wise-transfer' ),
				UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'woo-wise-transfer' ),
				UPLOAD_ERR_NO_TMP_DIR => __( 'Server missing temporary folder.', 'woo-wise-transfer' ),
				UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'woo-wise-transfer' ),
				UPLOAD_ERR_EXTENSION  => __( 'Upload blocked by server extension.', 'woo-wise-transfer' ),
			);
			$msg = isset( $error_messages[ $php_error ] ) ? $error_messages[ $php_error ] : __( 'Upload error.', 'woo-wise-transfer' );
			wp_send_json_error( array( 'message' => $msg ) );
		}

		// Validate file type.
		$allowed   = array( 'image/jpeg', 'image/png', 'application/pdf' );
		$file_type = wp_check_filetype( sanitize_file_name( $_FILES['wise_receipt']['name'] ) );

		if ( ! in_array( $file_type['type'], $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a JPG, PNG, or PDF file.', 'woo-wise-transfer' ) ) );
		}

		$max_size = 5 * 1024 * 1024; // 5MB
		if ( $_FILES['wise_receipt']['size'] > $max_size ) {
			wp_send_json_error( array( 'message' => __( 'File is too large. Maximum size is 5MB.', 'woo-wise-transfer' ) ) );
		}

		$upload_result = $this->handle_receipt_upload( $order_id );

		if ( is_wp_error( $upload_result ) ) {
			wp_send_json_error( array( 'message' => $upload_result->get_error_message() ) );
		}

		$uploaded_at = current_time( 'mysql' );

		$order->update_meta_data( '_wise_receipt_url', $upload_result['url'] );
		$order->update_meta_data( '_wise_receipt_path', $upload_result['file'] );
		$order->update_meta_data( '_wise_receipt_filename', sanitize_file_name( $_FILES['wise_receipt']['name'] ) );
		$order->update_meta_data( '_wise_receipt_uploaded_at', $uploaded_at );
		$order->add_order_note( __( 'Customer uploaded proof of payment.', 'woo-wise-transfer' ) );
		$order->save();

		// Notify admin about the uploaded receipt.
		$this->send_receipt_notification_email( $order, $upload_result['url'] );

		$safe_filename = sanitize_file_name( $_FILES['wise_receipt']['name'] );
		$is_image      = preg_match( '/\.(jpe?g|png)$/i', $safe_filename );

		wp_send_json_success( array(
			'message'     => __( 'Receipt uploaded successfully!', 'woo-wise-transfer' ),
			'filename'    => $safe_filename,
			'uploaded_at' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $uploaded_at ) ),
			'thumb_url'   => $is_image ? $upload_result['url'] : '',
		) );
	}

	/**
	 * Handle the receipt file upload.
	 *
	 * @param int $order_id Order ID.
	 * @return array|WP_Error Upload result or error.
	 */
	private function handle_receipt_upload( $order_id ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload_dir   = wp_upload_dir();
		$receipts_dir = $upload_dir['basedir'] . '/wise-receipts';

		if ( ! file_exists( $receipts_dir ) ) {
			wp_mkdir_p( $receipts_dir );
		}

		// Generate unique filename.
		$file_ext  = pathinfo( sanitize_file_name( $_FILES['wise_receipt']['name'] ), PATHINFO_EXTENSION );
		$file_name = 'receipt-order-' . $order_id . '-' . wp_generate_password( 8, false ) . '.' . $file_ext;

		$overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'jpg|jpeg' => 'image/jpeg',
				'png'      => 'image/png',
				'pdf'      => 'application/pdf',
			),
			'unique_filename_callback' => function( $dir, $name, $ext ) use ( $file_name ) {
				return $file_name;
			},
		);

		// Temporarily filter upload dir.
		$upload_dir_filter = function( $dirs ) {
			$dirs['path']   = $dirs['basedir'] . '/wise-receipts';
			$dirs['url']    = $dirs['baseurl'] . '/wise-receipts';
			$dirs['subdir'] = '/wise-receipts';
			return $dirs;
		};

		add_filter( 'upload_dir', $upload_dir_filter );
		$result = wp_handle_upload( $_FILES['wise_receipt'], $overrides );
		remove_filter( 'upload_dir', $upload_dir_filter );

		if ( isset( $result['error'] ) ) {
			return new WP_Error( 'upload_error', $result['error'] );
		}

		return $result;
	}

	/**
	 * Render the order-placed notification email HTML.
	 *
	 * @param array $data Email data.
	 * @return string HTML string.
	 */
	private function render_order_placed_email( $data ) {
		$font = "font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;";

		$rows = array(
			__( 'Order ID', 'woo-wise-transfer' )  => '#' . esc_html( $data['order_id'] ),
			__( 'Date', 'woo-wise-transfer' )       => esc_html( $data['order_date'] ),
			__( 'Customer', 'woo-wise-transfer' )   => esc_html( $data['customer'] ),
			__( 'Email', 'woo-wise-transfer' )      => esc_html( $data['email'] ),
			__( 'Total', 'woo-wise-transfer' )      => $data['total'],
		);

		$rows_html = '';
		foreach ( $rows as $label => $value ) {
			$rows_html .= '<tr><td style="padding:12px 0;border-bottom:1px solid #E0E6E0;color:#637381;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;' . $font . '">' . esc_html( $label ) . '</td>';
			$rows_html .= '<td style="padding:12px 0;border-bottom:1px solid #E0E6E0;color:#1A1A1A;font-size:14px;font-weight:600;text-align:right;' . $font . '">' . $value . '</td></tr>';
		}

		/* translators: %s: order ID */
		$subject = sprintf( __( 'New Wise Transfer Payment - Order #%s', 'woo-wise-transfer' ), $data['order_id'] );

		$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>';
		$html .= '<body style="margin:0;padding:20px;background:#f5f7f4;' . $font . '">';
		$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">';
		$html .= '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">';

		// Header.
		$html .= '<tr><td style="background:#163300;padding:24px 32px;">';
		$html .= '<h1 style="margin:0;color:#9FE870;font-size:20px;font-weight:600;' . $font . '">' . esc_html( $subject ) . '</h1>';
		$html .= '</td></tr>';

		// Body.
		$html .= '<tr><td style="padding:32px;">';
		$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">';
		$html .= '<tr><td style="font-size:14px;font-weight:600;color:#1A1A1A;padding-bottom:12px;' . $font . '">' . esc_html__( 'Order Details', 'woo-wise-transfer' ) . '</td><td></td></tr>';
		$html .= $rows_html;
		$html .= '</table>';

		// CTA button.
		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px;"><tr><td>';
		$html .= '<a href="' . esc_url( $data['admin_url'] ) . '" style="display:inline-block;background:#163300;color:#9FE870;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;' . $font . '">' . esc_html__( 'View Order in Dashboard', 'woo-wise-transfer' ) . '</a>';
		$html .= '</td></tr></table>';

		$html .= '</td></tr></table>';
		$html .= '</td></tr></table></body></html>';

		return $html;
	}

	/**
	 * Render the receipt-uploaded notification email HTML.
	 *
	 * @param array $data Email data.
	 * @return string HTML string.
	 */
	private function render_receipt_uploaded_email( $data ) {
		$font = "font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;";

		$rows = array(
			__( 'Order ID', 'woo-wise-transfer' )  => '#' . esc_html( $data['order_id'] ),
			__( 'Customer', 'woo-wise-transfer' )   => esc_html( $data['customer'] ),
			__( 'Email', 'woo-wise-transfer' )      => esc_html( $data['email'] ),
			__( 'Total', 'woo-wise-transfer' )      => $data['total'],
		);

		$rows_html = '';
		foreach ( $rows as $label => $value ) {
			$rows_html .= '<tr><td style="padding:12px 0;border-bottom:1px solid #E0E6E0;color:#637381;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;' . $font . '">' . esc_html( $label ) . '</td>';
			$rows_html .= '<td style="padding:12px 0;border-bottom:1px solid #E0E6E0;color:#1A1A1A;font-size:14px;font-weight:600;text-align:right;' . $font . '">' . $value . '</td></tr>';
		}

		/* translators: %s: order ID */
		$subject = sprintf( __( 'Payment Confirmation Received - Order #%s', 'woo-wise-transfer' ), $data['order_id'] );

		$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>';
		$html .= '<body style="margin:0;padding:20px;background:#f5f7f4;' . $font . '">';
		$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">';
		$html .= '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">';

		// Header.
		$html .= '<tr><td style="background:#163300;padding:24px 32px;">';
		$html .= '<h1 style="margin:0;color:#9FE870;font-size:20px;font-weight:600;' . $font . '">' . esc_html( $subject ) . '</h1>';
		$html .= '</td></tr>';

		// Body.
		$html .= '<tr><td style="padding:32px;">';
		$html .= '<p style="color:#454745;margin:0 0 24px;font-size:14px;line-height:1.5;' . $font . '">' . esc_html__( 'A customer has uploaded proof of payment for their order. Please review the receipt and confirm the payment.', 'woo-wise-transfer' ) . '</p>';
		$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">';
		$html .= '<tr><td style="font-size:14px;font-weight:600;color:#1A1A1A;padding-bottom:12px;' . $font . '">' . esc_html__( 'Order Details', 'woo-wise-transfer' ) . '</td><td></td></tr>';
		$html .= $rows_html;
		$html .= '</table>';

		// Receipt image or PDF link — only if a real file exists.
		$receipt_filename = isset( $data['receipt_filename'] ) ? $data['receipt_filename'] : '';
		$is_image         = preg_match( '/\.(jpe?g|png)$/i', $receipt_filename );

		if ( $receipt_filename ) {
			$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">';
			$html .= '<tr><td style="font-size:14px;font-weight:600;color:#1A1A1A;padding-bottom:12px;' . $font . '">' . esc_html__( 'Uploaded Receipt', 'woo-wise-transfer' ) . '</td></tr>';
			$html .= '<tr><td style="padding:0;">';

			if ( $is_image ) {
				$html .= '<a href="' . esc_url( $data['receipt_url'] ) . '"><img src="' . esc_url( $data['receipt_url'] ) . '" alt="' . esc_attr__( 'Payment Receipt', 'woo-wise-transfer' ) . '" style="max-width:100%;height:auto;border-radius:8px;border:1px solid #E0E6E0;display:block;"></a>';
			} else {
				$html .= '<a href="' . esc_url( $data['receipt_url'] ) . '" style="display:inline-block;background:#f5f7f4;color:#163300;text-decoration:none;padding:12px 16px;border-radius:8px;border:1px solid #E0E6E0;font-size:14px;' . $font . '">';
				$html .= '&#128196; ' . esc_html( $receipt_filename );
				$html .= '</a>';
			}

			$html .= '</td></tr></table>';
		}

		// CTA buttons.
		$html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px;"><tr>';
		$html .= '<td style="padding-right:8px;"><a href="' . esc_url( $data['admin_url'] ) . '" style="display:inline-block;background:#163300;color:#9FE870;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;' . $font . '">' . esc_html__( 'View Order', 'woo-wise-transfer' ) . '</a></td>';
		$html .= '<td><a href="' . esc_url( $data['receipt_url'] ) . '" style="display:inline-block;background:#9FE870;color:#163300;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;' . $font . '">' . esc_html__( 'View Receipt', 'woo-wise-transfer' ) . '</a></td>';
		$html .= '</tr></table>';

		$html .= '</td></tr></table>';
		$html .= '</td></tr></table></body></html>';

		return $html;
	}

	/**
	 * Get email data for previews — from a real order or sample placeholders.
	 *
	 * @param string $type     'order_placed' or 'receipt_uploaded'.
	 * @param int    $order_id Optional order ID to use real data.
	 * @return array
	 */
	private function get_sample_email_data( $type, $order_id = 0 ) {
		$order = $order_id ? wc_get_order( $order_id ) : null;

		if ( $order && 'wise_transfer' === $order->get_payment_method() ) {
			$data = array(
				'order_id'   => $order->get_id(),
				'order_date' => $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				'customer'   => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'email'      => $order->get_billing_email(),
				'total'      => $order->get_formatted_order_total(),
				'admin_url'  => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
			);

			if ( 'receipt_uploaded' === $type ) {
				$receipt = $order->get_meta( '_wise_receipt_url' );
				$data['receipt_url']      = $receipt ? $receipt : home_url( '/wp-content/uploads/wise-receipts/receipt-sample.jpg' );
				$data['receipt_filename'] = $order->get_meta( '_wise_receipt_filename' );
			}

			return $data;
		}

		// Fallback to sample data.
		$data = array(
			'order_id'   => '1234',
			'order_date' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'customer'   => 'Jane Smith',
			'email'      => 'jane@example.com',
			'total'      => wc_price( 149.99 ),
			'admin_url'  => admin_url( 'post.php?post=1234&action=edit' ),
		);

		if ( 'receipt_uploaded' === $type ) {
			$data['receipt_url']      = 'https://placehold.co/600x400/f5f7f4/163300?text=Receipt+Preview';
			$data['receipt_filename'] = 'receipt-sample.jpg';
		}

		return $data;
	}

	/**
	 * Send notification email to admin.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function send_notification_email( $order ) {
		$to = $this->notification_email;

		if ( empty( $to ) ) {
			return;
		}

		$order_id = $order->get_id();
		$data     = array(
			'order_id'   => $order_id,
			'order_date' => $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'customer'   => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'email'      => $order->get_billing_email(),
			'total'      => $order->get_formatted_order_total(),
			'admin_url'  => admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
		);

		/* translators: %s: order ID */
		$subject = sprintf( __( 'New Wise Transfer Payment - Order #%s', 'woo-wise-transfer' ), $order_id );
		$message = $this->render_order_placed_email( $data );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Send notification email when customer uploads proof of payment.
	 *
	 * @param WC_Order $order       Order object.
	 * @param string   $receipt_url URL of the uploaded receipt.
	 */
	private function send_receipt_notification_email( $order, $receipt_url ) {
		$to = $this->notification_email;

		if ( empty( $to ) ) {
			return;
		}

		$order_id = $order->get_id();
		$data     = array(
			'order_id'    => $order_id,
			'customer'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'email'       => $order->get_billing_email(),
			'total'       => $order->get_formatted_order_total(),
			'admin_url'        => admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
			'receipt_url'      => $receipt_url,
			'receipt_filename' => $order->get_meta( '_wise_receipt_filename' ),
		);

		/* translators: %s: order ID */
		$subject = sprintf( __( 'Payment Confirmation Received - Order #%s', 'woo-wise-transfer' ), $order_id );
		$message = $this->render_receipt_uploaded_email( $data );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Thank you page output — bank details + upload form.
	 *
	 * @param int $order_id Order ID.
	 */
	/**
	 * Block-based order confirmation fallback.
	 * Fires on woocommerce_order_details_before_order_table for WC 8.3+ block checkout.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function thankyou_page_block( $order ) {
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		// Prevent double rendering if classic hook already fired.
		if ( did_action( 'woocommerce_thankyou_' . $this->id ) ) {
			return;
		}

		// Enqueue assets inline since wp_enqueue_scripts may have already fired.
		if ( ! wp_style_is( 'woo-wise-transfer-checkout', 'enqueued' ) ) {
			wp_enqueue_style(
				'woo-wise-transfer-checkout',
				WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/css/checkout.css',
				array(),
				WOO_WISE_TRANSFER_VERSION
			);
		}

		$this->thankyou_page( $order->get_id() );
	}

	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$receipt_url = $order->get_meta( '_wise_receipt_url' );
		?>
		<div class="wise-thankyou-wrapper">

			<!-- Bank Transfer Details Card -->
			<div class="wise-thankyou-card">
				<div class="wise-card-header">
					<img src="<?php echo esc_url( WOO_WISE_TRANSFER_PLUGIN_URL . 'assets/images/wise-logo.svg' ); ?>" alt="Wise" class="wise-card-logo">
					<h3 class="wise-card-title"><?php esc_html_e( 'Transfer Details', 'woo-wise-transfer' ); ?></h3>
					<p class="wise-card-subtitle"><?php esc_html_e( 'Please transfer the order amount to the account below, then upload your proof of payment.', 'woo-wise-transfer' ); ?></p>
				</div>

				<table class="wise-details-table">
					<?php if ( $this->account_email ) : ?>
					<tr class="wise-details-row">
						<td class="wise-details-label"><?php esc_html_e( 'Email address', 'woo-wise-transfer' ); ?></td>
						<td class="wise-details-value">
							<span class="wise-details-value-with-copy">
								<?php echo esc_html( $this->account_email ); ?>
								<button type="button" class="wise-copy-btn" data-copy="<?php echo esc_attr( $this->account_email ); ?>" title="<?php esc_attr_e( 'Copy', 'woo-wise-transfer' ); ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
								</button>
							</span>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( $this->account_name ) : ?>
					<tr class="wise-details-row">
						<td class="wise-details-label"><?php esc_html_e( 'Account holder', 'woo-wise-transfer' ); ?></td>
						<td class="wise-details-value"><?php echo esc_html( $this->account_name ); ?></td>
					</tr>
					<?php endif; ?>

					<?php if ( $this->bank_name ) : ?>
					<tr class="wise-details-row">
						<td class="wise-details-label"><?php esc_html_e( 'Bank name', 'woo-wise-transfer' ); ?></td>
						<td class="wise-details-value"><?php echo esc_html( $this->bank_name ); ?></td>
					</tr>
					<?php endif; ?>

					<?php if ( $this->account_number ) : ?>
					<tr class="wise-details-row">
						<td class="wise-details-label"><?php esc_html_e( 'Account number', 'woo-wise-transfer' ); ?></td>
						<td class="wise-details-value">
							<span class="wise-details-value-with-copy">
								<?php echo esc_html( $this->account_number ); ?>
								<button type="button" class="wise-copy-btn" data-copy="<?php echo esc_attr( $this->account_number ); ?>" title="<?php esc_attr_e( 'Copy', 'woo-wise-transfer' ); ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
								</button>
							</span>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( $this->currency ) : ?>
					<tr class="wise-details-row">
						<td class="wise-details-label"><?php esc_html_e( 'Currency', 'woo-wise-transfer' ); ?></td>
						<td class="wise-details-value"><?php echo esc_html( $this->currency ); ?></td>
					</tr>
					<?php endif; ?>

					<?php if ( $this->swift_code ) : ?>
					<tr class="wise-details-row">
						<td class="wise-details-label"><?php esc_html_e( 'SWIFT code', 'woo-wise-transfer' ); ?></td>
						<td class="wise-details-value">
							<span class="wise-details-value-with-copy">
								<?php echo esc_html( $this->swift_code ); ?>
								<button type="button" class="wise-copy-btn" data-copy="<?php echo esc_attr( $this->swift_code ); ?>" title="<?php esc_attr_e( 'Copy', 'woo-wise-transfer' ); ?>">
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

			<!-- Upload Proof of Payment — Nudge -->
			<?php if ( $receipt_url ) : ?>
			<?php
				$receipt_filename = $order->get_meta( '_wise_receipt_filename' );
				$uploaded_at      = $order->get_meta( '_wise_receipt_uploaded_at' );
				$is_image         = preg_match( '/\.(jpe?g|png)$/i', $receipt_filename );
			?>
			<div class="wise-upload-card">
				<h4 class="wise-upload-card-title"><?php esc_html_e( 'Proof of Payment', 'woo-wise-transfer' ); ?></h4>
				<div class="wise-nudge">
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
			</div>
			<?php else : ?>
			<div class="wise-upload-card">
				<h4 class="wise-upload-card-title"><?php esc_html_e( 'Proof of Payment', 'woo-wise-transfer' ); ?></h4>
				<form id="wise-upload-form" enctype="multipart/form-data">
					<input type="hidden" name="action" value="wise_upload_receipt">
					<input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">
					<input type="hidden" name="order_key" value="<?php echo esc_attr( $order->get_order_key() ); ?>">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'wise_transfer_upload' ) ); ?>">

					<div class="wise-upload-area" id="wise-upload-area">
						<input type="file" name="wise_receipt" id="wise-receipt-input" accept=".jpg,.jpeg,.png,.pdf" style="display:none;">
						<div class="wise-upload-placeholder" id="wise-upload-placeholder">
							<span class="wise-upload-icon">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
							</span>
							<span class="wise-upload-label"><?php esc_html_e( 'Upload Proof of Payment', 'woo-wise-transfer' ); ?></span>
							<span class="wise-upload-sublabel"><?php esc_html_e( 'PDF, JPEG or PNG less than 5MB', 'woo-wise-transfer' ); ?></span>
							<span class="wise-upload-select-btn"><?php esc_html_e( 'Or select file', 'woo-wise-transfer' ); ?></span>
						</div>
						<div class="wise-upload-preview" id="wise-upload-preview" style="display:none;">
							<img id="wise-upload-thumb" class="wise-upload-thumb" src="" alt="">
							<span class="wise-upload-file-icon" id="wise-upload-pdf-icon" style="display:none;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
							</span>
							<span class="wise-upload-file-info">
								<span class="wise-upload-filename" id="wise-upload-filename"></span>
							</span>
							<button type="button" class="wise-upload-remove" id="wise-upload-remove" title="<?php esc_attr_e( 'Remove', 'woo-wise-transfer' ); ?>">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
							</button>
						</div>
					</div>
					<div class="wise-upload-error" id="wise-upload-error" style="display:none;"></div>
					<button type="submit" class="wise-submit-btn" id="wise-submit-btn" disabled>
						<?php esc_html_e( 'Confirm My Payment', 'woo-wise-transfer' ); ?>
					</button>
				</form>
			</div>
			<?php endif; ?>

		</div>

		<?php if ( ! $receipt_url ) : ?>
		<script>
		(function () {
			if ( typeof window._wiseUploadBound !== 'undefined' ) return;
			window._wiseUploadBound = true;

			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var i18n = {
				invalid_format: <?php echo wp_json_encode( __( 'Please upload a JPG, PNG, or PDF file.', 'woo-wise-transfer' ) ); ?>,
				file_too_large: <?php echo wp_json_encode( __( 'File is too large. Maximum size is 5MB.', 'woo-wise-transfer' ) ); ?>,
				uploading:      <?php echo wp_json_encode( __( 'Uploading...', 'woo-wise-transfer' ) ); ?>,
				upload_failed:  <?php echo wp_json_encode( __( 'Upload failed. Please try again.', 'woo-wise-transfer' ) ); ?>
			};

			function el(id) { return document.getElementById(id); }

			function showError(msg) {
				var e = el('wise-upload-error');
				if (e) { e.textContent = msg; e.style.display = 'block'; }
				var a = el('wise-upload-area');
				if (a) a.classList.add('has-error');
			}
			function hideError() {
				var e = el('wise-upload-error');
				if (e) { e.textContent = ''; e.style.display = 'none'; }
				var a = el('wise-upload-area');
				if (a) a.classList.remove('has-error');
			}
			function resetForm() {
				var input = el('wise-receipt-input');
				if (input) input.value = '';
				var ph = el('wise-upload-placeholder');
				if (ph) ph.style.display = '';
				var pv = el('wise-upload-preview');
				if (pv) pv.style.display = 'none';
				var thumb = el('wise-upload-thumb');
				if (thumb) { thumb.src = ''; thumb.style.display = 'none'; }
				var pdfIcon = el('wise-upload-pdf-icon');
				if (pdfIcon) pdfIcon.style.display = 'none';
				var fn = el('wise-upload-filename');
				if (fn) fn.textContent = '';
				var area = el('wise-upload-area');
				if (area) area.classList.remove('has-file');
				var btn = el('wise-submit-btn');
				if (btn) btn.disabled = true;
				hideError();
			}

			/* Click to open file dialog */
			var area = el('wise-upload-area');
			if (area) {
				area.addEventListener('click', function (e) {
					if (e.target.closest && e.target.closest('#wise-upload-remove')) return;
					var input = el('wise-receipt-input');
					if (input) input.click();
				});
			}

			/* File selected — show preview */
			var fileInput = el('wise-receipt-input');
			if (fileInput) {
				fileInput.addEventListener('change', function () {
					var file = this.files[0];
					if (!file) return;

					var allowed = ['image/jpeg', 'image/png', 'application/pdf'];
					if (allowed.indexOf(file.type) === -1) {
						showError(i18n.invalid_format);
						this.value = '';
						return;
					}
					if (file.size > 5 * 1024 * 1024) {
						showError(i18n.file_too_large);
						this.value = '';
						return;
					}

					hideError();

					var fn = el('wise-upload-filename');
					if (fn) fn.textContent = file.name;

					var thumb = el('wise-upload-thumb');
					var pdfIcon = el('wise-upload-pdf-icon');

					if (file.type === 'application/pdf') {
						if (thumb) thumb.style.display = 'none';
						if (pdfIcon) pdfIcon.style.display = 'flex';
					} else {
						if (pdfIcon) pdfIcon.style.display = 'none';
						if (thumb) {
							var reader = new FileReader();
							reader.onload = function (ev) {
								thumb.src = ev.target.result;
								thumb.style.display = 'block';
							};
							reader.readAsDataURL(file);
						}
					}

					var ph = el('wise-upload-placeholder');
					if (ph) ph.style.display = 'none';
					var pv = el('wise-upload-preview');
					if (pv) pv.style.display = 'flex';
					var a = el('wise-upload-area');
					if (a) a.classList.add('has-file');
					var btn = el('wise-submit-btn');
					if (btn) btn.disabled = false;
				});
			}

			/* Remove file */
			var removeBtn = el('wise-upload-remove');
			if (removeBtn) {
				removeBtn.addEventListener('click', function (e) {
					e.stopPropagation();
					resetForm();
				});
			}

			/* Form submit */
			var form = el('wise-upload-form');
			if (form) {
				form.addEventListener('submit', function (e) {
					e.preventDefault();
					var input = el('wise-receipt-input');
					if (!input || !input.files || input.files.length === 0) return;

					var btn = el('wise-submit-btn');
					var originalText = btn ? btn.textContent : '';
					if (btn) { btn.disabled = true; btn.textContent = i18n.uploading; }

					var fd = new FormData(form);
					var xhr = new XMLHttpRequest();
					xhr.open('POST', ajaxUrl, true);
					xhr.onload = function () {
						if (btn) { btn.disabled = false; btn.textContent = originalText; }
						if (xhr.status === 0 || xhr.status >= 400) {
							showError(i18n.upload_failed + ' (HTTP ' + xhr.status + ')');
							return;
						}
						try {
							var resp = JSON.parse(xhr.responseText);
							if (resp.success) {
								var d = resp.data || {};
								var thumbHtml = d.thumb_url
									? '<img class="wise-nudge-thumb" src="' + d.thumb_url + '" alt="">'
									: '<span class="wise-nudge-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>';
								form.outerHTML =
									'<div class="wise-nudge">' +
										thumbHtml +
										'<div class="wise-nudge-body">' +
											'<p class="wise-nudge-title">' + (d.filename || '') + '</p>' +
											(d.uploaded_at ? '<p class="wise-nudge-subtitle">' + d.uploaded_at + '</p>' : '') +
										'</div>' +
									'</div>';
							} else {
								showError((resp.data && resp.data.message) || i18n.upload_failed);
							}
						} catch (ex) {
							showError(i18n.upload_failed + ' — ' + xhr.responseText.substring(0, 120));
						}
					};
					xhr.onerror = function () {
						if (btn) { btn.disabled = false; btn.textContent = originalText; }
						showError(i18n.upload_failed + ' (network error)');
					};
					xhr.send(fd);
				});
			}
		})();
		</script>
		<?php endif; ?>

		<?php
	}

	/**
	 * Display uploaded receipt in admin order detail.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function display_admin_order_receipt( $order ) {
		if ( 'wise_transfer' !== $order->get_payment_method() ) {
			return;
		}

		$receipt_url  = $order->get_meta( '_wise_receipt_url' );
		$receipt_file = $order->get_meta( '_wise_receipt_filename' );

		if ( empty( $receipt_url ) ) {
			return;
		}

		$is_image = preg_match( '/\.(jpg|jpeg|png)$/i', $receipt_file );
		?>
		<div class="wise-admin-receipt">
			<h3><?php esc_html_e( 'Wise Transfer Receipt', 'woo-wise-transfer' ); ?></h3>
			<?php if ( $is_image ) : ?>
				<a href="<?php echo esc_url( $receipt_url ); ?>" target="_blank">
					<img src="<?php echo esc_url( $receipt_url ); ?>" alt="<?php esc_attr_e( 'Payment Receipt', 'woo-wise-transfer' ); ?>" style="max-width: 300px; border-radius: 8px; border: 1px solid #E0E6E0;">
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $receipt_url ); ?>" target="_blank" class="button">
					<?php echo esc_html( $receipt_file ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}
}
