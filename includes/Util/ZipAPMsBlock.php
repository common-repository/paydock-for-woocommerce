<?php

namespace Paydock\Util;

use Paydock\Abstracts\AbstractAPMsBlock;
use Paydock\Enums\OtherPaymentMethods;
use Paydock\Services\Checkout\ZipAPMsPaymentServiceService;

class ZipAPMsBlock extends AbstractAPMsBlock {

	public function getType(): OtherPaymentMethods {
		return OtherPaymentMethods::ZIPPAY();
	}

	public function initialize() {
		$this->gateway = new ZipAPMsPaymentServiceService();
	}
}
