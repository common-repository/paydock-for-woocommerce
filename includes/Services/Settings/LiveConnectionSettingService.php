<?php

namespace Paydock\Services\Settings;

use Paydock\Abstracts\AbstractSettingService;
use Paydock\Enums\APMsSettings;
use Paydock\Enums\BankAccountSettings;
use Paydock\Enums\CardSettings;
use Paydock\Enums\CredentialSettings;
use Paydock\Enums\CredentialsTypes;
use Paydock\Enums\DSTypes;
use Paydock\Enums\FraudTypes;
use Paydock\Enums\OtherPaymentMethods;
use Paydock\Enums\SaveCardOptions;
use Paydock\Enums\SettingGroups;
use Paydock\Enums\SettingsTabs;
use Paydock\Enums\TypeExchangeOTT;
use Paydock\Enums\WalletPaymentMethods;
use Paydock\Enums\WalletSettings;
use Paydock\PaydockPlugin;
use Paydock\Services\HashService;
use Paydock\Services\SettingsService;
use Paydock\Services\Validation\ConnectionValidationService;

class LiveConnectionSettingService extends AbstractSettingService {
	public function __construct() {
		parent::__construct();

		$service = SettingsService::getInstance();
		foreach ( CredentialSettings::cases() as $credentialSettings ) {
			if ( in_array( $credentialSettings->name, CredentialSettings::getHashed() ) ) {
				$key = $service->getOptionName( $this->id, [ 
					SettingGroups::CREDENTIALS()->name,
					$credentialSettings->name,
				] );

				if ( ! empty( $this->settings[ $key ] ) ) {
					$this->settings[ $key ] = HashService::decrypt( $this->settings[ $key ] );
				}
			}
		}
	}

	public function init_form_fields(): void {
		$service = SettingsService::getInstance();

		foreach ( SettingGroups::cases() as $settingGroup ) {
			$key = PaydockPlugin::PLUGIN_PREFIX . '_' . $service->getOptionName( $this->id, [ 
				$settingGroup->name,
				'label',
			] );

			if ( SettingGroups::CARD() == $settingGroup ) {
				$this->form_fields[ $key . '_label' ] = [ 
					'type' => 'big_label',
					'title' => __( 'Payment Methods:', 'paydock' ),
				];
			}

			$this->form_fields[ $key ] = [ 
				'type' => 'big_label',
				'title' => $settingGroup->getLabel(),
			];

			switch ( $settingGroup->name ) {
				case SettingGroups::CREDENTIALS()->name:
					$mergedOptions = $this->getCredentialOptions();
					break;
				case SettingGroups::CARD()->name:
					$mergedOptions = $this->getCardOptions();
					break;
				case SettingGroups::BANK_ACCOUNT()->name:
					$mergedOptions = $this->getBankAccountOptions();
					break;
				case SettingGroups::WALLETS()->name:
					$mergedOptions = $this->getWalletsOptions();
					break;
				case SettingGroups::A_P_M_S()->name:
					$mergedOptions = $this->getAPMsOptions();
					break;
				default:
					$mergedOptions = [];
					break;
			}

			$this->form_fields = array_merge( $this->form_fields, $mergedOptions );
		}
	}

	private function getCredentialOptions(): array {
		$fields = [];
		$service = SettingsService::getInstance();

		foreach ( CredentialSettings::cases() as $credentialSettings ) {
			if ( CredentialSettings::SANDBOX()->name != $credentialSettings->name ) {
				$key = $service->getOptionName( $this->id, [ 
					SettingGroups::CREDENTIALS()->name,
					$credentialSettings->name,
				] );
				$fields[ $key ] = [ 
					'type' => $credentialSettings->getInputType(),
					'title' => $credentialSettings->getLabel(),
				];
				$description = $credentialSettings->getDescription();
				if ( $description ) {
					$fields[ $key ]['description'] = $description;
					$fields[ $key ]['desc_tip'] = true;
				}

				if ( CredentialSettings::TYPE() == $credentialSettings ) {
					$fields[ $key ]['options'] = CredentialsTypes::toArray();
				}
			}
		}

		return $fields;
	}

	private function getCardOptions(): array {
		$fields = [];
		$service = SettingsService::getInstance();

		foreach ( CardSettings::cases() as $cardSettings ) {
			$key = $service->getOptionName( $this->id, [ SettingGroups::CARD()->name, $cardSettings->name ] );
			$fields[ $key ] = [ 
				'type' => $cardSettings->getInputType(),
				'title' => preg_replace( [ '/ Id/', '/ id/' ], ' ID', $cardSettings->getLabel() ),
				'default' => $cardSettings->getDefault(),
			];

			$description = $cardSettings->getDescription();
			if ( $description ) {
				$fields[ $key ]['description'] = $description;
				$fields[ $key ]['desc_tip'] = true;
			}

			switch ( $cardSettings->name ) {
				case CardSettings::DS()->name:
					$fields[ $key ]['options'] = DSTypes::toArray();
					break;
				case CardSettings::FRAUD()->name:
					$fields[ $key ]['options'] = FraudTypes::toArray();
					break;
				case CardSettings::SAVE_CARD_OPTION()->name:
					$fields[ $key ]['options'] = SaveCardOptions::toArray();
					break;
				case CardSettings::TYPE_EXCHANGE_OTT()->name:
					$fields[ $key ]['options'] = TypeExchangeOTT::toArray();
					break;
				default:
					$fields[ $key ]['options'] = [];
					break;
			}
		}

		return $fields;
	}

