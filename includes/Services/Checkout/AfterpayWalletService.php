<?php

namespace Paydock\Services\Checkout;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Paydock\Abstracts\AbstractWalletPaymentService;
use Paydock\Enums\OrderListColumns;
use Paydock\Enums\WalletPaymentMethods;
use Paydock\Repositories\LogRepository;

class AfterpayWalletService extends AbstractWalletPaymentService {
	protected function getWalletType(): WalletPaymentMethods {
		return WalletPaymentMethods::AFTERPAY();
	}

	public function get_title() {
		return trim( $this->title ) ? $this->title : 'Afterpay v1';
	}

	public function process_payment( $order_id, $retry = true, $force_customer = false ) {
		$wpNonce = ! empty( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : null;

		if ( ! wp_verify_nonce( $wpNonce, 'process_payment' ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_process_payment_error',

				/* Translators: %s Error message from API. */
				esc_html( __( 'Error: Security check', 'paydock' ) )
			);
		}

		$order    = wc_get_order( $order_id );
		$data     = [];
		$chargeId = null;

		if ( ! empty( $_POST['payment_response'] ) ) {
			$data = json_decode( sanitize_text_field( $_POST['payment_response'] ), true );
		}

		if ( ( json_last_error() === JSON_ERROR_NONE ) && ! empty( $_POST['payment_response'] ) ) {
			$chargeId = $data['data']['id'];
		}

		$wallets = [];

		if ( ! empty( $_POST['wallets'] ) ) {
			$wallets = json_decode( sanitize_text_field( $_POST['wallets'] ), true );
			if ( null === $wallets ) {
				$wallets = [];
			}
		}

		$wallet  = reset( $wallets );
		$isFraud = ! empty( $wallet['fraud'] ) && $wallet['fraud'];
		if ( $isFraud ) {
			update_option( 'paydock_fraud_' . (string) $order->get_id(), [] );
		}

		$loggerRepository = new LogRepository();

		$order->set_status( 'wc-pending' );
		$order->save();

		update_post_meta( $order_id, 'paydock_charge_id', $chargeId );
		add_post_meta(
			$order_id,
			OrderListColumns::PAYMENT_SOURCE_TYPE()->getKey(),
			$this->getWalletType()->getLabel()
		);

		$loggerRepository->createLogRecord(
			$data['data']['id'] ?? '',
			'Charge',
			'wc-pending',
			'Successful'
		);

		return [
			'result' => 'success',
		];
	}
}
