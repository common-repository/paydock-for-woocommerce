<?php

namespace Paydock\Services\Validation;

use Exception;
use Paydock\Abstracts\AbstractSettingService;
use Paydock\API\ConfigService;
use Paydock\Enums\BankAccountSettings;
use Paydock\Enums\CardSettings;
use Paydock\Enums\CredentialSettings;
use Paydock\Enums\CredentialsTypes;
use Paydock\Enums\DSTypes;
use Paydock\Enums\FraudTypes;
use Paydock\Enums\NotificationEvents;
use Paydock\Enums\OtherPaymentMethods;
use Paydock\Enums\SettingGroups;
use Paydock\Enums\SettingsTabs;
use Paydock\Enums\WalletPaymentMethods;
use Paydock\Enums\WalletSettings;
use Paydock\Services\SDKAdapterService;
use Paydock\Services\SettingsService;

class ConnectionValidationService {
	private $oldAccessToken = null;
	private $oldPublicKey = null;
	private $oldSecretKey = null;
	private const ENABLED_CONDITION = 'yes';

	private const UNSELECTED_CRD_VALUE = 'Please select payment methods...';

	private const AVAILABLE_CARD_TYPES = [
		'mastercard' => 'MasterCard',
		'visa'       => 'Visa',
		'amex'       => 'American Express',
		'diners'     => 'Diners Club',
		'japcb'      => 'Japanese Credit Bureau',
		'maestro'    => 'Maestro',
		'ausbc'      => 'Australian Bank Card',
	];
	private const IS_WEBHOOK_SET_OPTION = 'is_paydock_webhook_set';

	public $service = null;
	private $errors = [];
	private $result = [];
	private $data = [];
	private $getawayIds = [];
	private $servicesIds = [];
	private $adapterService = null;

