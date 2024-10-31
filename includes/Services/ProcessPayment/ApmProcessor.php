<?php

namespace Paydock\Services\ProcessPayment;

use Exception;
use Paydock\Enums\OtherPaymentMethods;
use Paydock\Helpers\ArgsForProcessPayment;
use Paydock\Helpers\ShippingHelper;
use Paydock\Repositories\LogRepository;
use Paydock\Repositories\UserCustomerRepository;
use Paydock\Services\SDKAdapterService;

class ApmProcessor {
	const CHARGE_METHOD = 'charge';
	const CUSTOMER_CHARGE_METHOD = 'customerCharge';
	const FRAUD_CHARGE_METHOD = 'fraudCharge';
	const ALLOWED_METHODS = [
		self::CHARGE_METHOD,
		self::CUSTOMER_CHARGE_METHOD,
		self::FRAUD_CHARGE_METHOD,
	];

	protected $args = [];
	protected $tokenData = [];
	protected $order = false;
	private $runMethod;
	private $logger;

	public function __construct( array $args = [] ) {
		$this->logger = new LogRepository();
		$this->args   = ArgsForProcessPayment::prepare( $args );
	}

	public function run( $order ): array {
		$this->order = $order;
		$this->setRunMethod();

		if ( ! in_array( $this->runMethod, self::ALLOWED_METHODS ) ) {
			throw new Exception( esc_html( __( 'Undefined run method', 'paydock' ) ) );
		}

		return call_user_func( [ $this, $this->runMethod ] );
	}

	private function setRunMethod() {
		switch ( true ) {
			case $this->args['fraud']:
				$this->runMethod = self::FRAUD_CHARGE_METHOD;
				break;
			case $this->args['apmsavecard']:
				$this->runMethod = self::CUSTOMER_CHARGE_METHOD;
				break;
			default:
				$this->runMethod = self::CHARGE_METHOD;
		}
	}

	public function getRunMethod() {
		return $this->runMethod;
	}

	private function charge(): array {
		$chargeArgs = [
			'amount'    => $this->args['amount'],
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'token'     => $this->args['paymentsourcetoken'],
			'capture'   => strtolower( OtherPaymentMethods::AFTERPAY()->name ) === $this->args['gatewaytype'] ? true : $this->args['directcharge'],
			'reference' => (string) $this->order->get_id(),
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => $this->getAdditionalFields( 'amount' )
			],
			'shipping'  => $this->getShippingFields(),
			'items'     => $this->getOrderItems(),
		];

