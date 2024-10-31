<?php

namespace Paydock\Services;

use Paydock\Abstracts\AbstractSingleton;
use Paydock\Enums\OrderListColumns;
use Paydock\Enums\SettingsTabs;
use Paydock\PaydockPlugin;
use Paydock\Services\Checkout\AfterpayAPMsPaymentServiceService;
use Paydock\Services\Checkout\AfterpayWalletService;
use Paydock\Services\Checkout\ApplePayWalletService;
use Paydock\Services\Checkout\BankAccountPaymentService;
use Paydock\Services\Checkout\CardPaymentService;
use Paydock\Services\Checkout\GooglePayWalletService;
use Paydock\Services\Checkout\PayPalWalletService;
use Paydock\Services\Checkout\ZipAPMsPaymentServiceService;
use Paydock\Services\Settings\LiveConnectionSettingService;
use Paydock\Services\Settings\LogsSettingService;
use Paydock\Services\Settings\SandboxConnectionSettingService;
use Paydock\Services\Settings\WidgetSettingService;

class FiltersService extends AbstractSingleton {
	protected static $instance = null;

	protected function __construct() {
		$this->addWooCommerceFilters();
		$this->addSettingsLink();
	}


	public function ordersListNewColumn( $columns ) {
		$new_columns = [];

		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;

			if ( OrderListColumns::AFTER_COLUMN === $column_name ) {
				$new_columns[ OrderListColumns::PAYMENT_SOURCE_TYPE()->getKey() ] = OrderListColumns::PAYMENT_SOURCE_TYPE()->getLabel();
			}
		}

