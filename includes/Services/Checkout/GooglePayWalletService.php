<?php

namespace Paydock\Services\Checkout;

use Paydock\Abstracts\AbstractWalletPaymentService;
use Paydock\Enums\WalletPaymentMethods;

class GooglePayWalletService extends AbstractWalletPaymentService {
	protected function getWalletType(): WalletPaymentMethods {
		return WalletPaymentMethods::GOOGLE_PAY();
	}
    public function  get_title(){
        return trim($this->title) ? $this->title : 'Google Pay';
    }
}
