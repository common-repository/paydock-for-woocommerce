<?php

namespace Paydock\Enums;

use Paydock\Abstracts\AbstractEnum;

class BankAccountSettings extends AbstractEnum {
	protected const ENABLE = 'ENABLE';
	protected const GATEWAY_ID = 'GATEWAY_ID';
	protected const SAVE_CARD = 'SAVE_CARD';
	protected const SAVE_CARD_OPTION = 'SAVE_CARD_OPTION';

	public function getLabel(): string {
		switch ( $this->name ) {
			case self::ENABLE:
				return ucfirst( strtolower( $this->name ) );
			case self::GATEWAY_ID:
				return 'Gateway ID';
			case self::SAVE_CARD:
				return 'Save bank account';
			case self::SAVE_CARD_OPTION:
				return 'Save bank account option';
			default:
				return '';
		}
	}

	public function getInputType(): string {
		switch ( $this->name ) {
			case self::GATEWAY_ID:
				return 'text';
			case self::ENABLE:
			case self::SAVE_CARD:
				return 'checkbox';
			default:
				return 'select';
		}
	}

	public function getDescription(): string {
		switch ( $this->name ) {
			case self::SAVE_CARD:
				return 'Offer your customer the option to permanently save the bank account information at Paydock for further usage';
			default:
				return '';
		}
	}
}