		return $new_columns;
	}

	public function ordersListNewColumnContent( $column, $order ) {
		if ( OrderListColumns::PAYMENT_SOURCE_TYPE()->getKey() === $column ) {
			$status = get_post_meta( $order->get_id(), OrderListColumns::PAYMENT_SOURCE_TYPE()->getKey() );

			echo esc_html( is_array( $status ) ? reset( $status ) : $status );
		}
	}

	public function changeOrderAmount( $formatted_total, $order ) {
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		if ( ! empty( $page ) && 'wc-orders' === $page ) {
			$capturedAmount = get_post_meta( $order->get_id(), 'capture_amount' );
			if ( $capturedAmount && is_array( $capturedAmount ) ) {
				$capturedAmount = reset( $capturedAmount );

				if ( $capturedAmount == $order->get_total() ) {
					return $formatted_total;
				}

				$price           = wc_price( ( $capturedAmount - $order->get_total_refunded() ),
					[ 'currency' => $order->get_currency() ] );
				$originalPrice   = wc_price( $order->get_total(), [ 'currency' => $order->get_currency() ] );
				$formatted_total = sprintf(
					'<del aria-hidden="true">%1$s</del><ins>%2$s</ins>',
					$originalPrice,
					$price
				);
			}
		}

		return $formatted_total;
	}

	public function addCaptureAmountCustomColumn( $columns ) {
		$new_columns                   = ( is_array( $columns ) ) ? $columns : [];
		$new_columns['capture_amount'] = 'Capture Amount';

		return $new_columns;
	}

	protected function addWooCommerceFilters(): void {
		add_filter( 'woocommerce_payment_gateways', [ $this, 'registerInWooCommercePaymentClass' ] );
		add_filter( 'woocommerce_register_shop_order_post_statuses', [ $this, 'addCustomOrderStatuses' ] );
		add_filter( 'wc_order_statuses', [ $this, 'addCustomOrderSingleStatusesStatuses' ] );
		add_filter( 'woocommerce_thankyou_order_received_text', [ $this, 'woocommerceThankyouOrderReceivedText' ] );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'ordersListNewColumn' ] );
		add_filter( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'ordersListNewColumnContent' ], 10, 2 );
		add_filter( 'woocommerce_get_formatted_order_total', [ $this, 'changeOrderAmount' ], 10, 2 );
		add_filter( 'manage_edit-shop_order_columns', [ $this, 'addCaptureAmountCustomColumn' ], 20 );

	}

	protected function addSettingsLink(): void {
		add_filter( 'plugin_action_links_' . plugin_basename( PAYDOCK_PLUGIN_FILE ), [ $this, 'getSettingLink' ] );
	}

	public function registerInWooCommercePaymentClass( array $methods ): array {
		global $current_section;
		global $current_tab;

		$methods[] = LiveConnectionSettingService::class;
		if ( 'checkout' != $current_tab
		     || in_array(
			     $current_section,
			     array_map(
				     function ( SettingsTabs $tab ) {
					     return $tab->value;
				     },
				     SettingsTabs::secondary()
			     )
		     ) ) {
			$methods[] = SandboxConnectionSettingService::class;
			$methods[] = WidgetSettingService::class;
			$methods[] = LogsSettingService::class;
			$methods[] = CardPaymentService::class;
			$methods[] = BankAccountPaymentService::class;
			$methods[] = ApplePayWalletService::class;
			$methods[] = GooglePayWalletService::class;
			$methods[] = AfterpayWalletService::class;
			$methods[] = PayPalWalletService::class;
			$methods[] = AfterpayAPMsPaymentServiceService::class;
			$methods[] = ZipAPMsPaymentServiceService::class;
		}


		return $methods;
	}

	public function woocommerceThankyouOrderReceivedText( $text ) {
		$orderId = absint( get_query_var( 'order-received' ) );
		$options = get_option( "paydock_fraud_{$orderId}" );
		$order   = wc_get_order( $orderId );
		$afterpay = filter_input( INPUT_GET, 'afterpay-error', FILTER_SANITIZE_STRING );

		if ( ! empty( $afterpay ) && ( 'true' === $afterpay ) ) {
			return __( 'Order has been cancelled', 'paydock' );
		}
		if ( false === $options && 'processing' !== $order->get_status() ) {
			return $text;
		}

		return __( 'Your order is being processed. We\'ll get back to you shortly', 'paydock' );
	}

	public function getSettingLink( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . PaydockPlugin::PLUGIN_PREFIX ),
				__( 'Settings', 'paydock' )
			)
		);

		return $links;
	}

	public function addCustomOrderStatuses( $order_statuses ) {
		$order_statuses['wc-paydock-paid']      = [
			'label'                     => 'Paid via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Paid via Paydock <span class="count">(%s)</span>',
				'Paid via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];
		$order_statuses['wc-paydock-p-paid']    = [
			'label'                     => 'Partial paid via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Partial paid via Paydock <span class="count">(%s)</span>',
				'Partial paid via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];
		$order_statuses['wc-paydock-pending']   = [
			'label'                     => 'Pending via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Panding via Paydock <span class="count">(%s)</span>',
				'Panding via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];
		$order_statuses['wc-paydock-failed']    = [
			'label'                     => 'Failed via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Failed via Paydock <span class="count">(%s)</span>',
				'Failed via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];
		$order_statuses['wc-paydock-authorize'] = [
			'label'                     => 'Authorized via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Authorized via Paydock <span class="count">(%s)</span>',
				'Authorized via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];
		$order_statuses['wc-paydock-cancelled'] = [
			'label'                     => 'Cancelled via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Cancelled via Paydock <span class="count">(%s)</span>',
				'Cancelled via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];
		$order_statuses['wc-paydock-refunded']  = [
			'label'                     => 'Refunded via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Refunded via Paydock <span class="count">(%s)</span>',
				'Refunded via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];
		$order_statuses['wc-paydock-p-refund']  = [
			'label'                     => 'Partial refunded via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Partial refunded via Paydock <span class="count">(%s)</span>',
				'Partial refunded via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];
		$order_statuses['wc-paydock-requested'] = [
			'label'                     => 'Requested via Paydock',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: %s is the number of orders.
			'label_count'               => _n_noop(
				'Requested via Paydock <span class="count">(%s)</span>',
				'Requested via Paydock <span class="count">(%s)</span>',
				'woocommerce'
			),
		];

		return $order_statuses;
	}

	public function addCustomOrderSingleStatusesStatuses( $order_statuses ) {
		$order_statuses['wc-paydock-failed']    = 'Failed via Paydock';
		$order_statuses['wc-paydock-pending']   = 'Pending via Paydock';
		$order_statuses['wc-paydock-paid']      = 'Paid via Paydock';
		$order_statuses['wc-paydock-authorize'] = 'Authorized via Paydock';
		$order_statuses['wc-paydock-cancelled'] = 'Cancelled via Paydock';
		$order_statuses['wc-paydock-refunded']  = 'Refunded via Paydock';
		$order_statuses['wc-paydock-p-refund']  = 'Partial refunded via Paydock';
		$order_statuses['wc-paydock-requested'] = 'Requested via Paydock';
		$order_statuses['wc-paydock-p-paid']    = 'Partial paid via  Paydock';

		return $order_statuses;
	}
}
