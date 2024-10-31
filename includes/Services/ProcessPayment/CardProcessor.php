<?php

namespace Paydock\Services\ProcessPayment;

use Exception;
use Paydock\Enums\DSTypes;
use Paydock\Enums\FraudTypes;
use Paydock\Enums\SaveCardOptions;
use Paydock\Helpers\ArgsForProcessPayment;
use Paydock\Helpers\VaultTokenHelper;
use Paydock\Repositories\LogRepository;
use Paydock\Repositories\UserTokenRepository;
use Paydock\Services\SDKAdapterService;

class CardProcessor {
	const FRAUD_3DS_CHARGE_METHOD = 'fraud3DsCharge';
	const THREE_DS_CHARGE_METHOD = 'threeDsCharge';
	const FRAUD_CHARGE_METHOD = 'fraudCharge';
	const FRAUD_IN_BUILD_CHARGE_METHOD = 'fraudInBuildCharge';
	const CUSTOMER_CHARGE_METHOD = 'customerCharge';
	const CHARGE_METHOD = 'charge';
	const ALLOWED_METHODS = [
		self::FRAUD_3DS_CHARGE_METHOD,
		self::THREE_DS_CHARGE_METHOD,
		self::FRAUD_CHARGE_METHOD,
		self::CUSTOMER_CHARGE_METHOD,
		self::CHARGE_METHOD,
	];

	protected $vaultTokenHelper;
	protected $userTokenRepository;
	protected $args = [];
	protected $tokenData = [];
	protected $order = false;
	protected $logger;
	private $runMethod;
	private $customerId = null;

