<?php

namespace Paydock\Services\ProcessPayment;

use Paydock\Enums\SaveCardOptions;
use Paydock\Exceptions\LoggedException;
use Paydock\Helpers\ArgsForProcessPayment;
use Paydock\Helpers\VaultTokenHelper;
use Paydock\Repositories\LogRepository;
use Paydock\Repositories\UserTokenRepository;
use Paydock\Services\SDKAdapterService;

class BankAccountProcessor {
	protected $order;
	protected $args;
	protected $tokenData = [];
	protected $orderId;
	private $vaultTokenHelper;
	private $logger;

	public function __construct( $order, $args ) {
		$this->order = $order;
		$this->logger = new LogRepository();
		$this->args = ArgsForProcessPayment::prepare( $args );
		$this->vaultTokenHelper = new VaultTokenHelper( $this->args );
	}

	public function run( $orderId = null ): array {
		$this->orderId = $orderId;
		if (
			$this->args['bankaccountsaveaccount']
			&& $this->args['bankaccountsavechecked']
			&& (
				SaveCardOptions::WITH_GATEWAY()->name === $this->getSaveType()
				|| SaveCardOptions::WITHOUT_GATEWAY()->name === $this->getSaveType()
			)
		) {
			return $this->chargeWithCustomerId();
		}

		return $this->directCharge();
	}

	protected function getSaveType(): ?string {
		switch ( $this->args['bankaccountsaveaccountoption'] ) {
			case SaveCardOptions::VAULT()->name:
				return SaveCardOptions::VAULT()->name;
			case SaveCardOptions::WITH_GATEWAY()->name:
				return SaveCardOptions::WITH_GATEWAY()->name;
			case SaveCardOptions::WITHOUT_GATEWAY()->name:
				return SaveCardOptions::WITHOUT_GATEWAY()->name;
			default:
				return null;
		}
	}

	public function chargeWithCustomerId(): array {
		$request = [ 
			'reference' => (string) $this->orderId,
			'first_name' => $this->order->get_billing_first_name(),
			'last_name' => $this->order->get_billing_last_name(),
			'email' => $this->order->get_billing_email(),
			'phone' => $this->order->get_billing_phone(),
			'payment_source' => array_merge( [ 
						'amount' => $this->args['amount'],
						'type' => 'bank_account',
						'vault_token' => $this->getVaultToken(),
					], $this->getAdditionalFields( 'amount' ) ),
		];

		if ( ! empty( $this->args['gatewayid'] ) && SaveCardOptions::WITH_GATEWAY()->name === $this->getSaveType() ) {
			$request['payment_source']['gateway_id'] = $this->args['gatewayid'];
		}

		if ( ! empty( $this->tokenData ) && ! empty( $this->tokenData['customer_id'] ) ) {
			$customerId = $this->tokenData['customer_id'];
		} else {
			$response = SDKAdapterService::getInstance()->createCustomer( $request );

			if ( ! empty( $response['error'] ) || empty( $response['resource']['data']['_id'] ) ) {
				$this->logger->createLogRecord(
					'',
					'Create customer',
					'Error',
					$response['error']['message'],
					LogRepository::ERROR
				);
				new LoggedException(
					__( 'Oops! Something went wrong. Please check the information provided and try again. ', 'paydock' ),
					0,
					null,
					$response
				);
			}

			$this->logger->createLogRecord(
				$response['resource']['data']['_id'],
				'Create customer',
				'Success',
				'',
				LogRepository::SUCCESS
			);

			if ( $this->vaultTokenHelper->shouldSaveVaultToken() ) {
				( new UserTokenRepository() )->updateUserToken( $this->args['selectedtoken'], [ 
					'customer_id' => $response['resource']['data']['_id'],
				] );
			}

			$customerId = $response['resource']['data']['_id'];
		}

		return $this->directCharge( $customerId );
	}

	protected function getVaultToken(): string {
		if ( ! empty( $this->args['selectedtoken'] ) ) {
			$this->tokenData = ( new UserTokenRepository() )->getUserToken( $this->args['selectedtoken'] );

			return $this->args['selectedtoken'];
		}

		$token = $this->vaultTokenHelper->get( $this->getAdditionalFields() );

		if ( ! empty( $token ) ) {
			$this->args['selectedtoken'] = $token;
		}

		return $token;
	}

	protected function getAdditionalFields( $exclude = [] ): array {
		$address1 = $this->order->get_billing_address_1();
		$address2 = $this->order->get_billing_address_2();

		$result = [ 
			'amount' => (float) $this->order->get_total(),
			'address_country' => $this->order->get_billing_country(),
			'address_postcode' => $this->order->get_billing_postcode(),
			'address_city' => $this->order->get_billing_city(),
			'address_state' => $this->order->get_billing_state(),
			'address_line1' => $address1,
			'address_line2' => empty( $address2 ) ? $address1 : $address2,
		];

		if ( ! empty( $exclude ) ) {
			if ( ! is_array( $exclude ) ) {
				$exclude = [ $exclude ];
			}

			$result = array_diff_key( $result, array_flip( $exclude ) );
		}

		return $result;
	}

	protected function directCharge( $customerId = null ): array {
		$addPaymentSource = $this->getAdditionalFields( 'amount' );

		$paymentSource = [ 
			'vault_token' => ! empty( $this->args['selectedtoken'] ) ? $this->args['selectedtoken'] : $this->getVaultToken(
			),
			'type' => 'bank_account',
		];

		if ( ! empty( $this->args['gatewayid'] ) ) {
			$paymentSource['gateway_id'] = $this->args['gatewayid'];
		}

		$request = [ 
			'reference' => (string) $this->orderId,
			'amount' => $this->args['amount'],
			'currency' => strtoupper( get_woocommerce_currency() ),
			'customer' => [ 
				'first_name' => $this->order->get_billing_first_name(),
				'last_name' => $this->order->get_billing_last_name(),
				'email' => $this->order->get_billing_email(),
				'phone' => $this->order->get_billing_phone(),
				'payment_source' => array_merge( $addPaymentSource, $paymentSource ),
			],
		];

		if ( ! empty( $customerId ) ) {
			$request['customer_id'] = $customerId;
		}

		$response = SDKAdapterService::getInstance()->createCharge( $request );

		if ( ! empty( $response['error'] ) ) {
			new LoggedException(
				__( 'Oops! Something went wrong. Please check the information provided and try again. ', 'power-board' ),
				0,
				null,
				$response
			);
		}

		return $response;
	}
}
