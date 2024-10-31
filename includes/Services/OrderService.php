<?php

namespace Paydock\Services;

class OrderService {

	protected $templateService;

	public function __construct() {
		if ( is_admin() ) {
			$this->templateService = new TemplateService( $this );
		}
	}

	public function iniPaydockOrderButtons( $order ) {
		$orderStatus = $order->get_status();
		if ( in_array( $orderStatus, [
			'paydock-pending',
			'paydock-failed',
			'paydock-refunded',
			'paydock-authorize',
			'paydock-cancelled',
		] ) ) {
			$this->templateService->includeAdminHtml( 'hide-refund-button' );
		}
		if ( in_array( $orderStatus, [
			'paydock-authorize',
			'paydock-paid',
			'paydock-p-paid'
		] ) ) {
			$this->templateService->includeAdminHtml( 'paydock-capture-block', compact( 'order', 'order' ) );
		}
	}

	public function statusChangeVerification( $orderId, $oldStatusKey, $newStatusKey, $order ) {
		if ( ( $oldStatusKey == $newStatusKey ) || ! empty( $GLOBALS['paydock_is_updating_order_status'] ) || null === $orderId ) {
			return;
		}
		$this->customHandleStockReduction( $order, $oldStatusKey, $newStatusKey );
		$rulesForStatuses = [
			'paydock-paid'      => [
				'paydock-refunded',
				'paydock-p-refund',
				'cancelled',
				'paydock-cancelled',
				'refunded',
				'paydock-failed',
				'paydock-pending',
			],
			'paydock-p-paid'    => [
				'paydock-refunded',
				'paydock-p-refund',
				'cancelled',
				'paydock-cancelled',
				'refunded',
				'paydock-failed',
				'paydock-pending',
			],
			'paydock-refunded'  => [ 'paydock-paid', 'paydock-p-paid', 'cancelled', 'paydock-failed', 'refunded' ],
			'paydock-p-refund'  => [
				'paydock-paid',
				'paydock-p-paid',
				'paydock-refunded',
				'refunded',
				'cancelled',
				'paydock-failed'
			],
			'paydock-authorize' => [
				'paydock-paid',
				'paydock-p-paid',
				'paydock-cancelled',
				'paydock-failed',
				'cancelled',
				'paydock-pending',
			],
			'paydock-cancelled' => [ 'paydock-failed', 'cancelled' ],
			'paydock-requested' => [
				'paydock-paid',
				'paydock-p-paid',
				'paydock-failed',
				'cancelled',
				'paydock-pending',
				'paydock-authorize',
			],
		];
		if ( ! empty( $rulesForStatuses[ $oldStatusKey ] ) ) {
			if ( ! in_array( $newStatusKey, $rulesForStatuses[ $oldStatusKey ] ) ) {
				$newStatusName                               = wc_get_order_status_name( $newStatusKey );
				$oldStatusName                               = wc_get_order_status_name( $oldStatusKey );
				$error                                       = sprintf(
				/* translators: %1$s: Old status of processing order.
				 * translators: %2$s: New status of processing order.
				 */
					__( 'You can not change status from "%1$s"  to "%2$s"', 'power-board' ),
					$oldStatusName,
					$newStatusName
				);
				$GLOBALS['paydock_is_updating_order_status'] = true;
				$order->update_status( $oldStatusKey, $error );
				update_option( 'paydock_status_change_error', $error );
				unset( $GLOBALS['paydock_is_updating_order_status'] );
				throw new \Exception( esc_html( $error ) );
			}
		}
	}

	public function informationAboutPartialCaptured( $orderId ) {
		$capturedAmount = get_post_meta( $orderId, 'capture_amount' );
		$order          = wc_get_order( $orderId );
		if ( $capturedAmount && is_array( $capturedAmount ) && in_array( $order->get_status(), [
				'paydock-failed',
				'paydock-pending',
				'paydock-paid',
				'paydock-authorize',
				'paydock-cancelled',
				'paydock-p-refund',
				'paydock-requested',
				'paydock-p-paid',
			] ) ) {
			$capturedAmount = reset( $capturedAmount );
			if ( $order->get_total() > $capturedAmount ) {
				$this->templateService->includeAdminHtml( 'information-about-partial-captured', compact( 'order', 'capturedAmount' ) );
			}
		}
	}

	public function displayStatusChangeError() {
		$screen = get_current_screen();
		if ( 'woocommerce_page_wc-orders' == $screen->id ) {
			$message = get_option( 'paydock_status_change_error', '' );
			if ( ! empty( $message ) ) {
				echo '<div class=\'notice notice-error is-dismissible\'><p>' . esc_html( $message ) . '</p></div>';
				delete_option( 'paydock_status_change_error' );
			}
		}
	}

	function customHandleStockReduction( $order, $oldStatusKey, $newStatusKey ) {
		$statusesWithDecreaseQuantityProduct = [
			'paydock-pending',
			'paydock-paid',
			'paydock-authorize',
			'paydock-requested',
			'paydock-p-paid',
			'completed'
		];
		if ( in_array( $newStatusKey, $statusesWithDecreaseQuantityProduct ) && ! in_array( $oldStatusKey, $statusesWithDecreaseQuantityProduct ) ) {
			foreach ( $order->get_items() as $item ) {
				if ( $product = $item->get_product() ) {
					wc_update_product_stock( $product, $item->get_quantity(), 'decrease' );
				}
			}
		}
	}
}