	public function __construct( AbstractSettingService $service ) {
		$this->service        = $service;
		$this->adapterService = SDKAdapterService::getInstance();
		$this->adapterService->initialise( SettingsTabs::LIVE_CONNECTION()->value === $this->service->id );

		$this->prepareFormData();
		$this->validate();

		$option_key = $service->get_option_key();
		do_action( 'woocommerce_update_option', [ 'id' => $option_key ] );

		update_option(
			$option_key,
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $service->id, $service->settings ),
			'yes'
		);
	}

	private function prepareFormData(): void {
		$post_data = $this->service->get_post_data();
		foreach ( $this->service->get_form_fields() as $key => $field ) {
			try {
				$this->data[ $key ]   = $this->service->get_field_value( $key, $field, $post_data );
				$this->result[ $key ] = $this->data[ $key ];

				if ( 'select' === $field['type'] || 'checkbox' === $field['type'] ) {
					do_action( 'woocommerce_update_non_option_setting', [
						'id'    => $key,
						'type'  => $field['type'],
						'value' => $this->data[ $key ],
					] );
				}
			} catch ( Exception $e ) {
				$this->service->add_error( $e->getMessage() );
			}
		}
	}

	private function validate(): void {
		if ( $this->validateCredential() ) {
			$this->validateCard();
			$this->validateWallets();
			$this->validateAPMs();
			$this->setWebhooks();
		}
	}

	private function validateCredential(): bool {
		$accessKey = SettingsService::getInstance()
		                            ->getOptionName( $this->service->id, [
			                            SettingGroups::CREDENTIALS()->name,
			                            CredentialSettings::ACCESS_KEY()->name,
		                            ] );

		$widgetKey = SettingsService::getInstance()
		                            ->getOptionName( $this->service->id, [
			                            SettingGroups::CREDENTIALS()->name,
			                            CredentialSettings::WIDGET_KEY()->name,
		                            ] );

		$publicKey = SettingsService::getInstance()
		                            ->getOptionName( $this->service->id, [
			                            SettingGroups::CREDENTIALS()->name,
			                            CredentialSettings::PUBLIC_KEY()->name,
		                            ] );

		$secretKey = SettingsService::getInstance()
		                            ->getOptionName( $this->service->id, [
			                            SettingGroups::CREDENTIALS()->name,
			                            CredentialSettings::SECRET_KEY()->name,
		                            ] );

		$typeKey = SettingsService::getInstance()
		                          ->getOptionName( $this->service->id, [
			                          SettingGroups::CREDENTIALS()->name,
			                          CredentialSettings::TYPE()->name,
		                          ] );

		$isAccessKey = CredentialsTypes::ACCESS_KEY()->name == $this->data[ $typeKey ];

		if (
			(
				$isAccessKey
				&& ! empty( $this->data[ $accessKey ] )
				&& ! empty( $this->data[ $widgetKey ] )
				&& $this->checkAccessKeyConnection( $this->data[ $accessKey ] )
				&& $this->checkWidgetKeyConnection( $this->data[ $widgetKey ] )
			) || (
				! $isAccessKey
				&& ! empty( $this->data[ $publicKey ] )
				&& ! empty( $this->data[ $secretKey ] )
				&& $this->checkCredentialConnection( $this->data[ $publicKey ], $this->data[ $secretKey ] )
			)
		) {
			return true;
		}

		$this->errors[] = 'Invalid credentials. Please update and try again. ';

		return false;
	}

	private function checkAccessKeyConnection( ?string $accessToken ): bool {
		$this->saveOldCredential();

		ConfigService::$accessToken = $accessToken;
		ConfigService::$publicKey   = null;
		ConfigService::$secretKey   = null;

		$this->getawayIds  = $this->adapterService->searchGateway( [ 'sort_direction' => 'DESC' ] );
		$this->servicesIds = $this->adapterService->searchServices( [ 'sort_direction' => 'DESC' ] );

		$result = empty( $this->getawayIds['error'] );

		$this->restoreCredential();

		if ( $result ) {
			ConfigService::$accessToken = $accessToken;
		}

		return $result;
	}

	private function saveOldCredential() {
		$this->oldAccessToken = ConfigService::$accessToken;
		$this->oldPublicKey   = ConfigService::$publicKey;
		$this->oldSecretKey   = ConfigService::$secretKey;
	}

	private function restoreCredential() {
		ConfigService::$accessToken = $this->oldAccessToken;
		ConfigService::$publicKey   = $this->oldPublicKey;
		ConfigService::$secretKey   = $this->oldSecretKey;
	}

	private function checkWidgetKeyConnection( ?string $accessToken ): bool {
		$this->saveOldCredential();

		ConfigService::$accessToken = $accessToken;
		ConfigService::$publicKey   = null;
		ConfigService::$secretKey   = null;

		$result = $this->adapterService->token();
		$result = empty( $result['error'] );

		$this->restoreCredential();

		return $result;
	}

	private function checkCredentialConnection( ?string $public, ?string $secret ): bool {
		ConfigService::$publicKey = $public;
		ConfigService::$secretKey = $secret;

		return $this->checkPublicKey( $public ) && $this->checkSecretKey( $secret );
	}

	private function checkPublicKey( ?string $publicKey ): bool {
		$this->saveOldCredential();
		ConfigService::$publicKey   = $publicKey;
		ConfigService::$secretKey   = null;
		ConfigService::$accessToken = null;

		$result = $this->adapterService->token();
		$result = empty( $result['error'] );

		$this->restoreCredential();

		return $result;
	}

	private function checkSecretKey( ?string $secretKey ): bool {
		$this->saveOldCredential();

		ConfigService::$publicKey   = null;
		ConfigService::$accessToken = null;
		ConfigService::$secretKey   = $secretKey;

		$this->getawayIds  = $this->adapterService->searchGateway( [ 'sort_direction' => 'DESC' ] );
		$this->servicesIds = $this->adapterService->searchServices( [ 'sort_direction' => 'DESC' ] );

		$result = empty( $this->getawayIds['error'] );

		$this->restoreCredential();

		return $result;
	}

	private function validateCard(): void {
		$enableKey = SettingsService::getInstance()
		                            ->getOptionName( $this->service->id, [
			                            SettingGroups::CARD()->name,
			                            CardSettings::ENABLE()->name,
		                            ] );

		$gatewayIdKey = SettingsService::getInstance()
		                               ->getOptionName( $this->service->id, [
			                               SettingGroups::CARD()->name,
			                               CardSettings::GATEWAY_ID()->name,
		                               ] );

		$fraudEnableServiceKey = SettingsService::getInstance()
		                                        ->getOptionName( $this->service->id, [
			                                        SettingGroups::CARD()->name,
			                                        CardSettings::FRAUD()->name,
		                                        ] );

		$fraudGatewayIdKey = SettingsService::getInstance()
		                                    ->getOptionName( $this->service->id, [
			                                    SettingGroups::CARD()->name,
			                                    CardSettings::FRAUD_SERVICE_ID()->name,
		                                    ] );

		$_3DSEnableServiceKey = SettingsService::getInstance()
		                                       ->getOptionName( $this->service->id, [
			                                       SettingGroups::CARD()->name,
			                                       CardSettings::DS()->name,
		                                       ] );

		$_3DSGatewayIdKey = SettingsService::getInstance()
		                                   ->getOptionName( $this->service->id, [
			                                   SettingGroups::CARD()->name,
			                                   CardSettings::DS_SERVICE_ID()->name,
		                                   ] );

		$this->result[ $enableKey ] = $this->data[ $enableKey ];

		if ( 'yes' !== $this->data[ $enableKey ] ) {
			$this->result[ $gatewayIdKey ] = $this->data[ $gatewayIdKey ];
		}

		$supportedCardTypesKey = SettingsService::getInstance()->getOptionName( $this->service->id, [
			SettingGroups::CARD()->name,
			CardSettings::SUPPORTED_CARD_TYPES()->name,
		] );


		if ( 'yes' == $this->data[ $enableKey ] && ! empty( $this->data[ $gatewayIdKey ] ) ) {
			$isValidGateway = $this->validateId( $this->data[ $gatewayIdKey ] );
			if ( $isValidGateway ) {
				$this->result[ $gatewayIdKey ] = $this->data[ $gatewayIdKey ];
				$supportCardTypeByGatewayId    = $this->getSupportCardTypeByGatewayId( $this->data[ $gatewayIdKey ] );
				if ( $supportCardTypeByGatewayId ) {
					if ( $this->data[ $supportedCardTypesKey ] ) {
						if ( self::UNSELECTED_CRD_VALUE == $this->data[ $supportedCardTypesKey ] ) {
							$this->errors[] = 'You have not selected a supported card type. Please choose from the list of supported card types to continue.';
						} else {
							$supportCardType             = strtolower(
								str_replace(
									' ',
									'',
									$this->data[ $supportedCardTypesKey ]
								)
							);
							$arraySupportedCardTypesKeys = explode( ',', $supportCardType );
							if ( empty(
							array_intersect(
								$arraySupportedCardTypesKeys,
								array_keys( self::AVAILABLE_CARD_TYPES )
							)
							) ) {
								$this->errors[] = 'The selected card types (' . implode(
										',',
										$arraySupportedCardTypesKeys
									) . ') are not supported with this Gateway ID.';
							}
						}
					} else {
						$this->errors[] = 'You have not selected a supported card type. Please choose from the list of supported card types to continue.';
					}
				}

			} else {
				$a              = $this->data[ $gatewayIdKey ];
				$b              = array_map( fn( $item ) => $item['_id'], $this->getawayIds['resource']['data'] );
				$this->errors[] = 'Incorrect Gateway ID for the card: ' . $this->data[ $gatewayIdKey ];
			}

			if (
				$isValidGateway
				&& ( FraudTypes::DISABLE()->name !== $this->data[ $fraudEnableServiceKey ] )
				&& ! $this->validateId( $this->data[ $fraudGatewayIdKey ] )
			) {
				$this->errors[] = 'Incorrect Fraud Service ID: ' . $this->data[ $fraudGatewayIdKey ];
			}

			if (
				$isValidGateway
				&& ( DSTypes::STANDALONE()->name === $this->data[ $_3DSEnableServiceKey ] )
				&& ! $this->validateId( $this->data[ $_3DSGatewayIdKey ] )
			) {
				$this->errors[] = 'Incorrect 3DS Service ID: ' . $this->data[ $_3DSGatewayIdKey ];
			}
		}
	}

	private function validateId( string $id, bool $fraudPassiveMode = false ): bool {
		foreach ( $this->getawayIds['resource']['data'] as $getawayId ) {
			if ( $getawayId['_id'] == $id ) {
				return true;
			}
		}

		foreach ( $this->servicesIds['resource']['data'] as $servicesId ) {
			if (
				(
					! $fraudPassiveMode
					&& $id == $servicesId['_id']
				) || (
					$id == $servicesId['_id']
					&& $fraudPassiveMode
					&& isset( $servicesId['fraud_options']['mode'] )
					&& 'active' !== $servicesId['fraud_options']['mode']
				)
			) {
				return true;
			}
		}

		return false;
	}

	private function getSupportCardTypeByGatewayId( $gatewayIdKey ): ?string {
		foreach ( $this->getawayIds['resource']['data'] as $getawayId ) {
			if ( $getawayId['_id'] == $gatewayIdKey ) {
				return strtolower( $getawayId['type'] );
			}
		}

		return false;
	}

	private function validateBankAccount(): void {
		return;

		$enabledKey = SettingsService::getInstance()
		                             ->getOptionName( $this->service->id, [
			                             SettingGroups::BANK_ACCOUNT()->name,
			                             BankAccountSettings::ENABLE()->name,
		                             ] );
		$gatewayKey = SettingsService::getInstance()
		                             ->getOptionName( $this->service->id, [
			                             SettingGroups::BANK_ACCOUNT()->name,
			                             BankAccountSettings::GATEWAY_ID()->name,
		                             ] );

		$result = false;

		if ( self::ENABLED_CONDITION !== $this->data[ $enabledKey ] ) {
			$result = true;
		}

		if ( ! $result && $this->validateId( $this->data[ $gatewayKey ] ) ) {
			$result = true;
		}

		if ( ! $result ) {
			$this->errors[] = 'Incorrect Gateway ID for Bank Accoun';
		}
	}

	private function validateWallets(): void {
		foreach ( WalletPaymentMethods::cases() as $method ) {
			$result            = true;
			$enabledKey        = SettingsService::getInstance()
			                                    ->getOptionName( $this->service->id, [
				                                    SettingGroups::WALLETS()->name,
				                                    $method->name,
				                                    WalletSettings::ENABLE()->name,
			                                    ] );
			$gatewayKey        = SettingsService::getInstance()
			                                    ->getOptionName( $this->service->id, [
				                                    SettingGroups::WALLETS()->name,
				                                    $method->name,
				                                    WalletSettings::GATEWAY_ID()->name,
			                                    ] );
			$fraudEnableKey    = SettingsService::getInstance()
			                                    ->getOptionName( $this->service->id, [
				                                    SettingGroups::WALLETS()->name,
				                                    $method->name,
				                                    WalletSettings::FRAUD()->name,
			                                    ] );
			$fraudGatewayIdKey = SettingsService::getInstance()
			                                    ->getOptionName( $this->service->id, [
				                                    SettingGroups::WALLETS()->name,
				                                    $method->name,
				                                    WalletSettings::FRAUD_SERVICE_ID()->name,
			                                    ] );
			$isEnabled         = self::ENABLED_CONDITION === $this->data[ $enabledKey ];
			if ( $isEnabled ) {
				switch ( $method->name ) {
					case WalletPaymentMethods::PAY_PAL_SMART_BUTTON()->name:
					case WalletPaymentMethods::AFTERPAY()->name:
					default:
						$result = $this->validateId( $this->data[ $gatewayKey ] );
						break;
				}
			}

			if ( ! $result ) {
				$this->errors[] = 'Incorrect Gateway ID for ' . $method->getLabel() . ' wallet';
			}

			if (
				$isEnabled
				&& ( self::ENABLED_CONDITION == $this->data[ $fraudEnableKey ] )
				&& ! $this->validateId( $this->data[ $fraudGatewayIdKey ] )
			) {
				$this->errors[] = 'Incorrect Fraud Service ID for '
				                  . $method->getLabel()
				                  . ' wallet';
			} elseif (
				$isEnabled
				&& ( self::ENABLED_CONDITION == $this->data[ $fraudEnableKey ] )
				&& WalletPaymentMethods::AFTERPAY()->name == $method->name
				&& ! $this->validateId( $this->data[ $fraudGatewayIdKey ], true )
			) {
				$this->errors[] = 'Fraud service mode is not supported with Alternative Payment Method';
			}
		}
	}

	private function validateAPMs(): void {
		foreach ( OtherPaymentMethods::cases() as $method ) {
			$result            = true;
			$enabledKey        = SettingsService::getInstance()
			                                    ->getOptionName( $this->service->id, [
				                                    SettingGroups::A_P_M_S()->name,
				                                    $method->name,
				                                    WalletSettings::ENABLE()->name,
			                                    ] );
			$gatewayKey        = SettingsService::getInstance()
			                                    ->getOptionName( $this->service->id, [
				                                    SettingGroups::A_P_M_S()->name,
				                                    $method->name,
				                                    WalletSettings::GATEWAY_ID()->name,
			                                    ] );
			$fraudEnableKey    = SettingsService::getInstance()
			                                    ->getOptionName( $this->service->id, [
				                                    SettingGroups::A_P_M_S()->name,
				                                    $method->name,
				                                    WalletSettings::FRAUD()->name,
			                                    ] );
			$fraudGatewayIdKey = SettingsService::getInstance()
			                                    ->getOptionName( $this->service->id, [
				                                    SettingGroups::A_P_M_S()->name,
				                                    $method->name,
				                                    WalletSettings::FRAUD_SERVICE_ID()->name,
			                                    ] );
			$isEnabled         = self::ENABLED_CONDITION === $this->data[ $enabledKey ];
			if ( $isEnabled ) {
				switch ( true ) {
					case OtherPaymentMethods::AFTERPAY() === $method:
						$result = $this->validateId( $this->data[ $gatewayKey ] );
						break;
					default:
						$result = $this->validateId( $this->data[ $gatewayKey ] );
						break;
				}
			}

			if ( ! $result ) {
				$this->errors[] = 'Incorrect Fraud Service ID for ' . $method->getLabel() . ' Alternative Payment Method .';
			}

			if (
				$isEnabled
				&& ( self::ENABLED_CONDITION == $this->data[ $fraudEnableKey ] )
				&& ! $this->validateId( $this->data[ $fraudGatewayIdKey ] )
			) {
				$this->errors[] = 'Incorrect '
				                  . $method->getLabel()
				                  . ' APM Fraud Service ID: '
				                  . $this->data[ $fraudGatewayIdKey ];
			} elseif (
				$isEnabled
				&& ( self::ENABLED_CONDITION == $this->data[ $fraudEnableKey ] )
				&& ! $this->validateId( $this->data[ $fraudGatewayIdKey ], true )
			) {
				$this->errors[] = 'Fraud service mode is not supported with Alternative Payment Method';
			}
		}
	}

	private function setWebhooks(): void {
		$webhookEvents = NotificationEvents::events();
		if ( false !== strpos( get_site_url(), 'localhost' ) ) {
			return;
		}

		$notSettedWebhooks   = $webhookEvents;
		$webhookSiteUrl      = get_site_url() . '/wc-api/paydock-webhook/';
		$shouldCreateWebhook = true;
		$webhookRequest      = $this->adapterService->searchNotifications( [ 'type' => 'webhook' ] );
		if ( ! empty( $webhookRequest['resource']['data'] ) ) {
			$events = [];
			foreach ( $webhookRequest['resource']['data'] as $webhook ) {
				if ( $webhook['destination'] === $webhookSiteUrl ) {
					$events[] = $webhook['event'];
				}
			}

			$notSettedWebhooks = array_diff( $webhookEvents, $events );
			if ( empty( $notSettedWebhooks ) ) {
				$shouldCreateWebhook = false;
			}
		}

		$webhookIds = [];
		if ( $shouldCreateWebhook ) {
			foreach ( $notSettedWebhooks as $event ) {
				$result = $this->adapterService->createNotification( [
					'event'            => $event,
					'destination'      => $webhookSiteUrl,
					'type'             => 'webhook',
					'transaction_only' => false,
				] );

				if ( ! empty( $result['resource']['data']['_id'] ) ) {
					$webhookIds[] = $result['resource']['data']['_id'];
				} else {
					$this->errors[] = __(
						                  'Can\'t create webhook',
						                  'paydock'
					                  ) . ( ! empty( $result['error'] ) ? ' ' . wp_json_encode( $result['error'] ) : '' );

					return;
				}
			}

			if ( ! empty( $webhookIds ) ) {
				update_option( self::IS_WEBHOOK_SET_OPTION, $webhookIds );
			}
		} else {
			return;
		}
	}

	public function getResult(): array {
		return $this->result;
	}

	public function getErrors(): array {
		return $this->errors;
	}
}
