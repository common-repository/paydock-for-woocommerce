<?php

namespace Paydock\Controllers\Admin;

use Paydock\Enums\WalletPaymentMethods;
use Paydock\Helpers\ShippingHelper;
use Paydock\Repositories\LogRepository;
use Paydock\Services\SDKAdapterService;
use Paydock\Services\SettingsService;
use WP_REST_Request;

class WidgetController {
	public function createWalletCharge( WP_REST_Request $request ) {
		$settings = SettingsService::getInstance();
		$order    = wc_get_order( $request['order_id'] );

		$loggerRepository = new LogRepository();

		$request    = $request->get_json_params();
		$result     = [];
		$isAfterPay = false;

		switch ( $request['type'] ) {
			case 'afterpay':
				$isAfterPay = true;
				$payment    = WalletPaymentMethods::AFTERPAY();
				break;
			case 'apple-pay':
				$payment = WalletPaymentMethods::APPLE_PAY();
				break;
			case 'google-pay':
				$payment = WalletPaymentMethods::GOOGLE_PAY();
				break;
			case 'pay-pal':
				$payment = WalletPaymentMethods::PAY_PAL_SMART_BUTTON();
				break;
		}

		$key = strtolower( $payment->name );
		if ( $settings->isWalletEnabled( $payment ) ) {
			$reference = $request['order_id'];

			$items = [];
			foreach ( $request['items'] as $item ) {
				$image = wp_get_attachment_image_url( get_post_thumbnail_id( $item['id'] ), 'full' );

				$itemData = [
					'amount'   => round( $item['prices']['price'] / 100, 2 ),
					'name'     => $item['name'],
					'type'     => $item['type'],
					'quantity' => $item['quantity'],
					'item_uri' => $item['permalink'],
				];

				if ( ! empty( $image ) ) {
					$itemData['image_uri'] = $image;
				}

				$items[] = $itemData;
			}
			$billingAdress   = $request['address'];
			$shippingAddress = $request['shipping_address'];

			foreach ( $shippingAddress as $key => $value ) {
				if ( empty( trim( $value ) ) ) {
					$shippingAddress[ $key ] = $billingAdress[ $key ];
				}
			}

			$chargeRequest = [
				'amount'    => round( $request['total']['total_price'] / 100, 2 ),
				'currency'  => $request['total']['currency_code'],
				'reference' => (string) $reference,
				'customer'  => [
					'first_name'     => $billingAdress['first_name'],
					'last_name'      => $billingAdress['last_name'],
					'email'          => $billingAdress['email'],
					'payment_source' => [
						'gateway_id'       => $settings->getWalletGatewayId( $payment ),
						'address_line1'    => $billingAdress['address_1'],
						'address_city'     => $billingAdress['city'],
						'address_state'    => $billingAdress['state'],
						'address_country'  => $billingAdress['country'],
						'address_postcode' => $billingAdress['postcode'],
					],
				],
				'meta'      => [
					'store_name' => get_bloginfo( 'name' ),
				],
				'items'     => $items,
				'shipping'  => [
					'amount'           => round( $request['total']['total_shipping'] / 100, 2 ),
					'currency'         => $request['total']['currency_code'],
					'address_line1'    => $shippingAddress['address_1'],
					'address_city'     => $shippingAddress['city'],
					'address_state'    => $shippingAddress['state'],
					'address_country'  => $shippingAddress['country'],
					'address_postcode' => $shippingAddress['postcode'],
					'contact'          => [
						'first_name' => $shippingAddress['first_name'],
						'last_name'  => $shippingAddress['last_name'],
					],
				],
			];

			if ( ! empty( $shippingAddress['phone'] ) ) {
				$chargeRequest['shipping']['contact']['phone'] = $shippingAddress['phone'];
			}

			if ( ! empty( $billingAdress['phone'] ) ) {
				$chargeRequest['customer']['phone'] = $billingAdress['phone'];
			}
			if ( ! empty( $billingAdress['address_2'] ) ) {
				$chargeRequest['customer']['payment_source']['address_line2'] = $billingAdress['address_2'];
			}

			if ( ! empty( $shippingAddress['address_2'] ) ) {
				$chargeRequest['shipping']['address_line2'] = $shippingAddress['address_2'];
			}

			if ( ! empty( $request['shipping_rates'] ) ) {
				$shippingRates = reset( $request['shipping_rates'] );
				foreach ( $shippingRates['shipping_rates'] as $shippingRate ) {
					if ( $shippingRate['selected'] ) {
						if ( 'pickup_location' === $shippingRate['method_id'] ) {
							$location = ShippingHelper::getPickupLocationByKey( $shippingRate['rate_id'] );
							if ( false !== $location ) {
								$chargeRequest['shipping']['address_line1']    = $location['address']['address_1'];
								$chargeRequest['shipping']['address_city']     = $location['address']['city'];
								$chargeRequest['shipping']['address_state']    = $location['address']['state'];
								$chargeRequest['shipping']['address_country']  = $location['address']['country'];
								$chargeRequest['shipping']['address_postcode'] = $location['address']['postcode'];
								unset( $chargeRequest['shipping']['address_line2'] );
							}
						}
						break;
					}
				}
			}

			if ( WalletPaymentMethods::APPLE_PAY()->name === $payment->name ) {
				$chargeRequest['customer']['payment_source']['wallet_type'] = 'apple';
			}

			$fraudService = $settings->getWalletFraudServiceId( $payment );
			if (
				$settings->isWalletFraud( $payment )
				&& ! empty( $fraudService )
			) {
				$chargeRequest['fraud'] = [
					'service_id' => $fraudService,
					'data'       => [],
				];
			}

			if ( $isAfterPay ) {
				$chargeRequest['meta']['success_url'] = $order->get_checkout_order_received_url();
				$chargeRequest['meta']['error_url']   = add_query_arg( 'afterpay-error', 'true',
					$order->get_checkout_order_received_url() );
			}

			$result = SDKAdapterService::getInstance()
			                           ->createWalletCharge( $chargeRequest,
				                           $settings->isWalletDirectCharge( $payment ) );

			$result['county'] = $billingAdress['country'] ?? '';

			if ( WalletPaymentMethods::PAY_PAL_SMART_BUTTON()->name === $payment->name ) {
				$result['pay_later'] = 'yes' === $settings->isPayPallSmartButtonPayLater();
			}

			if ( ! empty( $result[ $key ]['error'] ) ) {
				$operation = ucfirst( strtolower( $result[ $key ]['resource']['type'] ?? 'undefined' ) );
				$status    = $result[ $key ]['error']['message'] ?? 'empty status';
				$message   = $result[ $key ]['error']['details'][0]['gateway_specific_description'] ?? 'empty message';

				$loggerRepository->createLogRecord( '', $operation, $status, $message, LogRepository::ERROR );
			}
		}

		return rest_ensure_response( $result );
	}
}