		return SDKAdapterService::getInstance()->createCharge( $chargeArgs );
	}

	private function customerCharge(): array {
		$customerArgs = array_merge( [
			'first_name'     => $this->order->get_billing_first_name(),
			'last_name'      => $this->order->get_billing_last_name(),
			'email'          => $this->order->get_billing_email(),
			'phone'          => $this->order->get_billing_phone(),
			'token'          => $this->args['paymentsourcetoken'],
			'payment_source' => $this->getAdditionalFields( 'amount' )
		], $this->getAdditionalFields( 'amount' ) );

		$customer = SDKAdapterService::getInstance()->createCustomer( $customerArgs );

		if ( ! empty( $customer['error'] ) || empty( $customer['resource']['data']['_id'] ) ) {
			$message = ! empty( $customer['error']['message'] ) ? ' ' . $customer['error']['message'] : '';

			$this->logger->createLogRecord(
				'',
				'Create customer',
				'error',
				$message,
				LogRepository::ERROR
			);
			throw new Exception( esc_html( __( 'The Paydock customer could not be created successfully.', 'paydock' ) ) );
		}

		$this->logger->createLogRecord(
			$customer['resource']['data']['_id'],
			'Create customer',
			'Success',
			'',
			LogRepository::SUCCESS
		);

		if ( $this->args['apmsavecardchecked'] ) {
			( new UserCustomerRepository() )->saveUserCustomer( $customer['resource']['data'] );
		}

		$customer_id = $customer['resource']['data']['_id'];

		return SDKAdapterService::getInstance()->createCharge( [
			'amount'      => $this->args['amount'],
			'currency'    => strtoupper( get_woocommerce_currency() ),
			'customer_id' => $customer_id,
			'reference'   => (string) $this->order->get_id(),
			'capture'     => strtolower( OtherPaymentMethods::AFTERPAY()->name ) === $this->args['gatewaytype'] ? true : $this->args['directcharge'],
			'shipping'    => $this->getShippingFields(),
			'items'       => $this->getOrderItems()
		] );
	}

	private function fraudCharge(): array {
		return SDKAdapterService::getInstance()->createCharge( [
			'amount'    => $this->args['amount'],
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'capture'   => strtolower( OtherPaymentMethods::AFTERPAY()->name ) === $this->args['gatewaytype'] ? true : $this->args['directcharge'],
			'token'     => $this->args['paymentsourcetoken'],
			'reference' => (string) $this->order->get_id(),
			'fraud'     => [
				'service_id' => $this->args['fraudserviceid'] ?? '',
				'data'       => $this->getAdditionalFields(),
			],
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => $this->getAdditionalFields( 'amount' )
			],
			'shipping'  => $this->getShippingFields(),
			'items'     => $this->getOrderItems()
		] );
	}

	protected function getAdditionalFields( $exclude = [] ): array {
		if ( ! $this->order ) {
			return [];
		}

		$address1 = $this->order->get_billing_address_1();
		$address2 = $this->order->get_billing_address_2();

		$result = [
			'amount'           => (float) $this->order->get_total(),
			'address_country'  => $this->order->get_billing_country(),
			'address_postcode' => $this->order->get_billing_postcode(),
			'address_city'     => $this->order->get_billing_city(),
			'address_state'    => $this->order->get_billing_state(),
			'address_line1'    => $address1,
			'address_line2'    => empty( trim( $address2 ) ) ? $address1 : $address2,
		];

		if ( ! empty( $exclude ) ) {
			if ( ! is_array( $exclude ) ) {
				$exclude = [ $exclude ];
			}

			$result = array_diff_key( $result, array_flip( $exclude ) );
		}

		return $result;
	}

	protected function getShippingFields( $exclude = [] ): array {
		if ( ! $this->order ) {
			return [];
		}

		$orderData = $this->order->get_data();

		$result = [
			'amount'   => round( $orderData['shipping_total'], 2 ),
			'currency' => $orderData['currency'],
			'contact'  => [
				'first_name' => $this->order->get_shipping_first_name(),
				'last_name'  => $this->order->get_shipping_last_name(),
				'phone'      => $this->order->get_shipping_phone()
			]
		];

		$location       = false;
		$shippingMethod = reset( $this->order->get_shipping_methods() );
		if ( $shippingMethod && 'pickup_location' === $shippingMethod->get_method_id() ) {
			$metaDatas = $shippingMethod->get_meta_data();
			foreach ( $metaDatas as $metaData ) {
				$metaDataArray = $metaData->get_data();
				if ( 'pickup_location' === $metaDataArray['key'] ) {
					$location = ShippingHelper::getPickupLocationByName( $metaDataArray['value'] );
					break;
				}
			}

			if ( $location ) {
				$result['address_line1']    = $location['address']['address_1'];
				$result['address_city']     = $location['address']['city'];
				$result['address_state']    = $location['address']['state'];
				$result['address_country']  = $location['address']['country'];
				$result['address_postcode'] = $location['address']['postcode'];
			}
		}

		if ( false === $location ) {
			$address1 = $this->order->get_shipping_address_1();
			$address2 = $this->order->get_shipping_address_2();

			$result['address_line1']    = $address1;
			$result['address_line2']    = empty( trim( $address2 ) ) ? $address1 : $address2;
			$result['address_city']     = $this->order->get_shipping_city();
			$result['address_state']    = $this->order->get_shipping_state();
			$result['address_country']  = $this->order->get_shipping_country();
			$result['address_postcode'] = $this->order->get_shipping_postcode();
		}

		if ( ! empty( $exclude ) ) {
			if ( ! is_array( $exclude ) ) {
				$exclude = [ $exclude ];
			}

			$result = array_diff_key( $result, array_flip( $exclude ) );
		}

		return $result;
	}

	protected function getOrderItems(): array {
		$result = [];

		if ( ! $this->order ) {
			return $result;
		}

		$items = $this->order->get_items();
		foreach ( $items as $item ) {
			$product   = $item->get_product();
			$productId = $item->get_product_id();
			$image     = wp_get_attachment_image_url( get_post_thumbnail_id( $productId ), 'full' );
			$itemData  = [
				'amount'   => $product->get_price(),
				'name'     => $item->get_name(),
				'type'     => $item->get_type(),
				'quantity' => $item->get_quantity(),
				'item_uri' => get_permalink( $productId )
			];

			if ( ! empty( $image ) ) {
				$itemData['image_uri'] = $image;
			}

			$result[] = $itemData;
		}

		return $result;
	}
}
