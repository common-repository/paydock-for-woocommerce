<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class WalletPaymentMethods extends AbstractEnum {
	protected const APPLE_PAY = 'APPLE_PAY';
	protected const GOOGLE_PAY = 'GOOGLE_PAY';
	protected const PAY_PAL_SMART_BUTTON = 'PAY_PAL_SMART_BUTTON';
	protected const AFTERPAY = 'AFTERPAY';

	public function getLabel(): string {
		switch ( $this->name ) {
			case self::APPLE_PAY:
				return 'Apple Pay';
			case self::GOOGLE_PAY:
				return 'Google Pay';
			case self::PAY_PAL_SMART_BUTTON:
				return 'PayPal Smart Button';
			case self::AFTERPAY:
				return 'Afterpay v2';
			default:
				return '';
		}
	}

	public function getId(): string {
		switch ( $this->name ) {
			case self::APPLE_PAY:
				return 'apple-pay';
			case self::GOOGLE_PAY:
				return 'google-pay';
			case self::PAY_PAL_SMART_BUTTON:
				return 'pay-pal';
			case self::AFTERPAY:
				return 'afterpay';
			default:
				return '';
		}
	}
}
