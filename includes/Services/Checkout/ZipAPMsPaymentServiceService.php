<?php

namespace Paydock\Services\Checkout;

use Paydock\Abstracts\AbstractAPMsPaymentService;
use Paydock\Enums\OtherPaymentMethods;

class ZipAPMsPaymentServiceService extends AbstractAPMsPaymentService {

	protected function getAPMsType(): OtherPaymentMethods {
		return OtherPaymentMethods::ZIPPAY();
	}

    public function  get_title(){
		return trim($this->title) ? $this->title : 'Zip';
    }
}
