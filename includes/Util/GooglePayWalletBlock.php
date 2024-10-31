<?php

namespace Paydock\Util;

use Paydock\Abstracts\AbstractWalletBlock;
use Paydock\Enums\WalletPaymentMethods;
use Paydock\Services\Checkout\GooglePayWalletService;

final class GooglePayWalletBlock extends AbstractWalletBlock {
	public function getType(): WalletPaymentMethods {
		return WalletPaymentMethods::GOOGLE_PAY();
	}

	public function initialize() {
		$this->gateway = new GooglePayWalletService();
	}
}
