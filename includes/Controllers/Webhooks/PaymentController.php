<?php

namespace Paydock\Controllers\Webhooks;

use Paydock\Enums\ChargeStatuses;
use Paydock\Enums\NotificationEvents;
use Paydock\Repositories\LogRepository;
use Paydock\Services\SDKAdapterService;

class PaymentController {
	public function capturePayment() {
		$wpNonce = ! empty( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : null;
		if ( ! wp_verify_nonce( $wpNonce, 'capture-or-cancel' ) ) {
			wp_send_json_error( [ 'message' => __( 'Error: Security check', 'paydock' ) ] );

			return;
		}

		$orderId = ! empty( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : null;
		$error   = null;
		if ( ! $orderId ) {
			$error = __( 'The order is not found.' );
		} else {
			$order = wc_get_order( $orderId );
			if ( 'paydock-authorize' != $order->get_status() ) {
				$error = __( 'The order should be have status "paydock-authorize"', 'woocommerce' );
			}
		}
		$orderTotal       = $order->get_total();
		$amount           = ! empty( $_POST['amount'] ) ? wc_format_decimal( $_POST['amount'] ) : $order->get_total();
		$loggerRepository = new LogRepository();
		$paydockChargeId  = get_post_meta( $orderId, 'paydock_charge_id', true );
		if ( ! $error ) {
			$charge = SDKAdapterService::getInstance()->capture( [
				'charge_id' => $paydockChargeId,
				'amount'    => $amount,
			] );
			if ( ! empty( $charge['resource']['data']['status'] ) && 'complete' == $charge['resource']['data']['status'] ) {
				$newChargeId = $charge['resource']['data']['_id'];
				$newStatus   = $orderTotal > $amount ? 'wc-paydock-p-paid' : 'wc-paydock-paid';
				$loggerRepository->createLogRecord(
					$newChargeId,
					'Capture',
					$newStatus,
					'',
					LogRepository::SUCCESS
				);
				update_post_meta( $orderId, 'capture_amount', $amount );
				update_post_meta( $orderId, 'paydock_charge_id', $newChargeId );
				$order->set_status( $newStatus );
				$order->payment_complete();
				$order->save();
				wp_send_json_success( [
					'message' => __( 'The capture process has been successfully.', 'woocommerce' ),
				] );
			} else {
				if ( ! empty( $result['error'] ) ) {
					if ( is_array( $result['error'] ) ) {
						$result['error'] = wp_json_encode( $result['error'] );
					}
					$error = $result['error'];
				} else {
					$error = __( 'The capture process has failed; please try again.', 'woocommerce' );
				}
			}
		}
		if ( $error ) {
			$loggerRepository->createLogRecord( $paydockChargeId, 'Capture', 'error', $error, LogRepository::ERROR );
			wp_send_json_error( [ 'message' => $error ] );
		}
	}

	public function cancelAuthorised() {
		$wpNonce = ! empty( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : null;
		if ( ! wp_verify_nonce( $wpNonce, 'capture-or-cancel' ) ) {
			wp_send_json_error( [ 'message' => __( 'Error: Security check', 'paydock' ) ] );

			return;
		}

		$orderId = ! empty( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : null;
		$error   = null;
		$order   = wc_get_order( $orderId );
		if ( ! $order ) {
			$error = __( 'The order is not found.', 'woocommerce' );
		}
		$loggerRepository = new LogRepository();
		$paydockChargeId  = get_post_meta( $orderId, 'paydock_charge_id', true );
		if ( ! $error ) {
			$result = SDKAdapterService::getInstance()->cancelAuthorised( [ 'charge_id' => $paydockChargeId ] );

			if ( ! empty( $result['resource']['data']['status'] ) && 'cancelled' == $result['resource']['data']['status'] ) {
				$loggerRepository->createLogRecord(
					$paydockChargeId,
					'Cancel-authorised',
					'wc-paydock-cancelled',
					'',
					LogRepository::SUCCESS
				);
				$order->set_status( 'wc-paydock-cancelled' );
				$order->payment_complete();
				$order->save();
				wp_send_json_success(
					[ 'message' => __( 'The payment has been cancelled successfully. ', 'woocommerce' ) ]
				);
			} else {
				if ( ! empty( $result['error'] ) ) {
					if ( is_array( $result['error'] ) ) {
						$result['error'] = wp_json_encode( $result['error'] );
					}
					$error = $result['error'];
				} else {
					$error = __( 'The payment cancellation process has failed. Please try again.', 'woocommerce' );
				}
			}
		}
		if ( $error ) {
			$loggerRepository->createLogRecord(
				$paydockChargeId,
				'Cancel-authorised',
				'error',
				$error,
				LogRepository::ERROR
			);
			wp_send_json_error( [ 'message' => $error ] );
		}
	}

	public function refundProcess( $refund, $args ) {
		if ( ! empty( $args['from_webhook'] ) && true === $args['from_webhook'] ) {
			return;
		}

		$orderId = $args['order_id'];
		$amount  = $args['amount'];
		$order   = wc_get_order( $orderId );

		if ( ! in_array( $order->get_status(),
			[ 'paydock-paid', 'paydock-p-paid', 'paydock-p-refund', 'paydock-refunded' ] ) ) {
			return;
		}

		$loggerRepository = new LogRepository();

		$totalRefunded = 0;
		$refunds       = $order->get_refunds();
		foreach ( $refunds as $refund ) {
			$totalRefunded += $refund->get_amount();
		}
		$paydockChargeId = get_post_meta( $orderId, 'paydock_charge_id', true );
		$captureAmount   = get_post_meta( $orderId, 'capture_amount', true );
		if ( $captureAmount && $totalRefunded > $captureAmount ) {
			$totalRefunded = $captureAmount;
		}
		$result = SDKAdapterService::getInstance()->refunds( [ 'charge_id' => $paydockChargeId, 'amount' => $amount ] );
		if ( ! empty( $result['resource']['data']['status'] ) && in_array(
				$result['resource']['data']['status'],
				[ 'refunded', 'refund_requested' ]
			) ) {
			$newRefundedId = end( $result['resource']['data']['transactions'] )['_id'];
			if ( $captureAmount ) {
				$status = $totalRefunded < $captureAmount ? 'wc-paydock-p-refund' : 'wc-paydock-refunded';
			} else {
				$status = $totalRefunded < $order->get_total() ? 'wc-paydock-p-refund' : 'wc-paydock-refunded';
			}

			update_post_meta( $orderId, 'paydock_refunded_status', $status );
			$order->set_status( $status );
			$order->update_status(
				$status,
				__( 'The refund', 'woocommerce' ) . " {$amount} " . __( 'has been successfully.', 'woocommerce' )
			);
			$order->payment_complete();
			$order->save();
			update_post_meta( $orderId, 'api_refunded_id', $newRefundedId );
			$loggerRepository->createLogRecord( $newRefundedId, 'Refunded', $status, '', LogRepository::SUCCESS );
		} else {
			if ( ! empty( $result['error'] ) ) {
				if ( is_array( $result['error'] ) ) {
					$result['error'] = wp_json_encode( $result['error'] );
				}
				$loggerRepository->createLogRecord(
					$paydockChargeId,
					'Refund',
					'error',
					$result['error'],
					LogRepository::ERROR
				);
				throw new \Exception( esc_html( $result['error'] ) );
			} else {
				$error = __( 'The refund process has failed; please try again.', 'woocommerce' );
				$loggerRepository->createLogRecord( $paydockChargeId, 'Refunded', 'error', $error,
					LogRepository::ERROR );
				throw new \Exception( esc_html( $error ) );
			}
		}
	}

	public function afterRefundProcess( $orderId, $refundId ) {
		$paydockRefundedStatus = get_post_meta( $orderId, 'paydock_refunded_status', true );
		if ( $paydockRefundedStatus ) {
			$order = wc_get_order( $orderId );
			$order->update_status( $paydockRefundedStatus );
			delete_post_meta( $orderId, 'paydock_refunded_status' );
		}
	}

	public function webhook(): void {
		$input = json_decode( file_get_contents( 'php://input' ), true );

		if ( ( null === $input && json_last_error() !== JSON_ERROR_NONE ) || empty( $input['event'] ) ) {
			return;
		}

		( new LogRepository() )->createLogRecord(
			'',
			'Webhook',
			'Received',
			$input['event'],
			LogRepository::SUCCESS
		);

		$result = false;
		if ( ! empty( $input['data']['reference'] ) ) {
			switch ( strtoupper( $input['event'] ) ) {
				case NotificationEvents::TRANSACTION_SUCCESS()->name:
				case NotificationEvents::TRANSACTION_FAILURE()->name:
				case NotificationEvents::FRAUD_CHECK_IN_REVIEW()->name:
				case NotificationEvents::FRAUD_CHECK_IN_REVIEW_ASYNC_APPROVED()->name:
				case NotificationEvents::FRAUD_CHECK_TRANSACTION_IN_REVIEW_ASYNC_APPROVED()->name:
				case NotificationEvents::FRAUD_CHECK_SUCCESS()->name:
				case NotificationEvents::FRAUD_CHECK_TRANSACTION_IN_REVIEW_APPROVED()->name:
				case NotificationEvents::FRAUD_CHECK_FAILED()->name:
				case NotificationEvents::FRAUD_CHECK_TRANSACTION_IN_REVIEW_DECLINED()->name:
					$result = $this->webhookProcess( $input );
					break;
				case NotificationEvents::STANDALONE_FRAUD_CHECK_SUCCESS()->name:
				case NotificationEvents::STANDALONE_FRAUD_CHECK_FAILED()->name:
				case NotificationEvents::STANDALONE_FRAUD_CHECK_IN_REVIEW_APPROVED()->name:
				case NotificationEvents::STANDALONE_FRAUD_CHECK_IN_REVIEW_DECLINED()->name:
				case NotificationEvents::STANDALONE_FRAUD_CHECK_IN_REVIEW_ASYNC_APPROVED()->name:
				case NotificationEvents::STANDALONE_FRAUD_CHECK_IN_REVIEW_ASYNC_DECLINED()->name:
					$result = $this->fraudProcess( $input );
					break;
				case NotificationEvents::REFUND_SUCCESS()->name:
					$result = $this->refundSuccessProcess( $input );
					break;
				default:
					$result = false;
			}
		}

		echo $result ? 'Ok' : 'Fail';

		exit;
	}

	private function webhookProcess( array $input ): bool {
		$data = $input['data'];

		if ( strpos( $data['reference'], '_' ) === false ) {
			$orderId = (int) $data['reference'];
		} else {
			$referenceArray = explode( '_', $data['reference'] );
			$orderId        = (int) reset( $referenceArray );
		}

		$order = wc_get_order( $orderId );

		if ( false === $order || 'checkout-draft' === $order->get_status() ) {
			return false;
		}

		$chargeId        = $data['_id'] ?? '';
		$status          = ucfirst( strtolower( $data['status'] ?? 'undefined' ) );
		$operation       = ucfirst( strtolower( $data['type'] ?? 'undefined' ) );
		$isAuthorization = $data['authorization'] ?? 0;
		$orderTotal      = $order->get_total();

		switch ( strtoupper( $status ) ) {
			case ChargeStatuses::COMPLETE()->name:
				$captureAmount = wc_format_decimal( $data['transaction']['amount'] );
				$orderStatus   = $orderTotal > $captureAmount ? 'wc-paydock-p-paid' : 'wc-paydock-paid';
				update_post_meta( $orderId, 'capture_amount', $captureAmount );
				break;
			case ChargeStatuses::PENDING()->name:
			case ChargeStatuses::PRE_AUTHENTICATION_PENDING()->name:
				$orderStatus = $isAuthorization ? 'wc-paydock-authorize' : 'wc-paydock-pending';
				break;
			case ChargeStatuses::CANCELLED()->name:
				$orderStatus = 'wc-paydock-cancelled';
				break;
			case ChargeStatuses::REFUNDED()->name:
				$orderStatus = 'wc-paydock-refunded';
				break;
			case ChargeStatuses::REQUESTED()->name:
				$orderStatus = 'wc-paydock-requested';
				break;
			case ChargeStatuses::DECLINED()->name:
			case ChargeStatuses::FAILED()->name:
				$orderStatus = 'wc-paydock-failed';
				break;
			default:
				$orderStatus = $order->get_status();
		}

		$order->set_status( $orderStatus );
		$order->save();
		update_post_meta( $order->get_id(), 'paydock_charge_id', $chargeId );

		$loggerRepository = new LogRepository();
		$loggerRepository->createLogRecord(
			$chargeId,
			$operation,
			$orderStatus,
			'',
			in_array( $orderStatus,
				[ 'wc-paydock-paid', 'wc-paydock-p-paid', 'wc-paydock-authorize', 'wc-paydock-pending' ]
			) ? LogRepository::SUCCESS : LogRepository::DEFAULT
		);

		return true;
	}

	private function fraudProcess( array $input ): bool {
		$loggerRepository = new LogRepository();
		$data             = $input['data'];

		if ( strpos( $data['reference'], '_' ) === false ) {
			$orderId = (int) $data['reference'];
		} else {
			$referenceArray = explode( '_', $data['reference'] );
			$orderId        = (int) reset( $referenceArray );
		}

		$order       = wc_get_order( $orderId );
		$fraudId     = $data['_id'];
		$fraudStatus = $data['status'];

		$optionName = "paydock_fraud_{$orderId}";

		if ( 'complete' !== $fraudStatus ) {
			$operation = ucfirst( strtolower( $data['type'] ?? 'undefined' ) );
			$status    = 'wc-paydock-failed';

			delete_option( $optionName );
			$order->set_status( $status );
			$order->save();

			$loggerRepository->createLogRecord(
				$fraudId,
				$operation,
				$status,
				''
			);

			return true;
		}

		$options = get_option( $optionName );

		if ( false === $options || false === $order ) {
			return false;
		}

		$paymentSource = $data['customer']['payment_source'];
		if ( ! empty( $options['gateway_id'] ) ) {
			$paymentSource['gateway_id'] = $options['gateway_id'];
		}

		$chargeArgs = [
			'amount'          => (float) $order->get_total(),
			'reference'       => (string) $order->get_id(),
			'currency'        => strtoupper( $order->get_currency() ),
			'customer'        => [
				'first_name'     => $order->get_billing_first_name(),
				'last_name'      => $order->get_billing_last_name(),
				'email'          => $order->get_billing_email(),
				'phone'          => $order->get_billing_phone(),
				'payment_source' => $paymentSource,
			],
			'fraud_charge_id' => $fraudId,
			'capture'         => $options['capture'],
		];

		if ( ! empty( $options['charge3dsid'] ) ) {
			$chargeArgs['_3ds_charge_id'] = $options['charge3dsid'];
		}

		if ( ! empty( $options['_3ds'] ) ) {
			$chargeArgs['_3ds'] = $options['_3ds'];
		}

		if ( ! empty( $options['cvv'] ) ) {
			$chargeArgs['customer']['payment_source']['card_ccv'] = $options['cvv'];
		}

		delete_option( $optionName );

		$response = SDKAdapterService::getInstance()->createCharge( $chargeArgs );
		$chargeId = ! empty( $response['resource']['data']['_id'] ) ? $response['resource']['data']['_id'] : '';

		if ( ! empty( $response['error'] ) ) {
			$message = SDKAdapterService::getInstance()->errorMessageToString( $response );
			$loggerRepository->createLogRecord(
				$chargeId ?? '',
				'Charge',
				'UnfulfilledCondition',
				__( 'Can\'t charge.', 'paydock' ) . $message,
				LogRepository::ERROR
			);

			return false;
		}

		if ( ! empty( $options['_3ds'] ) ) {
			$attachResponse = SDKAdapterService::getInstance()->fraudAttach( $chargeId,
				[ 'fraud_charge_id' => $fraudId ] );
			if ( ! empty( $attachResponse['error'] ) ) {
				$message = SDKAdapterService::getInstance()->errorMessageToString( $attachResponse );
				$loggerRepository->createLogRecord(
					$chargeId ?? '',
					'Fraud Attach',
					'UnfulfilledCondition',
					__( 'Can\'t fraud attach.', 'paydock' ) . $message,
					LogRepository::ERROR
				);

				return false;
			}
		}

		$status          = ucfirst( strtolower( $response['resource']['data']['status'] ?? 'undefined' ) );
		$operation       = ucfirst( strtolower( $response['resource']['data']['type'] ?? 'undefined' ) );
		$isAuthorization = $response['resource']['data']['authorization'] ?? 0;
		$isCompleted     = false;
		$markAsSuccess   = false;

		if ( $isAuthorization && in_array( $status, [ 'Pending', 'Pre_authentication_pending' ] ) ) {
			$status = 'wc-paydock-authorize';
		} else {
			$markAsSuccess = true;
			$isCompleted   = 'Complete' === $status;
			$status        = $isCompleted ? 'wc-paydock-paid' : 'wc-paydock-pending';
		}

		$order->set_status( $status );
		$order->save();
		update_post_meta( $order->get_id(), 'paydock_charge_id', $chargeId );

		$loggerRepository->createLogRecord(
			$chargeId,
			$operation,
			$status,
			'',
			$markAsSuccess ? LogRepository::SUCCESS : LogRepository::DEFAULT
		);

		return true;
	}

	private function refundSuccessProcess( array $input ): bool {
		sleep( 2 );

		$data = $input['data'];

		if ( empty( $data['transaction'] ) ) {
			return false;
		}

		if ( strpos( $data['reference'], '_' ) === false ) {
			$orderId = (int) $data['reference'];
		} else {
			$referenceArray = explode( '_', $data['reference'] );
			$orderId        = (int) reset( $referenceArray );
		}

		$order = wc_get_order( $orderId );

		if ( false === $order || get_post_meta( $orderId, 'api_refunded_id', true ) === $data['transaction']['_id'] ) {
			return false;
		}

		$captureAmount = get_post_meta( $order->get_id(), 'capture_amount', true );
		$orderTotal    = $order->get_total();
		if ( $captureAmount && $orderTotal > $captureAmount ) {
			$orderTotal = $captureAmount;
		}

		$chargeId     = $data['_id'] ?? '';
		$status       = ucfirst( strtolower( $data['status'] ?? 'undefined' ) );
		$operation    = ucfirst( strtolower( $data['type'] ?? 'undefined' ) );
		$refundAmount = wc_format_decimal( $data['transaction']['amount'] );

		switch ( strtoupper( $status ) ) {
			case ChargeStatuses::REFUNDED()->name:
			case ChargeStatuses::REFUND_REQUESTED()->name:
				if ( $refundAmount < $orderTotal ) {
					$orderStatus = 'wc-paydock-p-refund';
				} else {
					$orderStatus = 'wc-paydock-refunded';
				}
				update_post_meta( $orderId, 'paydock_refunded_status', $orderStatus );
				break;
			default:
				$orderStatus = $order->get_status();
		}

		$order->set_status( $orderStatus );
		$order->update_status(
			$orderStatus,
			__( 'The refund', 'woocommerce' ) . " {$refundAmount} " . __( 'has been successfully.', 'woocommerce' )
		);
		$order->payment_complete();
		$order->save();

		$result = wc_create_refund( [
			'amount'         => $refundAmount,
			'reason'         => __( 'The refund', 'woocommerce' ) . " {$refundAmount} " . __(
					'has been successfully.',
					'woocommerce'
				),
			'order_id'       => $orderId,
			'refund_payment' => false,
			'from_webhook'   => true,
		] );

		$loggerRepository = new LogRepository();
		$loggerRepository->createLogRecord(
			$chargeId,
			$operation,
			$orderStatus,
			$result instanceof \WP_Error ? $result->get_error_message() : '',
			in_array( $orderStatus, [ 'wc-paydock-paid', 'wc-paydock-authorize', 'wc-paydock-pending' ]
			) ? LogRepository::SUCCESS : LogRepository::DEFAULT
		);

		return true;
	}
}
