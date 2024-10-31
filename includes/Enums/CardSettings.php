<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class CardSettings extends AbstractEnum {
	protected const ENABLE = 'ENABLE';

	protected const SUPPORTED_CARD_TYPES = 'SUPPORTED_CARD_TYPES';

	protected const GATEWAY_ID = 'GATEWAY_ID';

	protected const DS = 'DS';
	protected const DS_SERVICE_ID = 'DS_SERVICE_ID';
	protected const TYPE_EXCHANGE_OTT = 'TYPE_EXCHANGE_OTT';
	protected const FRAUD = 'FRAUD';
	protected const FRAUD_SERVICE_ID = 'FRAUD_SERVICE_ID';
	protected const DIRECT_CHARGE = 'DIRECT_CHARGE';
	protected const SAVE_CARD = 'SAVE_CARD';
	protected const SAVE_CARD_OPTION = 'SAVE_CARD_OPTION';

	public function getInputType(): string {
		switch ( $this->name ) {
			case self::GATEWAY_ID:
			case self::DS_SERVICE_ID:
			case self::FRAUD_SERVICE_ID:
				return 'text';
			case self::ENABLE:
			case self::DIRECT_CHARGE:
			case self::SAVE_CARD:
				return 'checkbox';
			case self::SUPPORTED_CARD_TYPES:
				return 'card_select';
			case self::TYPE_EXCHANGE_OTT:
			default:
				return 'select';
		}
	}

	public function getLabel(): string {
		switch ( $this->name ) {
			case self::DS:
				return '3DS';
			case self::DS_SERVICE_ID:
				return '3DS service ID';
			case self::TYPE_EXCHANGE_OTT:
				return '3DS flow';
			case self::SUPPORTED_CARD_TYPES:
				return 'Supported card schemes';
			default:
				return ucfirst( strtolower( str_replace( '_', ' ', $this->name ) ) );
		}
	}

	public function getDescription(): string {
		switch ( $this->name ) {
			case self::SAVE_CARD:
				return 'Offer your customer to save the card permanently at Paydock for further usage';
			default:
				return '';
		}
	}

	public function getDefault(): string {
		switch ( $this->name ) {
			case self::DIRECT_CHARGE:
				return 'yes';
			default:
				return '';
		}
	}
}
