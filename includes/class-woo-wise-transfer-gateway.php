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
		);
	}

	/**
	 * Custom admin options output.
	 */
	public function admin_options() {
		?>
		<h2><?php esc_html_e( 'Wise Transfer (unofficial) Settings', 'woo-wise-transfer' ); ?></h2>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
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

		$order->update_meta_data( '_wise_receipt_url', $upload_result['url'] );
		$order->update_meta_data( '_wise_receipt_path', $upload_result['file'] );
		$order->update_meta_data( '_wise_receipt_filename', sanitize_file_name( $_FILES['wise_receipt']['name'] ) );
		$order->add_order_note( __( 'Customer uploaded proof of payment.', 'woo-wise-transfer' ) );
		$order->save();

		// Notify admin about the uploaded receipt.
		$this->send_receipt_notification_email( $order, $upload_result['url'] );

		wp_send_json_success( array(
			'message'  => __( 'Receipt uploaded successfully!', 'woo-wise-transfer' ),
			'filename' => sanitize_file_name( $_FILES['wise_receipt']['name'] ),
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
	 * Send notification email to admin.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function send_notification_email( $order ) {
		$to = $this->notification_email;

		if ( empty( $to ) ) {
			return;
		}

		$order_id   = $order->get_id();
		$order_date = $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		$customer   = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$email      = $order->get_billing_email();
		$total      = $order->get_formatted_order_total();
		$admin_url  = admin_url( 'post.php?post=' . $order_id . '&action=edit' );

		/* translators: %s: order ID */
		$subject = sprintf( __( 'New Wise Transfer Payment - Order #%s', 'woo-wise-transfer' ), $order_id );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7f4; margin: 0; padding: 20px; }
				.email-wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
				.email-header { background: #00B9A5; padding: 24px 32px; }
				.email-header h1 { color: #fff; margin: 0; font-size: 20px; font-weight: 600; }
				.email-body { padding: 32px; }
				.info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #E0E6E0; }
				.info-label { color: #637381; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
				.info-value { color: #1A1A1A; font-size: 14px; font-weight: 600; }
				.btn { display: inline-block; background: #00B9A5; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-top: 8px; }
				.btn:hover { background: #009E8C; }
				.section { margin-bottom: 24px; }
				.section-title { font-size: 14px; font-weight: 600; color: #1A1A1A; margin-bottom: 12px; }
			</style>
		</head>
		<body>
			<div class="email-wrapper">
				<div class="email-header">
					<h1><?php echo esc_html( $subject ); ?></h1>
				</div>
				<div class="email-body">
					<div class="section">
						<div class="section-title"><?php esc_html_e( 'Order Details', 'woo-wise-transfer' ); ?></div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Order ID', 'woo-wise-transfer' ); ?></span>
							<span class="info-value">#<?php echo esc_html( $order_id ); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Date', 'woo-wise-transfer' ); ?></span>
							<span class="info-value"><?php echo esc_html( $order_date ); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Customer', 'woo-wise-transfer' ); ?></span>
							<span class="info-value"><?php echo esc_html( $customer ); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Email', 'woo-wise-transfer' ); ?></span>
							<span class="info-value"><?php echo esc_html( $email ); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Total', 'woo-wise-transfer' ); ?></span>
							<span class="info-value"><?php echo wp_kses_post( $total ); ?></span>
						</div>
					</div>

					<div class="section">
						<div class="section-title"><?php esc_html_e( 'Admin Actions', 'woo-wise-transfer' ); ?></div>
						<p><a href="<?php echo esc_url( $admin_url ); ?>" class="btn"><?php esc_html_e( 'View Order in Dashboard', 'woo-wise-transfer' ); ?></a></p>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		$message = ob_get_clean();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

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

		$order_id   = $order->get_id();
		$customer   = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$email      = $order->get_billing_email();
		$total      = $order->get_formatted_order_total();
		$admin_url  = admin_url( 'post.php?post=' . $order_id . '&action=edit' );

		/* translators: %s: order ID */
		$subject = sprintf( __( 'Payment Confirmation Received - Order #%s', 'woo-wise-transfer' ), $order_id );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7f4; margin: 0; padding: 20px; }
				.email-wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
				.email-header { background: #163300; padding: 24px 32px; }
				.email-header h1 { color: #9FE870; margin: 0; font-size: 20px; font-weight: 600; }
				.email-body { padding: 32px; }
				.info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #E0E6E0; }
				.info-label { color: #637381; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
				.info-value { color: #1A1A1A; font-size: 14px; font-weight: 600; }
				.btn { display: inline-block; background: #163300; color: #9FE870; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-top: 8px; }
				.btn-secondary { display: inline-block; background: #9FE870; color: #163300; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-top: 8px; margin-left: 8px; }
				.section { margin-bottom: 24px; }
				.section-title { font-size: 14px; font-weight: 600; color: #1A1A1A; margin-bottom: 12px; }
			</style>
		</head>
		<body>
			<div class="email-wrapper">
				<div class="email-header">
					<h1><?php echo esc_html( $subject ); ?></h1>
				</div>
				<div class="email-body">
					<p style="color: #454745; margin-top: 0;"><?php esc_html_e( 'A customer has uploaded proof of payment for their order. Please review the receipt and confirm the payment.', 'woo-wise-transfer' ); ?></p>

					<div class="section">
						<div class="section-title"><?php esc_html_e( 'Order Details', 'woo-wise-transfer' ); ?></div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Order ID', 'woo-wise-transfer' ); ?></span>
							<span class="info-value">#<?php echo esc_html( $order_id ); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Customer', 'woo-wise-transfer' ); ?></span>
							<span class="info-value"><?php echo esc_html( $customer ); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Email', 'woo-wise-transfer' ); ?></span>
							<span class="info-value"><?php echo esc_html( $email ); ?></span>
						</div>
						<div class="info-row">
							<span class="info-label"><?php esc_html_e( 'Total', 'woo-wise-transfer' ); ?></span>
							<span class="info-value"><?php echo wp_kses_post( $total ); ?></span>
						</div>
					</div>

					<div class="section">
						<div class="section-title"><?php esc_html_e( 'Actions', 'woo-wise-transfer' ); ?></div>
						<p>
							<a href="<?php echo esc_url( $admin_url ); ?>" class="btn"><?php esc_html_e( 'View Order', 'woo-wise-transfer' ); ?></a>
							<a href="<?php echo esc_url( $receipt_url ); ?>" class="btn-secondary"><?php esc_html_e( 'View Receipt', 'woo-wise-transfer' ); ?></a>
						</p>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		$message = ob_get_clean();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

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

			<!-- Upload Proof of Payment -->
			<?php if ( $receipt_url ) : ?>
			<div class="wise-upload-card">
				<h4 class="wise-upload-card-title"><?php esc_html_e( 'Proof of Payment', 'woo-wise-transfer' ); ?></h4>
				<div class="wise-upload-success">
					<span class="wise-upload-success-icon">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="wise-upload-success-text"><?php echo esc_html( $order->get_meta( '_wise_receipt_filename' ) ); ?></span>
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
								form.outerHTML =
									'<div class="wise-upload-success">' +
										'<span class="wise-upload-success-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
										'<span class="wise-upload-success-text">' + (resp.data && resp.data.filename ? resp.data.filename : '') + '</span>' +
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
