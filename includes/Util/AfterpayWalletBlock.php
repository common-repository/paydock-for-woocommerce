<?php

namespace Paydock\Util;

use Paydock\Abstracts\AbstractWalletBlock;
use Paydock\Enums\WalletPaymentMethods;
use Paydock\Services\Checkout\AfterpayWalletService;

final class AfterpayWalletBlock extends AbstractWalletBlock {
	public function getType(): WalletPaymentMethods {
		return WalletPaymentMethods::AFTERPAY();
	}

	public function initialize() {
		$this->gateway = new AfterpayWalletService();
	}
}
