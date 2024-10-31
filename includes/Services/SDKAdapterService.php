<?php

namespace Paydock\Services;

use Paydock\API\ChargeService;
use Paydock\API\ConfigService;
use Paydock\API\CustomerService;
use Paydock\API\GatewayService;
use Paydock\API\NotificationService;
use Paydock\API\ServiceService;
use Paydock\API\TokenService;
use Paydock\API\VaultService;
use Paydock\Enums\CredentialSettings;
use Paydock\Enums\CredentialsTypes;
use Paydock\Enums\SettingGroups;
use Paydock\Services\Settings\LiveConnectionSettingService;
use Paydock\Services\Settings\SandboxConnectionSettingService;

class SDKAdapterService {
	private const ENABLED_CONDITION = 'yes';
	private const PROD_ENV = 'production';
	private const SANDBOX_ENV = 'sandbox';
	private static $instance = null;

	public function __construct() {
		$this->initialise();
	}

	public function initialise( ?bool $forcedEnv = null ): void {
		$isProd = $this->isProd( $forcedEnv );

		$settingsService = SettingsService::getInstance();

		if ( $isProd ) {
			$settings = new LiveConnectionSettingService();

		} else {
			$settings = new SandboxConnectionSettingService();
		}

		$isAccessToken = CredentialsTypes::ACCESS_KEY()->name == $settings->get_option(
			$settingsService->getOptionName( $settings->id, [ 
				SettingGroups::CREDENTIALS()->name,
				CredentialSettings::TYPE()->name,
			] )
		);

		if ( $isAccessToken ) {
			$secretKey = $settings->get_option( $settingsService->getOptionName( $settings->id, [ 
				SettingGroups::CREDENTIALS()->name,
				CredentialSettings::ACCESS_KEY()->name,
			] ) );
		} else {
			$publicKey = $settings->get_option( $settingsService->getOptionName( $settings->id, [ 
				SettingGroups::CREDENTIALS()->name,
				CredentialSettings::PUBLIC_KEY()->name,
			] ) );
			$secretKey = $settings->get_option( $settingsService->getOptionName( $settings->id, [ 
				SettingGroups::CREDENTIALS()->name,
				CredentialSettings::SECRET_KEY()->name,
			] ) );
		}

		$env = $isProd ? self::PROD_ENV : self::SANDBOX_ENV;

		ConfigService::init( $env, $secretKey, $publicKey ?? null );
	}

	private function isProd( ?bool $forcedProdEnv = null ): bool {
		if ( is_null( $forcedProdEnv ) ) {
			$settings = new SandboxConnectionSettingService();

			return self::ENABLED_CONDITION !== $settings->get_option(
				SettingsService::getInstance()->getOptionName( $settings->id, [ 
					SettingGroups::CREDENTIALS()->name,
					CredentialSettings::SANDBOX()->name,
				] )
			);
		}

		return $forcedProdEnv;
	}

	public static function getInstance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function searchGateway( array $parameters = [] ): array {
		$gatewayService = new GatewayService();

		return $gatewayService->search( $parameters )->call();
	}

	public function searchServices( array $parameters = [] ): array {
		$serviceService = new ServiceService();

		return $serviceService->search( $parameters )->call();
	}

	public function searchNotifications( array $parameters = [] ): array {
		$notificationService = new NotificationService();

		return $notificationService->search( $parameters )->call();
	}

	public function createNotification( array $parameters = [] ): array {
		$notificationService = new NotificationService();

		return $notificationService->create( $parameters )->call();
	}

	public function token( array $params = [ 'gateway_id' => '', 'type' => '' ] ): array {
		$tokenService = new TokenService();

		return $tokenService->create( $params )->call();
	}

	public function getGatewayById( string $id ): array {
		$gatewayService = new GatewayService();

		return $gatewayService->get()->setId( $id )->call();
	}

	public function createVaultToken( array $params ): array {
		$vaultService = new VaultService();

		return $vaultService->create( $params )->call();
	}

	public function createCustomer( array $params ): array {
		$customerService = new CustomerService();

		if ( isset( $params['reference'] ) ) {
			unset( $params['reference'] );
		}

		return $customerService->create( $params )->call();
	}

	public function createCharge( array $params ): array {
		$chargeService = new ChargeService();

		return $chargeService->create( $params )->call();
	}

	public function createWalletCharge( array $params, ?bool $directCharge ): array {
		$chargeService = new ChargeService();

		return $chargeService->walletsInitialize( $params, $directCharge )->call();
	}

	public function standaloneFraudCharge( array $params ): array {
		$chargeService = new ChargeService();

		return $chargeService->standaloneFraud( $params )->call();
	}

	public function fraudAttach( string $id, array $params ): array {
		$chargeService = new ChargeService();

		return $chargeService->fraudAttach( $id, $params )->call();
	}

	public function standalone3DsCharge( array $params ): array {
		$chargeService = new ChargeService();

		return $chargeService->standalone3Ds( $params )->call();
	}

	public function capture( array $params ): array {
		$chargeService = new ChargeService();

		return $chargeService->capture( $params )->call();
	}

	public function cancelAuthorised( array $params ): array {
		$chargeService = new ChargeService();

		return $chargeService->cancelAuthorised( $params )->call();
	}

	public function refunds( array $params ): array {
		$chargeService = new ChargeService();

		return $chargeService->refunds( $params )->call();
	}

	public function errorMessageToString( $responce ): string {
		if ( $responce instanceof \WP_Error ) {
			return $responce->get_error_message();
		}

		$result = ! empty( $responce['error']['message'] ) ? ' ' . $responce['error']['message'] : '';
		if ( isset( $responce['error']['details'] ) ) {
			if ( ! empty( $responce['error']['details']['messages'] ) ) {
				$firstMessage = reset( $responce['error']['details']['messages'] );

				return $firstMessage;
			}

			$firstDetail = reset( $responce['error']['details'] );
			if ( is_array( $firstDetail ) ) {
				$result .= ' ' . implode( ',', $firstDetail );
			} else {
				$result .= ' ' . implode( ',', $responce['error']['details'] );
			}
		}

		return $result;
	}
}
