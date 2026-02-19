# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

WooCommerce payment gateway plugin for Wise bank transfers. Deferred payment model: customer checks out → order set to on-hold → customer uploads proof of payment on thank you page → admin confirms manually.

**Gateway ID:** `wise_transfer`

## Development Environment

- WordPress 5.8+, PHP 7.4+, WooCommerce 6.0+
- No build tools, no npm/composer dependencies — edit files directly
- Plugin text domain: `woo-wise-transfer`
- Admin settings: WooCommerce → Settings → Payments → Wise Transfer
- Settings URL: `admin.php?page=wc-settings&tab=checkout&section=wise_transfer`

## Architecture

### Bootstrap Flow (`woo-wise-transfer.php`)

Hooks into `plugins_loaded` → checks WooCommerce exists → requires gateway class → registers gateway via `woocommerce_payment_gateways` filter. AJAX handlers (`wp_ajax_wise_upload_receipt` / `wp_ajax_nopriv_wise_upload_receipt`) are registered here in the main plugin file, not inside the gateway class — this is intentional because WC may not instantiate gateway objects during AJAX requests.

### Gateway Class (`includes/class-woo-wise-transfer-gateway.php`)

Single class `Woo_Wise_Transfer_Gateway extends WC_Payment_Gateway` (~1000 lines). All gateway logic lives here:

- **Admin settings** — `init_form_fields()` uses WC Settings API for account info (email, name, bank, SWIFT, currency) and notification email
- **Checkout** — `payment_fields()` renders description only (`has_fields = true` but no custom inputs)
- **Payment processing** — `process_payment()` sets order to `on-hold`, triggers notification email
- **Thank you page** — `thankyou_page()` renders bank details card + file upload form. Also has `thankyou_page_block()` fallback for WC block-based checkout via `woocommerce_order_details_before_order_table`
- **Receipt upload** — `ajax_upload_receipt()` validates nonce + order key + file (MIME: jpeg/png/pdf, max 5MB), stores to `wise-receipts/`, updates order meta
- **Email notifications** — Two inline HTML emails generated with `ob_start()`/`ob_get_clean()`: order placed + receipt uploaded
- **Admin order view** — `display_admin_order_receipt()` shows uploaded receipt in order edit screen

### Frontend Assets

**JS:** `assets/js/checkout.js` (jQuery, ~200 lines) — file upload with drag-drop, copy-to-clipboard, AJAX submit. An inline vanilla JS fallback exists at the bottom of the thank you page PHP output. Guard `window._wiseUploadBound` prevents double-binding when both scripts load.

**CSS:** `assets/css/checkout.css` (frontend) + `assets/css/admin.css` (admin). Design tokens use `:root` CSS variables prefixed `--wise-*` (green: `#163300`/`#9FE870`, font: Inter).

Assets enqueued only on `is_checkout()` or `is_wc_endpoint_url('order-received')`.

### Localized JS Data

```
woo_wise_transfer.ajax_url  — admin-ajax.php URL
woo_wise_transfer.nonce     — wp_create_nonce('wise_upload_receipt')
woo_wise_transfer.i18n      — { invalid_format, file_too_large, copied, uploading, upload_success, upload_failed }
```

## Order Meta Keys

| Key | Value |
|-----|-------|
| `_wise_receipt_url` | Uploaded file URL |
| `_wise_receipt_path` | Server file path |
| `_wise_receipt_filename` | Original sanitized filename |
| `_wise_receipt_uploaded_at` | Upload timestamp (`current_time('mysql')`) |

## File Storage

Receipts stored in `wp-content/uploads/wise-receipts/` (created on plugin activation with `.htaccess` directory listing protection). Files renamed to `receipt-order-{id}-{random8chars}.{ext}`.

## Design System

Follow Wise's official design system: https://wise.design/components

When building or modifying UI, reference the matching Wise component (e.g. Nudge for success states, Upload for file inputs, Button for actions, Snackbar for feedback, Modal/Bottom Sheet for overlays). Key components currently in use:

- **Nudge** — upload success card (`wise-nudge` class)
- **Upload** — receipt file upload area
- **Button** — submit/copy actions
- **Card** — bank details display
- **Summary** — order info layout

CSS variables in `checkout.css` (prefixed `--wise-*`) mirror Wise brand tokens: greens `#163300`/`#9FE870`, font Inter, 16px card radius, 12px input radius. When adding new UI, consult the Wise component docs first and match their visual patterns rather than inventing custom styles.

## WordPress Coding Conventions

- All output escaped: `esc_html()`, `esc_url()`, `esc_attr()`
- All user input sanitized: `sanitize_file_name()`, `sanitize_text_field()`
- All i18n strings use `__()` / `esc_html__()` / `esc_attr__()` with text domain `woo-wise-transfer`
- AJAX secured with `wp_verify_nonce()` + order key verification
- File uploads validated with MIME type whitelist + size limit
- No namespaces — uses prefixed functions (`woo_wise_transfer_*`) and single class name
- No autoloader — single `require_once` in bootstrap