	public function __construct( array $args = [] ) {
		$this->logger           = new LogRepository();
		$this->args             = ArgsForProcessPayment::prepare( $args );
		$this->vaultTokenHelper = new VaultTokenHelper( $this->args );

		if ( $this->args['isuserloggedin'] ) {
			$this->userTokenRepository = new UserTokenRepository();
		}
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
			case (
				$this->args['cardsavecard']
				&& $this->isSavedVaultTokenWithCustomer()
			):
				$this->runMethod = self::CUSTOMER_CHARGE_METHOD;
				break;
			case ! empty( $this->args['card3ds'] ) && ! empty( $this->args['cardfraud'] ):
				$this->runMethod = self::FRAUD_3DS_CHARGE_METHOD;
				break;
			case ! empty( $this->args['card3ds'] ):
				$this->runMethod = self::THREE_DS_CHARGE_METHOD;
				break;
			case ! empty( $this->args['cardfraud'] ):
				$this->runMethod = self::FRAUD_CHARGE_METHOD;
				break;
			case (
				$this->args['cardsavecard']
				&& SaveCardOptions::VAULT()->name !== $this->args['cardsavecardoption']
				&& $this->args['cardsavecardchecked']
			):
				$this->runMethod = self::CUSTOMER_CHARGE_METHOD;
				break;
			default:
				$this->runMethod = self::CHARGE_METHOD;
		}
	}

	private function isSavedVaultTokenWithCustomer(): bool {
		if ( ! $this->args['isuserloggedin'] || empty( $this->args['selectedtoken'] ) ) {
			return false;
		}

		$vaultToken = $this->userTokenRepository->getUserToken( $this->args['selectedtoken'] );
		if ( empty( $vaultToken ) || empty( $vaultToken['customer_id'] ) ) {
			return false;
		}

		$this->customerId = $vaultToken['customer_id'];

		return true;
	}

	public function getRunMethod() {
		return $this->runMethod;
	}

	public function getStandalone3dsToken(): string {
		$vaultToken = $this->getVaultToken();

		$paymentSource = [
			'vault_token' => $vaultToken,
		];

		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
		}

		$args = [
			'amount'    => $this->args['amount'],
			'reference' => '',
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'customer'  => [
				'first_name'     => $this->args['first_name'],
				'last_name'      => $this->args['last_name'],
				'email'          => $this->args['email'],
				'payment_source' => $paymentSource,
			],
			'_3ds'      => [
				'service_id'     => $this->args['card3dsserviceid'] ?? '',
				'authentication' => [
					'type' => '01',
					'date' => gmdate( 'Y-m-d\TH:i:s.000\Z' ),
				],
			],
		];

		if ( ! empty( $this->args['phone'] ) ) {
			$args['customer']['phone'] = $this->args['phone'];
		}

		$threeDsCharge = SDKAdapterService::getInstance()->standalone3DsCharge( $args );

		if ( ! empty( $threeDsCharge['error'] ) || empty( $threeDsCharge['resource']['data']['_3ds']['token'] ) ) {
			$message = ! empty( $threeDsCharge['error']['message'] ) ? ' ' . $threeDsCharge['error']['message'] : '';
			$this->logger->createLogRecord(
				'',
				'3DS Charge',
				'error',
				$message,
				LogRepository::ERROR
			);
			throw new Exception( esc_html( __( 'The 3ds charge could not be created successfully.', 'paydock' ) ) );
		}

		$this->logger->createLogRecord(
			'',
			'3DS Charge',
			'Success',
			'',
			LogRepository::SUCCESS
		);

		return $threeDsCharge['resource']['data']['_3ds']['token'];
	}

	public function getVaultToken(): string {
		$token = $this->vaultTokenHelper->get( $this->getAdditionalFields( 'amount' ) );

		if ( ! empty( $token ) ) {
			$this->args['selectedtoken'] = $token;
		}

		return $token;
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
			'address_line2'    => empty( $address2 ) ? $address1 : $address2,
		];

		if ( ! empty( $exclude ) ) {
			if ( ! is_array( $exclude ) ) {
				$exclude = [ $exclude ];
			}

			$result = array_diff_key( $result, array_flip( $exclude ) );
		}

		return $result;
	}

	private function fraud3DsCharge(): array {
		switch ( true ) {
			case ( DSTypes::IN_BUILD()->name === $this->args['card3ds'] && FraudTypes::IN_BUILD()->name === $this->args['cardfraud'] ):
				$result = $this->fraud3DsInBuildCharge();
				break;
			case ( DSTypes::STANDALONE()->name === $this->args['card3ds'] && FraudTypes::STANDALONE()->name === $this->args['cardfraud'] ):
				$result = $this->fraud3DsStandaloneCharge();
				break;
			case ( DSTypes::IN_BUILD()->name === $this->args['card3ds'] && FraudTypes::STANDALONE()->name === $this->args['cardfraud'] ):
				$result = $this->fraudStandalone3DsInBuildCharge();
				break;
			case ( DSTypes::STANDALONE()->name === $this->args['card3ds'] && FraudTypes::IN_BUILD()->name === $this->args['cardfraud'] ):
				$result = $this->fraudInBuild3DsStandaloneCharge();
				break;
			default:
				$result = [ 'error' => [ 'message' => 'In-built fraud & 3ds error' ] ];
		}

		return $result;
	}

	private function fraud3DsInBuildCharge(): array {
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
		}

		$chargeArgs = [
			'amount'    => $this->args['amount'],
			'reference' => (string) $this->order->get_id(),
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'_3ds'      => [
				'id'         => $this->args['charge3dsid'] ?? '',
				'service_id' => $this->args['card3dsserviceid'] ?? '',
			],
			'fraud'     => [
				'service_id' => $this->args['cardfraudserviceid'] ?? '',
				'data'       => $this->getAdditionalFields(),
			],
			'capture'   => $this->args['carddirectcharge'],
		];

		if ( ! empty( $this->args['cvv'] ) ) {
			$chargeArgs['customer']['payment_source']['card_ccv'] = $this->args['cvv'];
		}

		return SDKAdapterService::getInstance()->createCharge( $chargeArgs );
	}

	private function fraud3DsStandaloneCharge(): array {
		$options    = [
			'method'      => __FUNCTION__,
			'capture'     => $this->args['carddirectcharge'],
			'charge3dsid' => $this->args['charge3dsid'],
		];
		$vaultToken = $this->getVaultToken();

		$paymentSource = [ 'vault_token' => $vaultToken ];
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
			$options['gateway_id']       = $this->args['gatewayid'];
		}

		if ( ! empty( $this->args['cvv'] ) ) {
			$paymentSource['card_ccv'] = $this->args['cvv'];
			$options['ccv']            = $this->args['cvv'];
		}

		$response = SDKAdapterService::getInstance()->standaloneFraudCharge( [
			'amount'    => $this->args['amount'],
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'reference' => (string) $this->order->get_id(),
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'fraud'     => [
				'service_id' => $this->args['cardfraudserviceid'] ?? '',
				'data'       => array_merge( [
					'first_name' => $this->order->get_billing_first_name(),
					'last_name'  => $this->order->get_billing_last_name(),
					'email'      => $this->order->get_billing_email(),
					'phone'      => $this->order->get_billing_phone(),
				], $this->getAdditionalFields( 'amount' ) ),
			],
		] );

		if ( empty( $response['error'] ) && ! empty( $response['resource']['data']['_id'] ) ) {
			update_option( 'paydock_fraud_' . (string) $this->order->get_id(), $options );
		}

		return $response;
	}

	private function fraudStandalone3DsInBuildCharge(): array {
		$options    = [
			'method'  => __FUNCTION__,
			'capture' => $this->args['carddirectcharge'],
			'_3ds'    => [
				'id'         => $this->args['charge3dsid'] ?? '',
				'service_id' => $this->args['card3dsserviceid'] ?? '',
			],
		];
		$vaultToken = $this->getVaultToken();

		$paymentSource = [ 'vault_token' => $vaultToken ];
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
			$options['gateway_id']       = $this->args['gatewayid'];
		}

		if ( ! empty( $this->args['cvv'] ) ) {
			$paymentSource['card_ccv'] = $this->args['cvv'];
			$options['ccv']            = $this->args['cvv'];
		}

		$response = SDKAdapterService::getInstance()->standaloneFraudCharge( [
			'amount'    => $this->args['amount'],
			'reference' => (string) $this->order->get_id(),
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'fraud'     => [
				'service_id' => $this->args['cardfraudserviceid'],
				'data'       => $this->getAdditionalFields(),
			],
		] );

		if ( empty( $response['error'] ) && ! empty( $response['resource']['data']['_id'] ) ) {
			update_option( 'paydock_fraud_' . (string) $this->order->get_id(), $options );
		}

		return $response;
	}

	private function fraudInBuild3DsStandaloneCharge(): array {
		$vaultToken = $this->getVaultToken();

		$paymentSource = [ 'vault_token' => $vaultToken ];
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
		}

		$chargeArgs = [
			'amount'         => $this->args['amount'],
			'reference'      => (string) $this->order->get_id(),
			'currency'       => strtoupper( get_woocommerce_currency() ),
			'customer'       => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'_3ds_charge_id' => $this->args['charge3dsid'],
			'fraud'          => [
				'service_id' => $this->args['cardfraudserviceid'] ?? '',
				'data'       => $this->getAdditionalFields(),
			],
			'capture'        => $this->args['carddirectcharge'],
		];

		if ( ! empty( $this->args['cvv'] ) ) {
			$chargeArgs['customer']['payment_source']['card_ccv'] = $this->args['cvv'];
		}

		return SDKAdapterService::getInstance()->createCharge( $chargeArgs );
	}

	private function threeDsCharge(): array {
		if ( DSTypes::IN_BUILD()->name === $this->args['card3ds'] ) {
			return $this->threeDsInBuildCharge();
		}

		return $this->threeDsStandaloneCharge();
	}

	private function threeDsInBuildCharge(): array {
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
		}

		$chargeArgs = [
			'amount'    => $this->args['amount'],
			'reference' => (string) $this->order->get_id(),
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'_3ds'      => [
				'id'         => $this->args['charge3dsid'] ?? '',
				'service_id' => $this->args['card3dsserviceid'] ?? '',
			],
			'capture'   => $this->args['carddirectcharge'],
		];

		if ( ! empty( $this->args['cvv'] ) ) {
			$chargeArgs['customer']['payment_source']['card_ccv'] = $this->args['cvv'];
		}

		return SDKAdapterService::getInstance()->createCharge( $chargeArgs );
	}

	private function threeDsStandaloneCharge(): array {
		$vaultToken = $this->getVaultToken();

		$paymentSource = [ 'vault_token' => $vaultToken ];
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
		}

		$chargeArgs = [
			'amount'         => $this->args['amount'],
			'reference'      => (string) $this->order->get_id(),
			'currency'       => strtoupper( get_woocommerce_currency() ),
			'customer'       => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'_3ds_charge_id' => $this->args['charge3dsid'],
			'capture'        => $this->args['carddirectcharge'],
		];

		if ( ! empty( $this->args['cvv'] ) ) {
			$chargeArgs['customer']['payment_source']['card_ccv'] = $this->args['cvv'];
		}

		return SDKAdapterService::getInstance()->createCharge( $chargeArgs );
	}

	private function fraudCharge(): array {
		if ( FraudTypes::IN_BUILD()->name === $this->args['cardfraud'] ) {
			return $this->fraudInBuildCharge();
		}

		return $this->fraudStandaloneCharge();
	}

	private function fraudInBuildCharge(): array {
		$this->runMethod = self::FRAUD_IN_BUILD_CHARGE_METHOD;

		$vaultToken = $this->getVaultToken();

		$paymentSource = [ 'vault_token' => $vaultToken ];
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
		}

		$address1 = $this->order->get_billing_address_1();
		$address2 = $this->order->get_billing_address_2();

		$chargeArgs = [
			'amount'    => $this->args['amount'],
			'reference' => (string) $this->order->get_id(),
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'fraud'     => [
				'service_id' => $this->args['cardfraudserviceid'] ?? '',
				'data'       => [
					'transaction' => [
						'billing' => [
							'customerEmailAddress' => $this->order->get_billing_email(),
							'shippingFirstName'    => $this->order->get_billing_first_name(),
							'shippingLastName'     => $this->order->get_billing_last_name(),
							'shippingAddress1'     => $address1,
							'shippingAddress2'     => empty( $address2 ) ? $address1 : $address2,
							'shippingCity'         => $this->order->get_billing_city(),
							'shippingState'        => $this->order->get_billing_state(),
							'shippingPostcode'     => $this->order->get_billing_postcode(),
							'shippingCountry'      => $this->order->get_billing_country(),
							'shippingPhone'        => $this->order->get_billing_phone(),
							'shippingEmail'        => $this->order->get_billing_email(),
						],
					],
				],
			],
			'capture'   => $this->args['carddirectcharge'],
		];

		if ( ! empty( $this->args['cvv'] ) ) {
			$chargeArgs['customer']['payment_source']['card_ccv'] = $this->args['cvv'];
		}

		return SDKAdapterService::getInstance()->createCharge( $chargeArgs );
	}

	private function fraudStandaloneCharge(): array {
		$options    = [
			'method'  => __FUNCTION__,
			'capture' => $this->args['carddirectcharge'],
		];
		$vaultToken = $this->getVaultToken();

		$paymentSource = [ 'vault_token' => $vaultToken ];
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
			$options['gateway_id']       = $this->args['gatewayid'];
		}

		if ( ! empty( $this->args['cvv'] ) ) {
			$paymentSource['card_ccv'] = $this->args['cvv'];
			$options['ccv']            = $this->args['cvv'];
		}

		$response = SDKAdapterService::getInstance()->standaloneFraudCharge( [
			'capture'   => $this->args['carddirectcharge'],
			'amount'    => $this->args['amount'],
			'reference' => (string) $this->order->get_id(),
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'fraud'     => [
				'service_id' => $this->args['cardfraudserviceid'],
				'data'       => $this->getAdditionalFields(),
			],
		] );

		if ( empty( $response['error'] ) && ! empty( $response['resource']['data']['_id'] ) ) {
			update_option( 'paydock_fraud_' . (string) $this->order->get_id(), $options );
		}

		return $response;
	}

	private function customerCharge(): array {
		if ( null === $this->customerId ) {
			$vaultToken = $this->getVaultToken();

			$customerArgs = array_merge( [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => [
					'amount'      => $this->args['amount'],
					'vault_token' => $vaultToken,
				],
			], $this->getAdditionalFields( 'amount' ) );

			if ( SaveCardOptions::WITH_GATEWAY()->name === $this->args['cardsavecardoption'] && ! empty( $this->args['gatewayid'] ) ) {
				$customerArgs['payment_source']['gateway_id'] = $this->args['gatewayid'];
			}

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

			if ( $this->vaultTokenHelper->shouldSaveVaultToken() && in_array( $this->args['cardsavecardoption'], [
					SaveCardOptions::WITH_GATEWAY()->name,
					SaveCardOptions::WITHOUT_GATEWAY()->name,
				] ) ) {
				$this->userTokenRepository->updateUserToken( $this->args['selectedtoken'], [
					'customer_id' => $customer['resource']['data']['_id'],
				] );
			}

			$customerId = $customer['resource']['data']['_id'];
		} else {
			$customerId = $this->customerId;
		}

		$params = [
			'amount'      => $this->args['amount'],
			'reference'   => (string) $this->order->get_id(),
			'currency'    => strtoupper( get_woocommerce_currency() ),
			'customer_id' => $customerId,
			'capture'     => $this->args['carddirectcharge'],
			'customer'    => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => $this->getAdditionalFields( 'amount' ),
			],
		];

		if ( ! empty( $this->args['gatewayid'] ) ) {
			$params['customer']['payment_source']['gateway_id'] = $this->args['gatewayid'];
		}

		if ( ! empty( $this->args['cvv'] ) ) {
			$params['customer']['payment_source']['card_ccv'] = $this->args['cvv'];
		}

		$responce = SDKAdapterService::getInstance()->createCharge( $params );

		if ( ! empty( $responce['error'] ) ) {
			$message = ! empty( $responce['error']['message'] ) ? ' ' . $responce['error']['message'] : '';
			/* translators: %s: Error message from Paydock API. */
			throw new Exception( esc_html( sprintf( __( 'The charge could not be created successfully. %s', 'paydock' ), $message ) ) );
		}

		return $responce;
	}

	public function createCustomer( $force = false ): void {
		if ( $this->shouldNotCreateCustomer() || $force ) {
			return;
		}

		$customerArgs = array_merge( [
			'first_name'     => $this->order->get_billing_first_name(),
			'last_name'      => $this->order->get_billing_last_name(),
			'email'          => $this->order->get_billing_email(),
			'phone'          => $this->order->get_billing_phone(),
			'payment_source' => array_merge( [
				'amount'      => $this->args['amount'],
				'vault_token' => $this->args['selectedtoken'],
			], $this->getAdditionalFields( 'amount' ) ),
		], $this->getAdditionalFields( 'amount' ) );

		if ( SaveCardOptions::WITH_GATEWAY()->name === $this->args['cardsavecardoption'] && ! empty( $this->args['gatewayid'] ) ) {
			$customerArgs['payment_source']['gateway_id'] = $this->args['gatewayid'];
		}

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
			/* translators: %s: Error message from Paydock API. */
			throw new Exception( esc_html( sprintf( __( 'The Paydock customer could not be created successfully. %s', 'paydock' ), $message ) ) );
		}
		$this->logger->createLogRecord(
			$customer['resource']['data']['_id'],
			'Create customer',
			'Success',
			'',
			LogRepository::SUCCESS
		);

		$this->userTokenRepository->updateUserToken( $this->args['selectedtoken'], [
			'customer_id' => $customer['resource']['data']['_id'],
		] );
	}

	private function shouldNotCreateCustomer(): bool {
		return ! $this->shouldCreateCustomer();
	}

	private function shouldCreateCustomer(): bool {
		return $this->vaultTokenHelper->shouldSaveVaultToken() &&
		       in_array( $this->args['cardsavecardoption'], [
			       SaveCardOptions::WITH_GATEWAY()->name,
			       SaveCardOptions::WITHOUT_GATEWAY()->name,
		       ] ) &&
		       self::CUSTOMER_CHARGE_METHOD !== $this->runMethod;
	}

	private function charge(): array {
		$vaultToken = $this->getVaultToken();

		$paymentSource = [ 'vault_token' => $vaultToken ];
		if ( isset( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
		}

		$chargeArgs = [
			'amount'    => $this->args['amount'],
			'reference' => (string) $this->order->get_id(),
			'currency'  => strtoupper( get_woocommerce_currency() ),
			'customer'  => [
				'first_name'     => $this->order->get_billing_first_name(),
				'last_name'      => $this->order->get_billing_last_name(),
				'email'          => $this->order->get_billing_email(),
				'phone'          => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $this->getAdditionalFields( 'amount' ), $paymentSource ),
			],
			'capture'   => $this->args['carddirectcharge'],
		];

		if ( ! empty( $this->args['cvv'] ) ) {
			$chargeArgs['customer']['payment_source']['card_ccv'] = $this->args['cvv'];
		}

		return SDKAdapterService::getInstance()->createCharge( $chargeArgs );
	}
}
