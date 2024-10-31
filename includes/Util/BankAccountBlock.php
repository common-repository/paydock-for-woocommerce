<?php

namespace Paydock\Util;

use Paydock\Abstracts\AbstractBlock;
use Paydock\Repositories\UserTokenRepository;
use Paydock\Services\Checkout\BankAccountPaymentService;
use Paydock\Services\SettingsService;

final class BankAccountBlock extends AbstractBlock {
	protected const SCRIPT = 'bank-account-form';

	protected $name = 'paydock_bank_account_block';

	protected $gateway;

	public function initialize() {
		$this->gateway = new BankAccountPaymentService();
	}

	public function get_payment_method_data(): array {
		$settingsService = SettingsService::getInstance();
		$userTokens = [];
		if ( is_user_logged_in() ) {
			$userTokens['tokens'] = ( new UserTokenRepository() )->getUserTokens();
		}

		return array_merge( $userTokens, [ 
			'isActive' => $this->is_active(),
			// Wordpress data
			'_wpnonce' => wp_create_nonce( 'process_payment' ),
			'isUserLoggedIn' => is_user_logged_in(),
			'isSandbox' => $settingsService->isSandbox(),
			// Woocommerce data
			'amount' => WC()->cart->total,
			'currency' => strtoupper( get_woocommerce_currency() ),
			// Widget
			'title' => $settingsService->getWidgetPaymentBankAccountTitle(),
			'description' => $settingsService->getWidgetPaymentBankAccountDescription(),
			'styles' => $settingsService->getWidgetStyles(),
			// Bank Account
			'gatewayId' => $settingsService->getBankAccountGatewayId(),
			// SaveBankAccount
			'bankAccountSaveAccount' => $settingsService->getBankAccountSaveAccount(),
			'bankAccountSaveAccountOption' => $settingsService->getBankAccountSaveAccountOption(),
			// Tokens & keys
			'publicKey' => $settingsService->getPublicKey(),
			'selectedToken' => '',
			'paymentSourceToken' => '',
			// Other
			'supports' => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] ),
		] );
	}
}
