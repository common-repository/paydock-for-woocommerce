<?php

namespace Paydock\Util;

use Paydock\Abstracts\AbstractAPMsBlock;
use Paydock\Enums\OtherPaymentMethods;
use Paydock\Services\Checkout\AfterpayAPMsPaymentServiceService;

class AfterpayAPMsBlock extends AbstractAPMsBlock {

	public function getType(): OtherPaymentMethods {
		return OtherPaymentMethods::AFTERPAY();
	}

	public function initialize() {
		$this->gateway = new AfterpayAPMsPaymentServiceService();
	}
}
