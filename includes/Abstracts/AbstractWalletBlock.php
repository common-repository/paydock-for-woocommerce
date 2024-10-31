<?php

namespace Paydock\Abstracts;

use Paydock\Enums\WalletPaymentMethods;
use Paydock\Services\SettingsService;

abstract class AbstractWalletBlock extends AbstractBlock {

	protected $gateway;

	public function __construct() {
		$walletTypeId = $this->getType()->getId();

		$this->name   = 'paydock_' . $walletTypeId . '_wallet_block';
		$this->script = $walletTypeId . '-wallet';

		parent::__construct();
	}

	abstract public function getType(): WalletPaymentMethods;

	public function get_payment_method_data(): array {
		$settings = SettingsService::getInstance();
		$payment  = $this->getType();

		$result = [
			'_wpnonce'         => wp_create_nonce( 'process_payment' ),
			'title'            => $settings->getWidgetPaymentWalletTitle( $payment ),
			'description'      => $settings->getWidgetPaymentWalletDescription( $payment ),
			'publicKey'        => $settings->getPublicKey(),
			'isSandbox'        => $settings->isSandbox(),
			'styles'           => $settings->getWidgetStyles(),
			'total_limitation' => $settings->getWidgetPaymentWalletMinMax( $payment ),
		];

		$result['wallets'][ strtolower( $payment->name ) ] = [
			'gatewayId'      => $settings->getWalletGatewayId( $payment ),
			'fraud'          => $settings->isWalletFraud( $payment ),
			'fraudServiceId' => $settings->getWalletFraudServiceId( $payment ),
			'directCharge'   => $settings->getWalletFraudServiceId( $payment ),
		];

		if ( WalletPaymentMethods::PAY_PAL_SMART_BUTTON()->name === $payment->name ) {
			$result[ strtolower( $payment->name ) ]['payLater'] = $settings->isPayPallSmartButtonPayLater();
		}


		return $result;
	}

	public function is_active() {
		return $this->gateway->is_available();
	}
}
