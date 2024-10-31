<?php

namespace Paydock\Util;

use Paydock\Abstracts\AbstractWalletBlock;
use Paydock\Enums\WalletPaymentMethods;
use Paydock\Services\Checkout\ApplePayWalletService;

final class ApplePayWalletBlock extends AbstractWalletBlock {
	public function getType(): WalletPaymentMethods {
		return WalletPaymentMethods::APPLE_PAY();
	}

	public function initialize() {
		$this->gateway = new ApplePayWalletService();
	}
}
