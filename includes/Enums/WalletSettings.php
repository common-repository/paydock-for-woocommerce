<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class WalletSettings extends AbstractEnum {
	protected const ENABLE = 'ENABLE';
	protected const GATEWAY_ID = 'GATEWAY_ID';
	protected const FRAUD = 'FRAUD';
	protected const FRAUD_SERVICE_ID = 'FRAUD_SERVICE_ID';
	protected const DIRECT_CHARGE = 'DIRECT_CHARGE';

	public function getInputType(): string {
		switch ( $this->name ) {
			case self::GATEWAY_ID:
			case self::FRAUD_SERVICE_ID:
				return 'text';
			case self::FRAUD:
			case self::ENABLE:
			case self::DIRECT_CHARGE:
				return 'checkbox';
			default:
				return '';
		}
	}

	public function getLabel(): string {
		return ucfirst( strtolower( str_replace( '_', ' ', $this->name ) ) );
	}

	public function getDescription(): string {
		switch ( $this->name ) {
			case self::DIRECT_CHARGE:
				return 'Direct charge stands for authorization and capture in a single request';
			default:
				return '';
		}
	}
}