	private function getBankAccountOptions(): array {
		$fields = [];
		$service = SettingsService::getInstance();

		foreach ( BankAccountSettings::cases() as $bankAccountSettings ) {
			$key = $service->getOptionName( $this->id, [ 
				SettingGroups::BANK_ACCOUNT()->name,
				$bankAccountSettings->name,
			] );

			$fields[ $key ] = [ 
				'type' => $bankAccountSettings->getInputType(),
				'title' => $bankAccountSettings->getLabel(),
			];

			$description = $bankAccountSettings->getDescription();
			if ( $description ) {
				$fields[ $key ]['description'] = $description;
				$fields[ $key ]['desc_tip'] = true;
			}

			if ( BankAccountSettings::SAVE_CARD_OPTION() == $bankAccountSettings ) {
				$fields[ $key ]['options'] = SaveCardOptions::toArray();
			}
		}

		return $fields;
	}

	private function getWalletsOptions(): array {
		$fields = [];
		$service = SettingsService::getInstance();

		foreach ( WalletPaymentMethods::cases() as $walletPaymentMethods ) {
			$fields[ $service->getOptionName( $this->id, [ 
				SettingGroups::WALLETS()->name,
				$walletPaymentMethods->name,
				'label',
			] ) ] = [ 
					'type' => 'label',
					'title' => $walletPaymentMethods->getLabel(),
				];

			foreach ( WalletSettings::cases() as $walletSettings ) {
				$key = $service->getOptionName( $this->id, [ 
					SettingGroups::WALLETS()->name,
					$walletPaymentMethods->name,
					$walletSettings->name,
				] );

				$fields[ $key ] = [ 
					'type' => $walletSettings->getInputType(),
					'title' => preg_replace( [ '/ Id/', '/ id/' ], ' ID', $walletSettings->getLabel() ),
				];

				$description = $walletSettings->getDescription();
				if ( $description ) {
					$fields[ $key ]['description'] = $description;
					$fields[ $key ]['desc_tip'] = true;
				}
			}

			if ( WalletPaymentMethods::PAY_PAL_SMART_BUTTON()->name === $walletPaymentMethods->name ) {
				$key = $service->getOptionName( $this->id, [ 
					SettingGroups::WALLETS()->name,
					$walletPaymentMethods->name,
					'pay_later',
				] );
				$fields[ $key ] = [ 
					'type' => 'checkbox',
					'title' => __( 'Pay Later', 'paydock' ),
				];
			}
		}

		return $fields;
	}

	public function getAPMsOptions(): array {
		$fields = [];
		$service = SettingsService::getInstance();

		foreach ( OtherPaymentMethods::cases() as $otherPaymentMethods ) {
			$fields[ $service->getOptionName( $this->id, [ 
				SettingGroups::A_P_M_S()->name,
				$otherPaymentMethods->name,
				'label',
			] ) ] = [ 
					'type' => 'label',
					'title' => $otherPaymentMethods->getLabel(),
				];

			foreach ( APMsSettings::cases() as $APMsSettings ) {
				if ( OtherPaymentMethods::AFTERPAY()->name === $otherPaymentMethods->name &&
					APMsSettings::DIRECT_CHARGE()->name === $APMsSettings->name ) {
					continue;
				}

				$key = $service->getOptionName( $this->id, [ 
					SettingGroups::A_P_M_S()->name,
					$otherPaymentMethods->name,
					$APMsSettings->name,
				] );

				$fields[ $key ] = [ 
					'type' => $APMsSettings->getInputType(),
					'title' => $APMsSettings->getLabel(),
				];

				$description = $APMsSettings->getDescription();
				if ( $description ) {
					$fields[ $key ]['description'] = $description;
					$fields[ $key ]['desc_tip'] = true;
				}

				if ( APMsSettings::SAVE_CARD_OPTION() == $APMsSettings ) {
					$fields[ $key ]['options'] = SaveCardOptions::toArray();
				}
			}
		}

		return $fields;
	}

	public function process_admin_options() {
		$this->init_settings();
		$validationService = new ConnectionValidationService( $this );
		$this->settings = array_merge( $this->settings, $validationService->getResult() );

		$service = SettingsService::getInstance();

		foreach ( CredentialSettings::cases() as $credentialSettings ) {
			if ( in_array( $credentialSettings->name, CredentialSettings::getHashed() ) ) {
				$key = $service->getOptionName( $this->id, [ 
					SettingGroups::CREDENTIALS()->name,
					$credentialSettings->name,
				] );

				if ( ! empty( $this->settings[ $key ] ) ) {
					$this->settings[ $key ] = HashService::encrypt( $this->settings[ $key ] );
				}
			}
		}
		foreach ( $validationService->getErrors() as $error ) {
			$this->add_error( $error );
			\WC_Admin_Settings::add_error( $error );
		}

		$option_key = $this->get_option_key();
		do_action( 'woocommerce_update_option', [ 'id' => $option_key ] );

		return update_option(
			$option_key,
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ),
			'yes'
		);
	}

	protected function getId(): string {
		return SettingsTabs::LIVE_CONNECTION()->value;
	}
}
