<?php

namespace Paydock\Services\Checkout;

use Paydock\Abstracts\AbstractAPMsPaymentService;
use Paydock\Enums\OtherPaymentMethods;

class AfterpayAPMsPaymentServiceService extends AbstractAPMsPaymentService {

	public function  get_title(){
		return trim($this->title) ? $this->title :  'Afterpay v1';
	}
	protected function getAPMsType(): OtherPaymentMethods {
		return OtherPaymentMethods::AFTERPAY();
	}
}
