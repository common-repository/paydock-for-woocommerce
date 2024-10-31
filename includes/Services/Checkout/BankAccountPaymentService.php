<?php

namespace Paydock\Services\Checkout;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Exception;
use Paydock\Abstracts\AbstractPaymentService;
use Paydock\Enums\OrderListColumns;
use Paydock\Exceptions\LoggedException;
use Paydock\Repositories\LogRepository;
use Paydock\Services\ProcessPayment\BankAccountProcessor;
use Paydock\Services\SettingsService;

class BankAccountPaymentService extends AbstractPaymentService {
	public function __construct() {
		$settings = SettingsService::getInstance();

		$this->id          = 'paydock_bank_account_gateway';
		$this->title       = $settings->getWidgetPaymentBankAccountTitle();
		$this->description = $settings->getWidgetPaymentBankAccountDescription();

		parent::__construct();
	}

	public function is_available() {
		return SettingsService::getInstance()->isEnabledPayment()
		       && SettingsService::getInstance()->isBankAccountEnabled();
	}

	public function payment_scripts() {
		return SettingsService::getInstance()->getWidgetScriptUrl();
	}

	public function process_payment( $order_id, $retry = true, $force_customer = false ) {
		$wpNonce = ! empty( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : null;
		if ( ! wp_verify_nonce( $wpNonce, 'process_payment' ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_process_payment_error',
				esc_html( __( 'Error: Security check', 'paydock' ) )
			);
		}

		$order = wc_get_order( $order_id );

		$loggerRepository = new LogRepository();
		$chargeId         = '';

		try {
			$processor = new BankAccountProcessor( $order, $_POST );

			$response = $processor->run( $order_id );
			$chargeId = ! empty( $response['resource']['data']['_id'] ) ? $response['resource']['data']['_id'] : '';
		} catch ( LoggedException $exception ) {

			$operation = ucfirst( strtolower( $exception->response['resource']['type'] ?? 'undefined' ) );
			$status    = $exception->response['error']['message'] ?? 'empty status';
			$message   = $exception->response['error']['details'][0]['gateway_specific_description'] ?? 'empty message';

			$loggerRepository->createLogRecord( $chargeId, $operation, $status, $message, LogRepository::ERROR );
			throw new RouteException(
				'woocommerce_rest_checkout_process_payment_error',
				/* Translators: %s Exception message. */
				esc_html( sprintf( __( 'Error: %s', 'paydock' ), $exception->getMessage() ) )
			);
		} catch ( Exception $exception ) {
			throw new RouteException(
				'woocommerce_rest_checkout_process_payment_error',
				/* Translators: %s Exception message. */
				esc_html( sprintf( __( 'Error: %s', 'paydock' ), $exception->getMessage() ) )
			);
		}

		$status          = ucfirst( strtolower( $response['resource']['data']['transactions'][0]['status'] ?? 'undefined' ) );
		$operation       = ucfirst( strtolower( $response['resource']['type'] ?? 'undefined' ) );
		$isAuthorization = $response['resource']['data']['authorization'] ?? 0;
		$isCompleted     = false;
		$markAsSuccess   = false;
		if ( $isAuthorization && 'Pending' == $status ) {
			$status = 'wc-paydock-authorize';
		} else {
			$markAsSuccess = true;
			$isCompleted   = 'Complete' === $status;
			$status        = $isCompleted ? 'wc-paydock-paid' : 'wc-paydock-requested';
		}
		$order->set_status( $status );
		$order->payment_complete();
		$order->save();
		update_post_meta( $order->get_id(), 'paydock_charge_id', $chargeId );
		add_post_meta(
			$order->get_id(),
			OrderListColumns::PAYMENT_SOURCE_TYPE()->getKey(),
			'Bank'
		);
		WC()->cart->empty_cart();

		$loggerRepository->createLogRecord(
			$chargeId,
			$operation,
			$status,
			'',
			$markAsSuccess ? LogRepository::SUCCESS : LogRepository::DEFAULT
		);

		return [
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		];
	}

	public function webhook() {

	}
}
